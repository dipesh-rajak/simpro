<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use AppHelper;
use Exception;
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

class GetZohoSubscriptions implements ShouldQueue
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

        $url = "https://subscriptions.zoho.com.au/api/v1/subscriptions?filter_by=SubscriptionStatus.ACTIVE";

        $response = $client->request("GET", $url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() == 200) {

            $result = json_decode($response->getBody());

            if (($result->message == 'success') && !empty($result->subscriptions)) {


                foreach ($result->subscriptions as $sub) {

                    try {

                        $sub = $this->getSubscriptionDetail($sub->subscription_id, $accessToken);
                        if (!$sub->customer) {
                            throw new Exception('Subscription detail not found.');
                        }

                        $sub_customer = $sub->customer;
                        $dbCustomer = Customer::where('email', $sub_customer->email)->first();

                        if (!$dbCustomer) {
                            throw new Exception('customer could not be created.');
                        }

                        $dbCustomer->zoho_sub_id = $sub_customer->customer_id;
                        $dbCustomer->save();

                        $plan = $sub->plan;
                        $dbPlan = Plan::where('code', $plan->plan_code)->first();

                        $dbSubscription =  Subscription::updateOrCreate(['zoho_id' => $sub->subscription_id], [
                            'zoho_id' => $sub->subscription_id,
                            'customer_id' => $dbCustomer->id,
                            'plan_id' => isset($dbPlan) ? $dbPlan->id : 0,
                            'name' => $sub->name,
                            'status' => $sub->status,
                            'amount' => $sub->amount,
                            'interval' => $sub->interval,
                            'interval_unit' => $sub->interval_unit,
                            'auto_collect' => $sub->auto_collect,
                            'zoho_created_at' => $sub->created_at,
                            'activated_at' => $sub->activated_at,
                            'current_term_starts_at' => $sub->current_term_starts_at,
                            'current_term_ends_at' => $sub->current_term_ends_at,
                            'last_billing_at' => $sub->last_billing_at,
                            'next_billing_at' => $sub->next_billing_at,
                            'expires_at' => $sub->expires_at ?? null,
                            'reference_id' => $sub->reference_id,
                            'salesperson_id' => $sub->salesperson_id,
                            'salesperson_name' => $sub->salesperson_name ?? '',


                        ]);

                        Log::debug(sprintf(' Subscription %s id: %d created/updated', $dbSubscription->name, $dbSubscription->zoho_id));
                    } catch (Exception $ex) {
                        //echo "error: " . $ex->getMessage() . '<br/>';
                        Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
                    }
                } //foreach
            } //result
        } //if response
    } //handle

    function getSubscriptionDetail($subId, $accessToken)
    {
        $client = new Client();

        $url = "https://subscriptions.zoho.com.au/api/v1/subscriptions/" . $subId;

        $response = $client->request("GET", $url, [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        if ($response->getStatusCode() == 200) {

            $result = json_decode($response->getBody());

            if (($result->message == 'success') && !empty($result->subscription)) {

                return $result->subscription;
            } //result
        } //response
    }
}
