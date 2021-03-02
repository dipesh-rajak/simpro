<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertZohoLead;
use App\Jobs\ExportCustomersToZoho;
use App\Jobs\ExportLeadToZoho;
use App\Jobs\SyncSimproContact;
use App\Jobs\SyncSimProSites;
use App\Jobs\SyncSPCustomer;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Salesperson;
use App\Models\Setting;
use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;

//handle SimPro Webhook
class SPWebhookController extends Controller
{ 

    /**
     * Request @request
     * $individual 'company', 'individual
     */
    public function contact(){
        $provider = AppHelper::getSimProProvider();
        $url = "/api/v1.0/companies/0/sites/";
        $simsite = $provider->fetchJSON($provider->fetchRequest($url));
       
        foreach ($simsite as $site) {
            $siteid ="";
            $siteprimarycontactid="";
            $siteid = $site->ID;

            $url = "/api/v1.0/companies/0/sites/$siteid";
            $singlesite = $provider->fetchJSON($provider->fetchRequest($url));
       
         
            if(!empty($singlesite->PrimaryContact->Contact->ID)){
           $siteprimarycontactid =  $singlesite->PrimaryContact->Contact->ID;
            }
         //  Log::debug('singlesite : ' .print_r($siteprimarycontactid));
       
              
                  Log::debug('action : ' . $siteid );
            $url = "/api/v1.0/companies/0/sites/$siteid/contacts/";
            $realtedcontact = $provider->fetchJSON($provider->fetchRequest($url));
           
            foreach ($realtedcontact as $contact) {
              
                $site = Site::where('sim_id', $siteid)->first(); 
                //print_r($site);
             if(!empty($site)){
                Log::info($contact->ID);
                SyncSimproContact::dispatch($site, $contact->ID,$siteprimarycontactid, true);
                
             }
            }
           
        }
        return redirect()->back()->with('success', 'Contact Successfully Synced');
    }
    public function index(Request $request, $individual = 'company')
    {

        try {

 
            Log::debug('simpro webhooks data', $request->all());

            $this->verifySignature($request);

            $data = $request->all();
            $id = $request->get('ID');
            //Log::debug('action : ' . $id);


            /*  $customerID = $data['reference']['customerID'] ?? 0;
            if (!$customerID) {
                throw new Exception('Customer ID ' . $customerID . ' is invalid');
            }

            $companyId = $data['reference']['companyID'] ?? 0; */

            switch ($id) {
                case 'company.customer.created':
                case 'company.customer.updated':

                    $this->handleCustomerUpdated($data);
                    break;
                case 'company.customer.deleted':
                case 'individual.customer.deleted':

                    $this->handleCustomerDeleted($data);
                    break;
                case 'individual.customer.created':
                case 'individual.customer.updated':
                    $this->handleCustomerUpdated($data, true);
                    break;

                case 'lead.created':
                case 'lead.updated':

                    $this->handleLeadCreation($data);
                    break;
                case 'lead.deleted':

                    $this->handleLeadDeletion($data);
                    break;
                case 'quote.created':
                case  'quote.updated':
                    $this->handleQuoteCreation($data);
                    break;
                case  'site.created':                  
                case  'site.updated':
                    $this->handleSites($data);
                    break;
                default:
                    $this->handleDefault($data);
            }
        } catch (Exception $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': Error occurred.');
            Log::error($ex->getMessage());
        }
    }

    private function handleLeadCreation($data)
    {
        $companyId = $data['reference']['companyID'] ?? 0;
        $leadID = $data['reference']['leadID'] ?? 0;

Log::debug(print_r( $companyId));
Log::debug(print_r( $leadID));

          
        $provider = AppHelper::getSimProProvider();
        try {

            $url = '/api/v1.0/companies/0/leads/' . $leadID;
            $detail = $provider->fetchJSON($provider->fetchRequest($url));
            /*  $customer = Customer::updateOrCreate(['sim_lead_id'=> $leadID], [
                'sim_lead_id' => $leadID,
            ]); */

             Log::debug(print_r($detail, true));

            if (!$detail) {
                throw new Exception('detail not available for lead ' . $leadID);
            }


            $sim_customer_id = $detail->Customer->ID;
            $customer = Customer::where('sim_id', $sim_customer_id)->first();
            if (!$customer) {
                SyncSPCustomer::dispatchNow($companyId, $sim_customer_id);
                $customer = Customer::where('sim_id', $sim_customer_id)->first();
                //save lead

            }

            $customer->sim_lead_id = $leadID;
            $customer->save();

            $customFieldsArray = Arr::pluck(json_decode(json_encode($detail->CustomFields), true), 'Value',  'CustomField.Name');

            // Log::debug(print_r($customFieldsArray, true));

            $existing_criteria = ['sim_id' => $leadID, 'customer_id' => $customer->id];
            $zoho_id = null;

            //if Zoho ID is set
            if (isset($customFieldsArray['Zoho ID']) && $customFieldsArray['Zoho ID']) {
                $zoho_id = $customFieldsArray['Zoho ID'];
                $existing_criteria = array_merge($existing_criteria, ['zoho_id' => $zoho_id]);
            }


            $lead = Lead::updateOrCreate($existing_criteria, [
                'sim_id' => $leadID,
                'name' => $detail->LeadName,
                'status' => json_encode($detail->Status),
                'stage' => $detail->Stage ?? '',
                'notes' => $detail->Notes,
                'followupdate' => $detail->FollowUpDate,
                'description' => $detail->Description,
                'salesperson_id' => $detail->Salesperson->ID,


            ]);



            if (!$lead) {
                throw new Exception(__CLASS__ . __LINE__ . ': Lead could not be created in database.');
            }

            Log::debug(sprintf('%s:%s Lead saved in lead table: ID:%d, Simpro ID: %d ', __CLASS__, __LINE__, $lead->id, $leadID));

            /* if ($customer) {
                if (!$customer->leads()->exists($lead))
                    $customer->leads()->save($lead);
            } */

            if ($customer && (!$lead->customer_id)) {
                $lead->customer_id = $customer->id;
                $lead->save();
            }

            ExportLeadToZoho::dispatch($customer, $lead);
        } catch (Exception $ex) {

            Log::error(sprintf('%s:%s error occurred in lead webhook %s', __CLASS__, __LINE__, $ex->getMessage()));
            Log::debug($ex->getTraceAsString());
        }


        // SyncSPCustomer::dispatch($companyId, $customerID, $individual);
    }

    private function handleQuoteCreation($data)
    {
        $companyId = $data['reference']['companyID'] ?? 0;
        $quoteId = $data['reference']['quoteID'] ?? 0;
        if ($quoteId) {
            $provider = AppHelper::getSimProProvider();
            $url = "/api/v1.0/companies/$companyId/quotes/$quoteId";
            Log::debug($url);
            $json = $provider->fetchJSON($provider->fetchRequest($url));
            if ($json) {
                //Log::debug(print_r($json, true));
                if (isset($json->ConvertedFromLead) && isset($json->ConvertedFromLead->ID)) {
                    $leadID = $json->ConvertedFromLead->ID;
                    $customer = Customer::where('sim_lead_id', $leadID)->first();
                    if ($customer) {
                        $customerID = $json->Customer->ID;
                        SyncSPCustomer::dispatch($companyId, $customerID, !$customer->is_company);
                    } else {

                        $lead = Lead::with('customer')->where('sim_id', $leadID)->first();
                        if (!$lead) {
                            Log::error(' No lead record exists for lead ID: ' . $leadID);
                            return;
                        }

                        $customer = $lead->customer;

                        if (!$customer) {
                            Log::error(' No customer record exists for lead ID: ' . $leadID);
                        }
                        SyncSPCustomer::dispatch($compa>>nyId, $customer->sim_id, !$customer->is_company);
                    }
                    ConvertZohoLead::dispatch($customer);
                    ExportCustomersToZoho::dispatch($customer);
                }
            } else {
                Log::error(' No response received from API for quote : ' . $quoteId);
            }
        }
    }

    private function handleLeadDeletion($data)
    {
        $companyId = $data['reference']['companyID'] ?? 0;
        $leadId = $data['reference']['leadID'] ?? 0;
        $dbCustomer = Customer::where('sim_lead_id', $leadId)->first();
        if ($dbCustomer) {

            if ($dbCustomer->zoho_reference_id) {
                $this->handleZohoEntityDelete($dbCustomer);
            }

            $dbCustomer->delete();
            Log::debug('lead ' . $dbCustomer->name . ' with simpro ID: ' . $dbCustomer->sim_lead_id . ' deleted.');
        } else {
            Log::error('lead with simpro id: ' . $leadId .  ' not found.');
        }
    }

    private function handleSites($data)
    {
       

        try {
            $companyId = $data['reference']['companyID'] ?? 0;
            $siteId = $data['reference']['siteID'] ?? 0;
           
         
          
            $site = Site::where('sim_id', $siteId)->first();
            if($site){
                SyncSimProSites::dispatch($site->customer, $siteId, true);
            }else{
                $provider = AppHelper::getSimProProvider();
                $url = "/api/v1.0/companies/0/sites/{$siteId}";           
                $simSite = $provider->fetchJSON($provider->fetchRequest($url));
                $customer = Customer::where('sim_id', $simSite->Customers['0']->ID)->firstOrFail();
                SyncSimProSites::dispatch($customer, $siteId, true); 
            }
         
         
          
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s error occurred in site webhook %s', __CLASS__, __LINE__, $ex->getMessage()));
        }
    }
    private function handleSitesCreate($data)
    {
       

        try {
            $companyId = $data['reference']['companyID'] ?? 0;
            $siteId = $data['reference']['siteID'] ?? 0;
           

            // Log::debug('simSite data: '. PHP_EOL. print_r($simSite, true));
            // Log::debug('simpro id: '. $simSite->Customers['0']->ID);
            // Log::debug('customer : '. $site);
               
         
          
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s error occurred in site webhook %s', __CLASS__, __LINE__, $ex->getMessage()));
        }
    }

    //handle updates
    private function handleCustomerUpdated($data, $individual = false)
    {
        $companyId = $data['reference']['companyID'] ?? 0;
        $customerID = $data['reference']['customerID'] ?? 0;
        SyncSPCustomer::dispatch($companyId, $customerID, $individual)->delay(now()->addSeconds(5));
    }

    //handle delete
    private function handleCustomerDeleted($data)
    {

        $companyId = $data['reference']['companyID'] ?? 0;
        $customerID = $data['reference']['customerID'] ?? 0;

        $dbCustomer = Customer::where('sim_id', $customerID)->first();
        if ($dbCustomer) {

            if ($dbCustomer->zoho_reference_id) {
                $this->handleZohoEntityDelete($dbCustomer);
            }

            $dbCustomer->delete();
            Log::debug('customer ' . $dbCustomer->name . ' with simpro ID: ' . $dbCustomer->sim_id . ' deleted.');
        } else {
            Log::error('customer with simpro id: ' . $customerID .  ' not found.');
        }
    }

    private function handleZohoEntityDelete($dbCustomer)
    {
        try {
            AppHelper::initAuth();
            if ($dbCustomer->customer_type == 'lead') {
                $record = ZCRMRecord::getInstance("Leads", $dbCustomer->zoho_reference_id);
                if ($record) {
                    $record->delete();
                    Log::debug(' Lead with ZohoID: ' . $dbCustomer->zoho_reference_id . ' deleted.');
                }
            } else {
                $record = ZCRMRecord::getInstance("Customers", $dbCustomer->zoho_reference_id);
                if ($record) {
                    $record->delete();
                    Log::debug(' Customer with ZohoID: ' . $dbCustomer->zoho_reference_id . ' deleted.');
                }
            }
        } catch (ZCRMException $ex) {

            $code =  $ex->getExceptionCode();
            $details = $ex->getExceptionDetails();

            Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
            Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
        } catch (Exception $ex) {

            Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $ex->getCode()));
            Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    private function handleDefault($data)
    {
        Log::debug('simpro not handled action: ' . $data['action'] ?? '');
    }


    private function verifySignature(Request $request)
    {
        $header = $request->header('X-Response-Signature');
        //Log::debug('Header signature: ' . $header . PHP_EOL);

        //First try the database setting
        $secretSetting = Setting::where('slug', 'simpro.webhook.secret')->first();
        if (!$secretSetting) { //database setting not found
            //try config
            $secret = config('services.simpro.webhook_secret');
        } else {
            $secret = $secretSetting->value;
        }

        if (!$secret)
            throw new Exception("secret not set in setting.");


        $body = $request->getContent();

        $hash = hash_hmac('sha1', $body, $secret);

        // has check

        if (!hash_equals($header, $hash)) {
            Log::debug('signature does not match ' . PHP_EOL);
            Log::debug('Header signature: ' . $header . PHP_EOL);
            Log::debug('calculated hash: ' . $hash . PHP_EOL);
            throw new \Exception('Hashes do not match');
        }
    }
    public function test(){
        echo 'dbhbf';

    }
}
