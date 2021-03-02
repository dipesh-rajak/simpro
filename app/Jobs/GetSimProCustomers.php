<?php

namespace App\Jobs;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Setting;
use AppHelper;
use Hamcrest\Core\Set;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetSimProCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $token, $buildURL, $currentPage, $totalPages, $pageSize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
        $this->token = config('services.simpro.api_key');
        $this->buildURL = config('services.simpro.client_url');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $pageSetting = Setting::where('slug', 'simpro.customers.pageNo.')->first();
        $this->currentPage = $pageSetting->value ?? 0;

        /*  $provider = (new \simPRO\RestClient\OAuth2\APIKey())
            ->withBuildURL($this->buildURL)
            ->withToken($this->token); */

        $provider = AppHelper::getSimProProvider();

        //First, fetch and select a company ID. For most builds, there is only one company id.
        $companyID = $this->getCompanyID($provider);

        //Then, list all employees and select one to view full details.

        $totalPageSetting = Setting::where('slug', 'simpro.customers.totalPages')->first();
        if ((!$totalPageSetting) || ($totalPageSetting->value > $this->currentPage)) {
            $this->getCustomers($provider, $companyID);
        }
    }

    function getCompanyID($provider)
    {
        $companyURL = '/api/v1.0/companies/';

        Log::debug("Now calling URL {$companyURL}" . PHP_EOL);
        $request = $provider->fetchRequest($companyURL); //PSR7 Request Object
        $response = $provider->fetchResponse($request); //PSR7 Response Object
        $companyArray = json_decode((string)$response->getBody());
        Log::debug("Response is:" . PHP_EOL);
        Log::debug(json_encode($companyArray, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL);

        foreach ($companyArray as $companyObject) {
            Log::debug("Company with ID " . $companyObject->ID . " is named " . $companyObject->Name . PHP_EOL);
        }

        //Select which company ID you wish to use.
        $companyID = $companyArray[count($companyArray) - 1]->ID;
        Log::debug("Using company id {$companyID}" . PHP_EOL . PHP_EOL);
        return $companyID;
    }

    function getCustomers($provider, $companyID)
    {
        //Fetch a list of employees
        $nextPage = $this->currentPage + 1;
        $employeeListURL = "/api/v1.0/companies/{$companyID}/customers/?page=" . $nextPage;

        Log::debug("Now calling URL {$employeeListURL}" . PHP_EOL);
        $request = $provider->fetchRequest($employeeListURL);
        $response = $provider->fetchResponse($request); //Can use fetchJSON instead of fetchResponse to just get the JSON.

        $total = $response->hasHeader('Result-Total') ? $response->getHeader('Result-Total')[0] : 0;

        Setting::updateOrCreate(['slug' => 'simpro.customers.totalItems'], [
            'slug' => 'simpro.customers.totalItems',
            'value' => $total
        ]);


        $pages = $response->hasHeader('Result-Pages') ? $response->getHeader('Result-Pages')[0] : 0;

        Setting::updateOrCreate(['slug' => 'simpro.customers.totalPages'], [
            'slug' => 'simpro.customers.totalPages',
            'value' => $pages
        ]);
        $employeeArray = $provider->fetchJSON($request); //Can use fetchJSON instead of fetchResponse to just get the JSON.

        Log::debug('Customers Count: ' . count($employeeArray) . PHP_EOL);

        foreach ($employeeArray as $employeeObject) {

            Log::debug('Selected Customer: ' . PHP_EOL);
            Log::debug(json_encode($employeeObject, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL);

            $employeeID = $employeeObject->ID;

            //sync details
            //  $this->viewCustomerDetails($provider, $companyID, $employeeObject->_href);          
            SyncSPCustomer::dispatch($companyID, $employeeObject->ID, strpos($employeeObject->_href, 'individuals') !== false);
            break;
        }

        Setting::updateOrCreate(['slug' => 'simpro.customers.pageNo.'], [
            'slug' => 'simpro.customers.pageNo.',
            'value' => $nextPage
        ]);
    }
}
