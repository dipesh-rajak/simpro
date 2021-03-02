<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Lead;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;

class ExportLeadToZoho implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer, $lead;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, Lead $lead = null)
    {
        $this->customer = $customer;
        $this->lead = $lead;
    }



    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {


            if ($this->customer) {
                if (!$this->lead) {
                    if (count($this->customer->leads) == 1) {
                        $this->lead = $this->customer->leads->first();
                    } else if (count($this->customer->leads) == 0) {

                        throw new Exception(__CLASS__ . __LINE__ . ': No associated leads found.');
                    } else {
                        throw new Exception(__CLASS__ . ':' . __LINE__ . ' multiple associated leads found.');
                    }
                }
                $this->pushToZoho($this->customer, $this->lead);
            }
        } catch (ZCRMException  $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': Error occurred.');
            Log::error($ex->getMessage());
            Log::error(print_r($ex->getExceptionDetails(), true));
        } catch (Exception  $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': Error occurred.');
            Log::error($ex->getMessage());
        }
    }

    function pushToZoho(Customer $customer, Lead $lead)
    {

        Log::debug(sprintf('%s:%s lead sim id:%d zoho id: %d', __CLASS__, __LINE__, $lead->sim_id, $lead->zoho_id));
        Log::debug(__CLASS__ . ' ' . __LINE__ . ' customer ', [$customer]);
        Log::debug(__CLASS__ . ' ' . __LINE__ . ' lead ', [$lead]);

        $record = ZCRMRecord::getInstance("Leads", $lead->zoho_id ?? null);
        $record->setFieldValue('Company', $customer->company_name ?? '');
        $record->setFieldValue('Street', $customer->address->address ?? '');
        $record->setFieldValue('City', $customer->address->city ?? '');
        $record->setFieldValue('State', $customer->address->state ?? '');
        $record->setFieldValue('Zip_Code', $customer->address->postalCode ?? '');
        $record->setFieldValue('Country', $customer->address->country ?? '');
        $record->setFieldValue('Lead_Source', $customer->source ?? '');

        $record->setFieldValue('SimPro_ID', $lead->sim_id ?? '');
        $record->setFieldValue('SimPro_URL', $customer->sim_url ?? '');

        $record->setFieldValue('Email', $customer->email);
        $record->setFieldValue('Phone', $customer->phone);

        if ($customer->title != '') {
            $title = (strpos($customer->title, '.') === false) ? $customer->title . '.' : $customer->title;
            $record->setFieldValue('Salutation', $title);
        }

        $record->setFieldValue('Description', $customer->description ?? '');
        $record->setFieldValue('Mobile', $customer->mobile ?? '');

        /* if($customer->is_company){

            $record->setFieldValue('Last_Name', empty($customer->family_name)?$customer->company_name: $customer->family_name);
        } */
        if ($lead->name && ($lead->name != '')) {

            $first_name = $lead->name;
            $last_name = '-';
            $arr = explode(' ', $lead->name);
            if (count($arr) == 1) {
                $first_name = $lead->name;
            } else if (count($arr) == 2) {
                $first_name = $arr[0];
                $arr = array_except($arr, 0);
                $last_name = implode(' ', $arr);
            }
            $record->setFieldValue('First_Name', $first_name);
            $record->setFieldValue('Last_Name', $last_name);
        } else {
            $record->setFieldValue('First_Name', $customer->given_name);
            $record->setFieldValue('Last_Name', $customer->family_name);
        }


        $trigger = array(); //triggers to include
        $lar_id = ""; //lead assignment rule id
        if (!$lead->zoho_id)
            $responseIns = $record->create($trigger, $lar_id);
        else
            $responseIns = $record->update($trigger, $lar_id);

        $code = $responseIns->getHttpStatusCode(); // To get http response code
        $status =  $responseIns->getStatus(); // To get response status

        Log::debug(sprintf(" response code: %s, status: %s", $code, $status));

        if ($code <= 201) {
            $zohoId =  $record->getEntityId();

            $lead->zoho_id = $zohoId;
            $lead->save();
            Log::debug('Simpro Lead with email: ' . $customer->email . ' saved ' . $zohoId);
            Log::debug(sprintf('%s:%s Lead saved ID:%d, Simpro ID: %d, Zoho Id:%d ', __CLASS__, __LINE__, $lead->id, $lead->sim_id, $lead->zoho_id));

            if ($customer->customer_type == 'lead') {
                $customer->zoho_reference_id = $zohoId;

                if ($this->lead) {
                    $customer->sim_lead_id = $this->lead->sim_id;
                }
                $customer->save();
            }
        } else {
            Log::error(sprintf('%s:%s error occurred while creating lead in Zoho: Simpro Lead ID', __CLASS__, __LINE__, $this->lead->sim_id));
            Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));
            Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
        }
    }

    public function middleware()
    {
        return [(new WithoutOverlapping($this->customer->id))->releaseAfter(10)];
    }
}
