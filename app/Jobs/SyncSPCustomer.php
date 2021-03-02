<?php

namespace App\Jobs;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;


class SyncSPCustomer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customerId, $companyId;
    protected $token, $buildURL;
    protected $individual;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($companyId, $customerId, $individual = false)
    {
        $this->companyId = $companyId;
        $this->customerId = $customerId;
        $this->individual = $individual;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            $config = AppHelper::simProAuth();
            $provider = (new \simPRO\RestClient\OAuth2\APIKey())
                ->withBuildURL($config['url'])
                ->withToken($config['apiKey']);

            $this->viewCustomerDetails($provider, 0, $this->customerId);
        } catch (Exception $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': Error occurred.');
            Log::error($ex->getMessage());
            Log::error($ex->getTraceAsString());
        }
    }

    function viewCustomerDetails($provider, $companyID, $customerID)
    {
        if ($this->individual)
            $employeeGetURL = "/api/v1.0/companies/{$companyID}/customers/individuals/{$customerID}";
        else {
            $employeeGetURL = "/api/v1.0/companies/{$companyID}/customers/companies/{$customerID}";
        }
        //Log::debug("Now calling URL {$employeeGetURL}" . PHP_EOL);
        $employeeInfo = $provider->fetchJSON($provider->fetchRequest($employeeGetURL)); //Can combine into one line.
        if (!$employeeInfo) {
            Log::error(__CLASS__ . ': ' . __LINE__ . ' No data found.');
            return;
        }
        //Log::debug(json_encode($employeeInfo, JSON_PRETTY_PRINT) . PHP_EOL);

        $customFieldsArray = Arr::pluck(json_decode(json_encode($employeeInfo->CustomFields), true), 'Value',  'CustomField.Name');

        // Log::debug(print_r($customFieldsArray, true));

        $existing_criteria = ['sim_id' => $employeeInfo->ID];
        $zoho_id = null;

        //if Zoho ID is set
        if (isset($customFieldsArray['Zoho ID']) && $customFieldsArray['Zoho ID']) {
            $zoho_id = $customFieldsArray['Zoho ID'];
            $existing_criteria = array_merge($existing_criteria, ['zoho_reference_id' => $zoho_id]);
        }

        $dbCustomer = Customer::updateOrCreate(
            $existing_criteria,
            [

                'sim_id' => $employeeInfo->ID,
                'email' => $employeeInfo->Email,
                'title' => $employeeInfo->Title ?? '',
                'given_name' => $employeeInfo->GivenName ?? '',
                'family_name' => $employeeInfo->FamilyName ?? '',
                'phone' => $employeeInfo->Phone ?? '',
                'dnd' => $employeeInfo->DoNotCall,
                'customer_type' => strtolower($employeeInfo->CustomerType),
                'archived' => $employeeInfo->Archived,
                'ein' => $employeeInfo->EIN ?? '',
                'fax' => $employeeInfo->Fax ?? '',
                'company_number' => $employeeInfo->CompanyNumber ?? '',
                'source' => !empty($customFieldsArray) ? $customFieldsArray['How Customer Was Acquired'] : '',
                'company_name' => $employeeInfo->CompanyName ?? '',
                'is_company' => !$this->individual,
                'have_subscription' => !empty($customFieldsArray) ? (isset($customFieldsArray['Have Subscription?']) ? ($customFieldsArray['Have Subscription?'] == 'Yes' ? true : false) : false) : false,
                'mobile' => $employeeInfo->CellPhone ?? '',
                'description' => $employeeInfo->Description ?? '',
                'altphone' => $employeeInfo->AltPhone ?? '',
                'website' => $employeeInfo->Website ?? '',

            ]
        );



        /*  Log::debug('address', $dbCustomer->address->toArray());
        Log::debug('billingAddress', $dbCustomer->billingAddress->toArray());

        exit(); */

        /*  if(!$dbCustomer->wasRecentlyCreated){
            $dbCustomer->load('address', 'BillingAddress');
        } */

        //save address
        /* $dbCustomer->address()->updateOrCreate(['type' => 'address', 'customer' => $dbCustomer->id], [
            'address' => $employeeInfo->Address->Address ?? '',
            'city' => $employeeInfo->Address->City ?? '',
            'state' => $employeeInfo->Address->State ?? '',
            'postalCode' => $employeeInfo->Address->PostalCode ?? '',
            'country' => $employeeInfo->Address->Country ?? '',
            'type' => 'address',

        ]); */

        //save billing address
        /* $dbCustomer->billingAddress()->updateOrCreate(['type' => 'billing', 'customer' => $dbCustomer->id], [
            'address' => $employeeInfo->BillingAddress->Address ?? '',
            'city' => $employeeInfo->BillingAddress->City ?? '',
            'state' => $employeeInfo->BillingAddress->State ?? '',
            'postalCode' => $employeeInfo->BillingAddress->PostalCode ?? '',
            'country' => $employeeInfo->BillingAddress->Country ?? '',
            'type' => 'billing'
        ]); */

        $address = Address::where('type', 'address')->where('customer', $dbCustomer->id)->first();
        $update = false;
        if (!$address) {
            $address = Address::create([
                'address' => $employeeInfo->Address->Address ?? '',
                'city' => $employeeInfo->Address->City ?? '',
                'state' => $employeeInfo->Address->State ?? '',
                'postalCode' => $employeeInfo->Address->PostalCode ?? '',
                'country' => $employeeInfo->Address->Country ?? '',
                'type' => 'address',
                'customer' => $dbCustomer->id
            ]);
        } else {
            $address->address = $employeeInfo->Address->Address ?? '';
            $address->city = $employeeInfo->Address->City ?? '';
            $address->state = $employeeInfo->Address->State ?? '';
            $address->postalCode = $employeeInfo->Address->PostalCode ?? '';
            $address->country = $employeeInfo->Address->Country ?? '';
            $address->save();
            $update = $address->wasChanged();
        }

        $billingAddress = Address::where('type', 'billing')->where('customer', $dbCustomer->id)->first();
        $billingUpdate = false;
        if (!$billingAddress) {
            $billingAddress = Address::create([
                'address' => $employeeInfo->BillingAddress->Address ?? '',
                'city' => $employeeInfo->BillingAddress->City ?? '',
                'state' => $employeeInfo->BillingAddress->State ?? '',
                'postalCode' => $employeeInfo->BillingAddress->PostalCode ?? '',
                'country' => $employeeInfo->BillingAddress->Country ?? '',
                'type' => 'billing',
                'customer' => $dbCustomer->id
            ]);
        } else {
            $billingAddress->address = $employeeInfo->BillingAddress->Address ?? '';
            $billingAddress->city = $employeeInfo->BillingAddress->City ?? '';
            $billingAddress->state = $employeeInfo->BillingAddress->State ?? '';
            $billingAddress->postalCode = $employeeInfo->BillingAddress->PostalCode ?? '';
            $billingAddress->country = $employeeInfo->BillingAddress->Country ?? '';
            $billingAddress->save();
            $billingUpdate = $billingAddress->wasChanged();
        }


        if ($dbCustomer->wasRecentlyCreated) {

            Log::debug('Customer created in database: simpro ID. ' . $dbCustomer->sim_id);
            if ($zoho_id) {
                Log::debug('Customer has Zoho Id' . $zoho_id);
                $dbCustomer->zoho_reference_id = $zoho_id;
                $dbCustomer->save();
            }
            if ($dbCustomer->customer_type == 'customer') {
                ExportCustomersToZoho::dispatch($dbCustomer);
            } else {
                ExportLeadToZoho::dispatch($dbCustomer);
            }
        } else if ($dbCustomer->wasChanged()) {
            $changes = $dbCustomer->getChanges();
            Log::debug('customer changes', $changes);

            $type_changed = $dbCustomer->wasChanged('customer_type');
            $company_changed = $dbCustomer->wasChanged('is_company');

            if ($dbCustomer->customer_type == 'customer') {
                ExportCustomersToZoho::dispatch($dbCustomer, $type_changed, $company_changed);
            } else {
                ExportLeadToZoho::dispatch($dbCustomer);
            }
        } else if ($update || $billingUpdate) {
            Log::debug('address changes');
            if ($dbCustomer->customer_type == 'customer') {
                ExportCustomersToZoho::dispatch($dbCustomer);
            } else {
                ExportLeadToZoho::dispatch($dbCustomer);
            }
        } else {
            Log::debug('No change in the customer information: ' . $dbCustomer->email);
        }

        




        if (!empty($employeeInfo->Sites)) {
            foreach ($employeeInfo->Sites as $simSite) {

                SyncSimProSites::dispatch($dbCustomer, $simSite->ID);
            }
        }
    } //function

    public function middleware()
    {
        return [(new WithoutOverLapping($this->customerId))->releaseAfter(10)];
    }
}
