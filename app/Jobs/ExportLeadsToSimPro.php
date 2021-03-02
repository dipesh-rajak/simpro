<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExportLeadsToSimPro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;
    protected $token, $buildURL;
    protected $dbLead;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, Lead $lead = null)
    {
        $this->customer = $customer;
        $this->dbLead = $lead;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

        try {
            if (!$this->customer) {
                throw new Exception(' Customer is required to push to SimPro. ');
            }
            $this->pushToSimPro($this->customer);
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    //push customer details to SimPro

    function pushToSimPro(Customer $customer)
    {


        $customer = $customer->load(['address', 'billingAddress']);
        $provider = AppHelper::getSimProProvider();

        try {

            if ($customer->type == 'customer') {
                $this->createSimCustomer($provider, $customer);
                $customer = $customer->refresh();
                //$this->updateCustomerCustomField($customer->sim_id, $customer, $provider);
                $customer->refresh();

                Log::debug('success: ' . $customer->email . ' successfully pushed to simpro: simpro ID: ' . $customer->sim_id);
            } else {
                $leadId = $this->createSimLead($provider, $customer);

                Log::debug('success: Lead ' . $customer->email . ' successfully pushed to simpro: simpro Lead ID: ' . $leadId . ' SimPro CustomerId: ' . $customer->sim_id);
            }
        } catch (\Exception  $ex) {

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

        if ($customer->sim_lead_id) {

            $url = '/api/v1.0/companies/0/leads/' . $customer->sim_lead_id;
            $method = "patch";
        }

        $data = [
            'LeadName' => $customer->is_company ? $customer->company_name : $customer->name,
            'Customer' => (int)$simId,
            'Site' => $customer->sites->first()->sim_id,
            'Description' => $customer->description ?? '',


        ];

        if ($customer->salesperson_id) {
            $data['Salesperson'] = $customer->salesperson_id;
        }

        /* if($this->dbLead->zoho_id){
            $data['CustomFields']= [

            ];
        } */

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders();

        if ($response->getStatusCode() <= 204) {

            if ($method == 'post') {
                $json = json_decode((string)$response->getBody());

                if (!$json) {
                    Log::error(sprintf('%s:%s no response received', __CLASS__, __LINE__));
                    Log::error($response->getBody());
                    throw new Exception($response->getReasonPhrase());
                }


                $customer->sim_lead_id = $json->ID;
                $customer->save();


                if ($this->dbLead) {
                    $this->dbLead->sim_id = $json->ID;
                    $this->dbLead->save();
                } else {

                    $this->dbLead =  Lead::updateOrCreate(['sim_id' => $json->ID], [
                        'sim_id' => $json->ID,
                        'name' => $json->Name,
                        'status' => $json->Status,
                        'stage' => $json->Stage,
                        'notes' => $json->Notes,
                        'followupdate' => $json->FollowUpDate,
                        'description' => $json->Description,
                        'salesperson_id' => $json->Salesperson->ID,
                        'customer_id' => $customer->id,
                    ]);
                }

                //Log::debug('Lead saved in lead table: '. $lead->sim_id);
                Log::debug(sprintf('%s:%s Lead saved in lead table: ID:%d, Simpro ID: %d, Zoho Id:%d ', __CLASS__, __LINE__, $this->dbLead->id, $this->dbLead->sim_id, $this->dbLead->zoho_id));

                $this->updateLeadZohoId($this->dbLead, $provider);
            }

            if ($customer->sim_lead_id) {
                return $customer->sim_lead_id;
            }
        } else {
            Log::error(__CLASS__ . ':' . __LINE__ . ' could not create lead.');
            Log::error($response->getReasonPhrase());
        }
    }

    function createSimCustomer($provider, $customer)
    {
        //Company ID is 0 for single-company builds. If you are querying against a multi-company build, adjust the company id accordingly.
        $url = '/api/v1.0/companies/0/customers/individuals/?createSite=true';
        $method = 'post';

        if ($customer->sim_id) {
            $url = "/api/v1.0/companies/0/customers/individuals/" . $customer->sim_id;
            $method = 'patch';
        }


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

            /*  'EIN' => $customer->ein,
            'Website' => $customer->website, */
            /*  'Phone' => $customer->phone */


            'CustomerType' => ucfirst($customer->customer_type),
            'Phone' => $customer->phone,
            'AltPhone' => $customer->altphone,
            'DoNotCall' => $customer->dnd ?? false,

        ];

        if ($customer->sites) {
            //$data = array_merge($data, ['Sites'])
        }

        $title = trim(str_replace(".", '', $customer->title));

        // Log::debug(' title: ' . $title);


        if ($customer->is_company) {

            $url = '/api/v1.0/companies/0/customers/companies/?createSite=true';
            if ($customer->sim_id) {
                $url = '/api/v1.0/companies/0/customers/companies/' . $customer->sim_id;
            }

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
                'Title' => $title,
                'CellPhone' => $customer->mobile,

            ]);
        }



        //print '<strong>' . $method . ' ' . $url . '</strong><br /><pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders(); //Array of headers which were sent in the response.

        $resourceID = 0;

        if (isset($headers['Resource-ID']) && (isset($headers['Resource-ID'][0]))) {
            $resourceID = $headers['Resource-ID'][0]; //Eg. 1999
        }

        if (!$resourceID) {
            throw new Exception(__CLASS__ . __LINE__ . ': customer could not be created.', $headers);
        }


        $json = json_decode((string)$response->getBody()); //Full json representation of the customer we just inserted.

        //Log::debug(print_r($json, true));

        if (!$customer->sim_id) {
            $customer->sim_id = $resourceID;
            $customer->save();
        }


        $this->updateCustomerCustomFields($resourceID, $customer, $provider);

        if (!empty($json->Sites)) {
            foreach ($json->Sites as $simSite) {
                SyncSimProSites::dispatch($customer, $simSite->ID);
            }
        }


        return $resourceID;
    }



    function updateCustomerCustomFields($simId, $customer, $provider)
    {
        $customFieldSetting = Setting::where('slug', 'simpro.fields')->first();
        if ($customFieldSetting) {
            $customFields = json_decode($customFieldSetting->value);
            foreach ($customFields as $name => $id) {
                $value = '';
                if ($name == 'How Customer Was Acquired') {
                    $value = $customer->source;
                } else if ($name == 'Have Subscription?') {
                    $value = $customer->have_subscription ? "Yes" : "No";
                } else if ($name == 'Zoho ID') {
                    $value = $customer->zoho_reference_id ?? '';
                }

                $this->updateCustomerCustomField($simId, $customer, $provider, $id, $value);
            }
        }
    }

    function updateCustomerCustomField($simId, $customer, $provider, $cfID, $value)
    {
        try {
            $url = "/api/v1.0/companies/0/customers/$simId/customFields/" . $cfID;
            $method = "PATCH";
            $data = ['Value' => (string)$value];
            $response = $provider->fetchResponse($provider->fetchRequest($url, $method, $data)); //PSR7 Response object.

            // Log::debug(sprintf(' %s custom field id %d=> %s updated. Status: %d ', $customer->email, $cfID, $value, $response->getStatusCode()));
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    function updateLeadZohoId($lead, $provider)
    {
        try {
            $cfID = 106;
            //106
            $url = "/api/v1.0/companies/0/leads/{$lead->sim_id}/customFields/{$cfID}";
            $method = "PATCH";
            $data = ['Value' => (string)$lead->zoho_id];
            $response = $provider->fetchResponse($provider->fetchRequest($url, $method, $data)); //PSR7 Response object.

            Log::debug(sprintf('lead %d custom field id %d=> %s updated. Status: %d ', $lead->id, $cfID, $lead->zoho_id, $response->getStatusCode()));
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->customer->id))->releaseAfter(10)];
    }
}
