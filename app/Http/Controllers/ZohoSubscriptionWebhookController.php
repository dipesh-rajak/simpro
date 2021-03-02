<?php

namespace App\Http\Controllers;
use Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use SevenShores\Hubspot\Http\Client;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use App\Jobs\SyncZSInvoicetoSimpro;



/**
 * Handle Zoho Webhook Requests
 */
class ZohoSubscriptionWebhookController extends Controller
{
    public function getSubcriptionData(Request $request){
        $data=$request->all();
        $subscription_id=$data['Subscription_id'];
        $subscriptionType=$data['event_type'];
        SyncZSInvoicetoSimpro::dispatch($subscription_id,$subscriptionType);
        //Log::debug($subscriptionType);

    }
    // public function getInvoiceData(Request $request){
    //     //return "hello";
    //     $data=$request->all();
    //     //$data->invoice_items;
    //     Log::debug($data);
    //     // $subscription_id=$data['Subscription_id'];
    //     // $subscriptionType=$data['event_type'];
    //     // ZohoSubscriptionSyncSimpro::dispatch($subscription_id,$subscriptionType);
    //    // 

    // }



    
}
