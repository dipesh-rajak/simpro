<?php

namespace App\Http\Controllers;

use App\Jobs\ExportCustomersToZoho;
use App\Models\Customer;
use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Http\Request;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\crm\setup\users\ZCRMRole;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\Helper;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;

class CustomerController extends Controller
{
    //
    protected $token, $buildURL, $currentPage, $totalPages, $pageSize;
    function __construct()
    {
        $this->middleware('auth');

        $this->token = config('services.simpro.api_key');
        $this->buildURL = config('services.simpro.client_url');
    }

    function pushToZoho(Customer $customer)
    {


        ExportCustomersToZoho::dispatchNow($customer);

        $customer = $customer->refresh();
        return redirect(route('dashboard'))->with('success', $customer->email . ' successfully pushed to Zoho: Zoho ID: ' . $customer->zoho_reference_id);
    }



    function createZohoSite($customer)
    {

        foreach ($customer->sites as $site) {

            $site = $site->load(['address', 'billing_address']);

            Log::debug(print_r($site->toArray(), true));
            try {



                $record = ZCRMRecord::getInstance("Sites", null);
                $record->setFieldValue('Name', $site->name);
                $record->setFieldValue('Street_Address', $site->address->address);
                $record->setFieldValue('Suburb',  $site->address->city);
                $record->setFieldValue('State', $site->address->state);
                $record->setFieldValue('Country', $site->address->country);

                $record->setFieldValue('Postal_Address', $site->billing_address ? $site->billing_address->address : '');
                $record->setFieldValue('Postal_State', $site->billing_address->state);
                $record->setFieldValue('Postal_Postcode', $site->billing_address->postalCode);
                $record->setFieldValue('Postal_Suburb', $site->billing_address->city);

                $record->setFieldValue('Postal_Contact', $customer->name);
                $record->setFieldValue('Customer_ID', $customer->zoho_reference_id);

                $record->setFieldValue('Simpro_Site_ID', $site->sim_id);


                $trigger = array(); //triggers to include
                $lar_id = ""; //lead assignment rule id
                $responseIns = $record->create($trigger, $lar_id);
                $code = $responseIns->getHttpStatusCode(); // To get http response code
                $status =  $responseIns->getStatus(); // To get response status

                if ($code == 201) {
                    $zohoId =  $record->getEntityId();
                } else {

                    Log::error(sprintf('%s:%s error occurred while creating site %s', __CLASS__, __LINE__, $customer->email));
                    Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));

                    Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
                }
            } catch (ZCRMException $ex) {


                $code =  $ex->getExceptionCode();
                $details = $ex->getExceptionDetails();

                Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
                Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
            } catch (Exception $ex) {
                echo "error: " . $ex->getMessage() . '<br/>';
                Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
                Log::error($ex->getTraceAsString());
            }
        } //foreach site ends
    }

    function pushToSimPro(Customer $customer)
    {

        if (!$customer)
            abort(404);

        $customer = $customer->load(['address', 'billingAddress']);

        $provider = (new \simPRO\RestClient\OAuth2\APIKey())
            ->withBuildURL($this->buildURL)
            ->withToken($this->token);

        try {

            if ($customer->type == 'customer') {
                $this->createSimCustomer($provider, $customer);
                $customer = $customer->refresh();
                $this->updateCustomerCustomField($customer->sim_id, $customer, $provider);
                $customer->refresh();

                return redirect(route('dashboard'))->with('success', $customer->email . ' successfully pushed to simpro: simpro ID: ' . $customer->sim_id);
            } else {
                $leadId = $this->createSimLead($provider, $customer);
                return redirect(route('dashboard'))->with('success', $customer->email . ' successfully pushed to simpro: simpro ID: ' . $customer->sim_id);
            }
        } catch (\Exception  $ex) {
            echo "error: " . $ex->getMessage() . '<br/>';
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    function createSimLead($provider, $customer)
    {

        $simId = $this->createSimCustomer($provider, $customer);

        $customer = $customer->load('sites');

        if (empty($customer->sites)) {
            Log::error(sprintf('%s:%s No sites found %s', __CLASS__, __LINE__,   $customer->email));
            return;
        }

        $url = '/api/v1.0/companies/0/leads/';
        $method = 'post';
        $data = [
            'LeadName' => $customer->name,
            'Customer' => (int)$simId,
            'Site' => $customer->sites->first()->sim_id,

        ];

        $request = $provider->fetchRequest($url, $method, $data);
        $response = $provider->fetchResponse($request);
        $headers = $response->getHeaders();

        $json = json_decode((string)$response->getBody());


        if (($json) && ($json->ID)) {
            $customer->sim_lead_id = $json->ID;
            $customer->save();
        }

        return $json->ID;
    }

    function createSimCustomer($provider, $customer)
    {
        //Company ID is 0 for single-company builds. If you are querying against a multi-company build, adjust the company id accordingly.
        $url = '/api/v1.0/companies/0/customers/individuals/?createSite=true';
        $method = 'post';


        $data = [

            'Address' => [
                'Address' => $customer->address->address,
                'City' => $customer->address->city,
                'State' => $customer->address->state,
                'PostalCode' => $customer->address->postalCode,
                'Country' => $customer->address->country,

            ],
            'BillingAddress' => [
                'Address' => $customer->billingAddress->address,
                'City' => $customer->billingAddress->city,
                'State' => $customer->billingAddress->state,
                'PostalCode' => $customer->billingAddress->postalCode,
                'Country' => $customer->billingAddress->country,
            ],
            'Email' => $customer->email,
            'CustomerType' => ucfirst($customer->customer_type),

            /*  'EIN' => $customer->ein,
            'Website' => $customer->website, */
            'Phone' => $customer->phone,

            'Sites' => []
        ];

        if ($customer->is_company) {
            $url = '/api/v1.0/companies/0/customers/companies/?createSite=true';
            $data = array_merge($data, [
                'CompanyName' => $customer->company_name,
                'EIN' => (string) $customer->ein,
                'Website' => $customer->website,
                'CompanyNumber' => $customer->company_number,
                'CustomerType' => 'Customer',
                'Fax' => $customer->phone

            ]);
        } else {
            $data = array_merge($data, [
                'GivenName' => $customer->given_name,
                'FamilyName' => $customer->family_name,
                'Phone' => $customer->phone
            ]);
        }

        //print '<strong>' . $method . ' ' . $url . '</strong><br /><pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders(); //Array of headers which were sent in the response.

        $location = $headers['Location'][0]; //Eg. /api/v1.0/companies/0/customers/individuals/1999
        $resourceBaseURI = $headers['Resource-Base-URI'][0]; //Eg. /api/v1.0/companies/0/customers/individuals/
        $resourceID = $headers['Resource-ID'][0]; //Eg. 1999
        $json = json_decode((string)$response->getBody()); //Full json representation of the customer we just inserted.


        Log::debug($response->getBody());

        /*  print 'New Customer:<br /><pre>' . json_encode($json, JSON_PRETTY_PRINT) . '</pre>';
        print 'Customer ID: ' . $resourceID . '<br />';
        print 'Accessed by URL: ' . $location . '<br />';
        print '<br />'; */

        $customer->sim_id = $resourceID;
        $customer->save();

        if (!empty($json->Sites)) {
            foreach ($json->Sites as $simSite) {

                $dbSite =   Site::updateOrCreate([
                    'sim_id' => $simSite->ID
                ], [
                    'sim_id' => $simSite->ID,
                    'zoho_id' => null,
                    'name' => $simSite->Name,
                    'billing_contact' => $customer->id,
                    'address_id' => $customer->address->id,
                    'billingAddress_id' => $customer->billingAddress->id,
                    'public_notes' => $simSite->PublicNotes ?? '',
                    'private_notes' => $simSite->PrivateNotes ?? '',
                    'archived' => $simSite->Archived ?? false,
                    'customer_id' => $customer->id

                ]);

                // $customer->sites->save($dbSite);

            }
        }

        return $resourceID;
    }

    function updateCustomerCustomField($simId, $customer, $provider)
    {
        $url = "/api/v1.0/companies/0/customers/$simId/customFields/2";
        $method = "PATCH";
        $data = ['Value' => $customer->source];
        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders();
    }
    
}
