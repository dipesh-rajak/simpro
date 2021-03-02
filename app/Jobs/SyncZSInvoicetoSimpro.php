<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use AppHelper;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use zcrmsdk\oauth\ZohoOAuthClient;
use App\Models\ZohoSimproInvoice;


use Illuminate\Support\Facades\DB;

class SyncZSInvoicetoSimpro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $subscription_id;
    protected $eventType;





    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($subscription_id, $subscriptionType)
    {
        $this->subscription_id = $subscription_id;
        $this->eventType = $subscriptionType;
        //Log::debug($this->subscription_id, $this->eventType);
    }
    public function handle()
    {

        
        $config = AppHelper::initAuth();
        // Log::debug($this->eventType);

        $oAuthClient = ZohoOAuthClient::getInstanceWithOutParam();

        //Log::debug($config['currentUserEmail']);
        $accessToken = $oAuthClient->getAccessToken($config['currentUserEmail']);
        // Log::debug($accessToken);
        $client = new Client();
        //Log::debug($client);

        if ($this->eventType == 'subscription_created') {


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://subscriptions.zoho.com/api/v1/subscriptions/" . $this->subscription_id,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer " . $accessToken,
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "postman-token: 2c466d56-5057-e597-e934-94c2dbaaf33d"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                //Log::debug($response);
            }
            $subData = json_decode($response);
            $name = $subData->subscription->name;
            $subscription_number = $subData->subscription->subscription_number;
            $status = $subData->subscription->status;
            $amount = $subData->subscription->amount;
            $billing_mode = $subData->subscription->billing_mode;
            $current_term_starts_at = $subData->subscription->current_term_starts_at;
            $product_id = $subData->subscription->product_id;
            $customer_id = $subData->subscription->customer_id;
           
            $subscriptionsave = ZohoSimproInvoice::updateOrCreate(
                ['id' => $this->subscription_id,],
                ['name' => $name, 'subscription_number' => $subscription_number, 'status' => $status, 'amount' => $amount, 'billing_mode' => $billing_mode, 'current_term_starts_at' => $current_term_starts_at, 'product_id' => $product_id, 'customer_id' => $customer_id, 'product_id', $product_id,]



            );
            if($this->sim_id==$this->siteprimarycontactid){
                $primaryconval=true;
            }
    
            $provider = AppHelper::getSimProProvider();
            $url = "/api/v1.0/companies/{companyID}/jobs/{2}/invoices/";
            $simContcats = $provider->fetchJSON($provider->fetchRequest($url));
            $dbSite =   Contact::updateOrCreate([
                'sim_id' => $simContcats->ID
            ], [
                'sim_id' => $simContcats->ID,
                'title' => $simContcats->Title ?? '',
                'given_name' => $simContcats->GivenName ?? '',
                'family_name' => $simContcats->FamilyName ?? '',
                'email' => $simContcats->Email ?? '',
                'workphone' => $simContcats->WorkPhone ?? '',
                'fax' => $simContcats->Fax ?? '',
                'cellphone' => $simContcats->CellPhone ?? '',
                'altphone' =>  $simContcats->AltPhone ?? '',
                'department' => $simContcats->Department ?? '',
                'position' =>  $simContcats->Position ?? '',
                'primary_contact'=> $primaryconval ?? '',
                'notes' => $simContcats->Notes ?? '',
                'company_number' => ''
    
            ]);
    
            Log::info('Subscription data save successful');
        } elseif ($this->eventType == 'subscription_deleted') {

            $subscriptiondelete = ZohoSimproInvoice::find($this->subscription_id);

            $subscriptiondelete->delete();
            Log::info('Subscription data delete successful');
        }
    }
}
