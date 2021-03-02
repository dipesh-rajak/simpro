<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Subscription;
use App\Models\ZohoSubscription;
use AppHelper;
use Database\Factories\AddressFactory;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use zcrmsdk\oauth\ZohoOAuthClient;

class ZohoSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

       

        AppHelper::initAuth();
        $oAuthClient = ZohoOAuthClient::getInstanceWithOutParam();
        $accessToken = $oAuthClient->getAccessToken(config('services.zoho.user_email_id'));

        /*    print_r($data->toArray());
        exit(); */

        $data =  ZohoSubscription::factory()->count(10)->make();

        foreach ($data as $customer) {
            try {

                $address = Address::factory()->make(['type' => 'shipping']);
                $billing_address = Address::factory()->make(['type' => 'billing']);

                $customer->shipping_address = $address;
                $customer->billing_address = $billing_address;

                $client = new Client();

                $url = "https://subscriptions.zoho.com.au/api/v1/subscriptions";

                $subscription = [
                    'customer' => $customer,
                    'add_to_unbilled_charges' => true,
                    'plan' => [
                       
                        "plan_code" => "basic-monthly",            
                        "plan_description" => "Monthly Basic plan",
                        'quantity' => 1,
                        'price' => 150,
                        'exclude_setup_fee' => true,
                        'exclude_trial' => false,
                        'is_taxable' => false
                    ]
                ];

                $response = $client->request("post", $url, [
                    RequestOptions::HEADERS => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',

                    ],
                    RequestOptions::JSON => $subscription
                ]);

                if ($response->getStatusCode() == 200) {

                    $result = json_decode($response->getBody());
                    print_r($result);
                }
            } catch (Exception $ex) {

                echo $ex->getMessage();
            }
        }
    }
}
