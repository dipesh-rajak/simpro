<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\Subscription;
use AppHelper;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\utility\ZohoOAuthTokens;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\oauth\ZohoOAuthClient;

class GetZSCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        AppHelper::initAuth();
        $settings =         Setting::pluck('value', 'slug');
        $user_email_id = $settings['zoho.app.user_email'] ?? config('services.zoho.user_email_id');
        $oAuthClient = ZohoOAuthClient::getInstanceWithOutParam();
        $accessToken = $oAuthClient->getAccessToken($user_email_id);
       /*  echo $accessToken . PHP_EOL;
        exit(); */

        //https://subscriptions.zoho.com.au/api/v1/subscriptions?filter_by=SubscriptionStatus.ACTIVE

        $client = new Client();

        $url = "https://subscriptions.zoho.com.au/api/v1/customers";

        $response = $client->request("GET", $url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() == 200) {

            $result = json_decode($response->getBody());

            if (($result->message == 'success') && !empty($result->customers)) {

               /*  print_r($result);
                exit(); */

               // Log::debug( '', [$result]);

                echo 'No. of customers = ' . count($result->customers) . PHP_EOL;

                foreach ($result->customers as $zscustomer) {

                    $email = $zscustomer->email;
                    $customer_id = $zscustomer->customer_id;

                    $dbCustomer = Customer::updateOrCreate(['email' => $email, 'zoho_sub_id' => $customer_id

                    ], [
                        'email' => $email, 
                        'zoho_sub_id' => $customer_id,
                        'given_name' => $zscustomer->first_name,
                        'family_name' => $zscustomer->last_name,
                        'phone' => $zscustomer->phone,
                        'source' => 'Zoho Subscriptions',
                        'customer_type' => 'customer'
                    ]);

                    if($dbCustomer->wasRecentlyCreated) {
                        Log::debug('customer '. $email . ' created');
                    }

                    $this->getCustomerDetail($accessToken, $dbCustomer);
                   
                }
            }
        }

    
    }

    function getCustomerDetail($accessToken, $dbCustomer) {
        $client = new Client();

        $url = "https://subscriptions.zoho.com.au/api/v1/customers/". $dbCustomer->zoho_sub_id;

        $response = $client->request("GET", $url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() == 200) {

            $result = json_decode($response->getBody());

           /*  Log::debug(print_r($result->customer, true));
            exit(); */

            if (($result->message == 'success') && !empty($result->customer)) {

                $zscustomer = $result->customer;

                $dbCustomer->zoho_reference_id = $zscustomer->zcrm_contact_id;
                $dbCustomer->is_company = $zscustomer->customer_sub_type =='business'?true:false;
                $dbCustomer->save();
            }

            //zcrm_contact_id
        }
    }
}
