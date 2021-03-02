<?php

namespace App\Jobs;

use App\Models\Customer;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

class ConvertZohoLead implements ShouldQueue
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
            if(!$this->customer){
                throw new Exception('Customer is required to convert the lead. ');
            }
            $this->convert($this->customer);
        } catch (ZCRMException  $ex) {
            $code =  $ex->getExceptionCode();
            $details = $ex->getExceptionDetails();

            

            Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
            Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    public function convert($customer)
    {

        if (!$customer->zoho_reference_id) {
            throw new Exception('customer id: ' . $customer->id . ' Zoho lead Id required for conversion');
        }
        Log::debug(sprintf('Converting lead ZOHO LeadID: %s', $customer->zoho_reference_id));

        $record = ZCRMRestClient::getInstance()->getRecordInstance("Leads", $customer->zoho_reference_id); // To get record instance
        $contact = ZCRMRecord::getInstance("contacts", Null); // to get the record of deal in form of ZCRMRecord insatnce

        $details = array(
            "overwrite" => TRUE,
            "notify_lead_owner" => TRUE,
            "notify_new_entity_owner" => TRUE,

        );
        $responseIn = $record->convert($contact, $details); // to convert record       

        if (isset($responseIn['Contacts'])) {
            Log::debug(sprintf('Zoho Lead %d converted to Zoho contact %d', $customer->zoho_reference_id, $responseIn['Contacts']));
            $customer->zoho_reference_id = $responseIn['Contacts'];
            //Log::debug(' detail response', $rdetails);
            $customer->save();
        } else {
            Log::debug(sprintf('%s:%s Zoho Lead Id: %d could not be converted. result: ', __CLASS__, __LINE__, $customer->zoho_reference_id), [$responseIn->getData()]);
        }
    }
}
