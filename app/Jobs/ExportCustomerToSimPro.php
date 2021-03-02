<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/***
 * Updates Customer in SimPro
 */
class ExportCustomerToSimPro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (!$this->customer) {
                throw new Exception('customer is required.');
            }

            $provider = AppHelper::getSimProProvider();
            $this->createSimCustomer($provider, $this->customer);
        } catch (Exception  $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': Error occurred.');
            Log::error($ex->getMessage());
            Log::debug($ex->getTraceAsString());
        }
    }

    function createSimCustomer($provider, $customer)
    {
        //Company ID is 0 for single-company builds. If you are querying against a multi-company 
        $url = '/api/v1.0/companies/0/customers/individuals/?createSite=true';
        $method = 'post';
        $customer = $customer->load(['address', 'billingAddress']);

       // Log::debug(print_r($customer, true));

        if ($customer->sim_id) {
            $url = "/api/v1.0/companies/0/customers/individuals/" . $customer->sim_id;
            $method = 'patch';
            Log::debug(sprintf('%s:%s customer sim id %d exists.', __CLASS__, __LINE__, $customer->sim_id));
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
            'CustomerType' => ucfirst($customer->customer_type),
            'Phone' => $customer->phone,
            'AltPhone' => $customer->altphone,
            'DoNotCall' => $customer->dnd ?? false,

        ];


        $title = trim(str_replace(".", '', $customer->title));

        // Log::debug(' title: ' . $title);

        if ($customer->is_company) {

            $url = '/api/v1.0/companies/0/customers/companies/?createSite=true';
            if ($customer->sim_id) {
                $url = '/api/v1.0/companies/0/customers/companies/' . $customer->sim_id;
            }

            $companyName = $customer->company_name;
            if($companyName ==''){
                $companyName = $customer->name;
            }

            $data = array_merge($data, [
                'CompanyName' => $companyName,
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

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders(); //Array of headers which were sent in the response.

        $resourceID = 0;

        if (isset($headers['Resource-ID']) && (isset($headers['Resource-ID'][0]))) {
            $resourceID = $headers['Resource-ID'][0]; //Eg. 1999
        }

        if (!$resourceID) {
            throw new Exception(__CLASS__ . __LINE__ . ': Customer could not be created in Simpro.', $headers);
        }

        $json = json_decode((string)$response->getBody()); //Full json representation of the customer we just inserted.

        //Log::debug(print_r($json, true));

        if (!$customer->sim_id) {
            $customer->sim_id = $resourceID;
            $customer->save();
            Log::debug(sprintf('%s:%s Customer %s sim id %d saved now.', __CLASS__, __LINE__, $customer->given_name, $resourceID));
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

            Log::debug(sprintf(' %s:%s:  %s custom field id %d=> %s updated. Status: %d ', __CLASS__, __LINE__, $customer->email, $cfID, $value, $response->getStatusCode()));
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    public function middleware()
    {

        return [(new WithoutOverlapping($this->customer->id))->releaseAfter(10)];
    }
}
