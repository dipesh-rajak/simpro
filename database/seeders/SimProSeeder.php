<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Site;
use AppHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SimProSeeder extends Seeder
{

    protected $token, $buildURL, $currentPage, $totalPages, $pageSize;
    protected $site_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //

    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $customers =  Customer::factory()->count(1)->make(['customer_type' => 'lead']);

        /*  print_r($customers);
        exit(); */

        $config = AppHelper::simProAuth();

        $provider = (new \simPRO\RestClient\OAuth2\APIKey())
            ->withBuildURL($config['url'])
            ->withToken($config['apiKey']);


        foreach ($customers as $customer) {
            $this->pushToSimPro($provider, $customer);
        }
    }





    function pushToSimPro($provider, Customer $customer)
    {

        if (!$customer)
            abort(404);

        //$customer = $customer->load(['address', 'billingAddress']);



        try {

            $simId = $this->createSimCustomer($provider, $customer);

            if ($customer->customer_type == 'lead') {
                $leadId = $this->createSimLead($provider, $customer, $simId);
            }
        } catch (\Exception  $ex) {
            echo "error: " . $ex->getMessage() . '<br/>';
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    function createSimLead($provider, $customer, $simId)
    {



        $customer = $customer->load('sites');

        $url = '/api/v1.0/companies/0/leads/';
        $method = 'post';
        $data = [
            'LeadName' => $customer->name,
            'Customer' => (int)$simId,
            'Site' =>  $this->site_id,

        ];

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders();

        $json = json_decode((string)$response->getBody());

        /*  print_r($json);
        exit(); */

        if ($json) {
            $customer->sim_lead_id = $json->ID;
            //$customer->save();
        }

        return $json->ID;
    }

    function createSimCustomer($provider, $customer)
    {
        //Company ID is 0 for single-company builds. If you are querying against a multi-company build, adjust the company id accordingly.
        $url = '/api/v1.0/companies/0/customers/individuals/?createSite=true';

        $method = 'post';
        $address = Address::factory()->make(['type' => 'shipping']);
        $billing_address = Address::factory()->make(['type' => 'billing']);

        $data = [

            'Address' => [
                'Address' => $address->address,
                'City' => $address->city,
                'State' => $address->state,
                'PostalCode' => $address->postalCode,
                'Country' => $address->country,

            ],
            'BillingAddress' => [
                'Address' => $billing_address->address,
                'City' => $billing_address->city,
                'State' => $billing_address->state,
                'PostalCode' => $billing_address->postalCode,
                'Country' => $billing_address->country,
            ],
            'Email' => $customer->email,

            /*  'EIN' => $customer->ein,
            'Website' => $customer->website, */
            /*  'Phone' => $customer->phone */

            'Sites' => [],
            'CustomerType' => ucfirst($customer->customer_type),
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


        // Log::debug($response->getBody());

        print 'New Customer:<br /><pre>' . json_encode($json, JSON_PRETTY_PRINT) . '</pre>';
        print 'Customer ID: ' . $resourceID . '<br />';
        print 'Accessed by URL: ' . $location . '<br />';
        print '<br />';

       

        $customer->sim_id = $resourceID;
        if (!empty($json->Sites)) {
            $this->site_id = $json->Sites[0]->ID;
        }


        $this->updateCustomerCustomFields($resourceID, $customer, $provider);

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
                }

                $this->updateCustomerCustomField($simId, $customer, $provider, $id, $value);
            }
        }
    }

    function updateCustomerCustomField($simId, $customer, $provider, $cfID, $value)
    {
        $url = "/api/v1.0/companies/0/customers/$simId/customFields/" . $cfID;
        $method = "PATCH";
        $data = ['Value' => $value];   
        $response = $provider->fetchResponse($provider->fetchRequest($url, $method, $data)); //PSR7 Response object.
        
        echo $response->getStatusCode(). PHP_EOL;

    }
}
