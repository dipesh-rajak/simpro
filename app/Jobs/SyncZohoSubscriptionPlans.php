<?php

namespace App\Jobs;

use App\Models\Plan;
use AppHelper;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use zcrmsdk\oauth\ZohoOAuthClient;

class SyncZohoSubscriptionPlans implements ShouldQueue
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
        $config = AppHelper::initAuth();
        $oAuthClient = ZohoOAuthClient::getInstanceWithOutParam();
        $accessToken = $oAuthClient->getAccessToken($config['currentUserEmail']);
        $client = new Client();

        $url = "https://subscriptions.zoho.com.au/api/v1/plans";

        $response = $client->request("GET", $url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() == 200) {

            $result = json_decode($response->getBody());
            if (($result->message == 'success') && !empty($result->plans)) {    

                foreach($result->plans as $plan) {
                    Plan::updateOrCreate([
                        'code' => $plan->plan_code
                    ],  [
                        'code' => $plan->plan_code,
                        'name' => $plan->name??'',
                        'status' => $plan->status,
                        'description' => $plan->description??'',
                        'product_id'=> $plan->product_id??0,
                        'account_id' => $plan->account_id??0,
                        'account_name'=> $plan->account,
                        'trial_period' => $plan->trial_period,
                        'setup_fee'=> $plan->setup_fee,
                        'tax_id' => $plan->tax_id !==''?$plan->tax_id:0,
                        'setup_fee_account_id' => $plan->setup_fee_account_id,
                        'setup_fee_account_name'=> $plan->setup_fee_account_name??'',
                        'recurring_price' => $plan->recurring_price??0,
                        'interval' => $plan->interval,
                        'interval_unit'=> $plan->interval_unit,
                        'billing_cycles'=> $plan->billing_cycles,
                        'url'=> ''

                    ]);

                }

            }
        }
    }
}
