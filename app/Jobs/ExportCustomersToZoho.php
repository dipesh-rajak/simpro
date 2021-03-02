<?php

namespace App\Jobs;

use App\Models\Customer;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\Helper;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;




class ExportCustomersToZoho implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;
    protected $customer_type_changed;
    protected $company_type_changed;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer = null, $customer_type_changed = false, $company_type_changed = false)
    {
        $this->customer = $customer;
        $this->customer_type_changed = $customer_type_changed;
        $this->company_type_changed = $company_type_changed;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        AppHelper::initAuth();

        $customers = [];
        if (!$this->customer) {
            $customers = Customer::with('billingAddress')->with('address')
                ->where('email', '!=', '')
                ->where('family_name', '!=', '')
                ->whereNull('zoho_reference_id')
                ->limit(4)->get();
        } else {
            $customers = [$this->customer];
        }
        foreach ($customers as $customer) {

            try {

                $customer = $customer->refresh();

                if ($customer->customer_type == 'lead') {
                    Log::debug('handled by lead table. exit');
                    return;
                }

                /*  if ($this->customer_type_changed && ($customer->customer_type == 'customer')) {

                    $this->convert($customer);
                    $customer = $customer->refresh();
                } */

                $this->pushToZoho($customer);
                UpdateZohoIdInSimpro::dispatch($customer);
            } catch (ZCRMException $ex) {

                //echo "error: " . $ex->getMessage() . '<br/>';
                // Log::error($ex->getCode());
                //Log::error($ex->getTraceAsString());

                $code =  $ex->getExceptionCode();
                $details = $ex->getExceptionDetails();


                Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
                Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
                //Log::debug($ex->__toString());

                if (($code == 'DUPLICATE_DATA') && isset($details['id'])) {

                    $customer->zoho_reference_id = $details['id'];
                    $customer->save();
                    Log::debug('Record with email: ' . $customer->email . ' saved as ' . $customer->customer_type);
                }
            } catch (Exception $ex) {

                echo "error: " . $ex->getMessage() . '<br/>';
                Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
            }
        } //foreach ends
    } //handle


    public function convert($customer)
    {

        if (!$customer->zoho_reference_id) {
            throw new Exception('customer id: ' . $customer->id . ' Zoho lead Id required for conversion');
        }
        Log::debug(sprintf('Converting lead to customer. LeadID: %s', $customer->zoho_reference_id));

        $record = ZCRMRestClient::getInstance()->getRecordInstance("Leads", $customer->zoho_reference_id); // To get record instance
        $contact = ZCRMRecord::getInstance("contacts", Null); // to get the record of deal in form of ZCRMRecord insatnce

        $details = array(
            "overwrite" => TRUE,
            "notify_lead_owner" => TRUE,
            "notify_new_entity_owner" => TRUE,

        );
        $responseIn = $record->convert($contact, $details); // to convert record
        // Log::debug(print_r($responseIn, true));

        if (isset($responseIn['Contacts'])) {
            Log::debug(sprintf('Zoho Lead %d converted to Zoho contact %d', $customer->zoho_reference_id, $responseIn['Contacts']));
            $customer->zoho_reference_id = $responseIn['Contacts'];

            //Log::debug(' detail response', $rdetails);
            $customer->save();
        }
    }



    function pushToZoho(Customer $customer)
    {
        echo 'Customer:' . $customer->email . ' type: ' . $customer->customer_type . PHP_EOL;
        Log::debug(__CLASS__ . ':' . __LINE__ . ': Customer:' . $customer->email . ' type: ' . $customer->customer_type);

        /* Log::debug(__CLASS__ . ':' . __LINE__ . ': Customer details: ' . PHP_EOL . print_r($customer->toArray(), true));
 */

        if ($customer->customer_type == "customer") {



            $record = ZCRMRecord::getInstance("Accounts", $customer->zoho_reference_id ?? null);
            if ($customer->is_company) {

                //    $record->setFieldValue('Primary_Phone', $customer->company_number);                
                //  $record->setFieldValue('Company_Fax', $customer->fax);               
                //  

                $record->setFieldValue('ABN', $customer->ein);
                $lastName = '';
                $record->setFieldValue('Account_Name', $customer->company_name);
                $arr = explode(" ", $customer->company_name);
                if (count($arr) > 1) {
                    $record->setFieldValue('First_Name', $arr[0]);
                    $lastName = $arr[1];
                } else if (count($arr) == 1) {
                    $lastName = $arr[0];
                }

                if ($lastName == '') {
                    $lastName = $customer->family_name;
                }
                $record->setFieldValue('Last_Name', $lastName);
                $record->setFieldValue('Company_Phone', $customer->phone ?? '');
                $record->setFieldValue('Company_Fax', $customer->fax ?? '');
                $record->setFieldValue('Website', $customer->website);
            }

            if (!$customer->is_company) {
                $record->setFieldValue('First_Name', $customer->given_name);
                $record->setFieldValue('Last_Name', $customer->family_name);
                $record->setFieldValue('Primary_Phone', $customer->phone);
                $record->setFieldValue('Mobile_Phone', $customer->cellphone);
                $record->setFieldValue('Account_Name', $customer->name);
                $record->setFieldValue('Title', $customer->title ?? '');

            }

            //Type
            $record->setFieldValue('Type', $customer->is_company ? 'Company' : 'Individual');
            //How_Customer_Was_Acquired
            $record->setFieldValue('How_Customer_Was_Acquired', $customer->source ?? '');
            $record->setFieldValue('Lead_Source', $customer->source ?? '');

            //Do_Not_Call
            $record->setFieldValue('Do_Not_Call', $customer->dnd ?? '');
            //SimPro_Customer_Id
            $record->setFieldValue('SimPro_ID', $customer->sim_id);
            //SimPro_URL
            $record->setFieldValue('SimPro_URL', $customer->sim_url);

            $record->setFieldValue('Have_Subscription', $customer->have_subscription);
            $record->setFieldValue('Email', $customer->email);
            $record->setFieldValue('Alt_Phone', $customer->altphone);


            $record->setFieldValue('Shipping_Street', $customer->address->address ?? '');
            $record->setFieldValue('Shipping_City', $customer->address->city ?? '');
            $record->setFieldValue('Shipping_State', $customer->address->state ?? '');
            $record->setFieldValue('Shipping_Code', $customer->address->postalCode ?? '');
            $record->setFieldValue('Shipping_Country', $customer->address->country ?? '');
            $record->setFieldValue('Billing_Street', $customer->billingAddress->address ?? '');
            $record->setFieldValue('Billing_City', $customer->billingAddress->city ?? '');
            $record->setFieldValue('Billing_State', $customer->billingAddress->state ?? '');
            $record->setFieldValue('Billing_Code', $customer->billingAddress->postalCode ?? '');
            $record->setFieldValue('Billing_Country', $customer->billingAddress->country ?? '');
        } else {
            //if lead
            $record = ZCRMRecord::getInstance("Leads", $customer->zoho_reference_id ?? null);
            $record->setFieldValue('Street', $customer->address->address ?? '');
            $record->setFieldValue('City', $customer->address->city ?? '');
            $record->setFieldValue('State', $customer->address->state ?? '');
            $record->setFieldValue('Zip_Code', $customer->address->postalCode ?? '');
            $record->setFieldValue('Country', $customer->address->country ?? '');
            $record->setFieldValue('Lead_Source', $customer->source ?? '');
            $record->setFieldValue('Company', $customer->company_name ?? '');
            $record->setFieldValue('SimPro_ID', $customer->sim_lead_id ?? '');
            $record->setFieldValue('SimPro_URL', $customer->sim_url ?? '');
        }

        if ($customer->title != '') {
            $title = (strpos($customer->title, '.') === false) ? $customer->title . '.' : $customer->title;
            $record->setFieldValue('Salutation', $title == '.' ? '' : $title);
        }


        $trigger = array(); //triggers to include
        $lar_id = ""; //lead assignment rule id
        if (!$customer->zoho_reference_id)
            $responseIns = $record->create($trigger, $lar_id);
        else
            $responseIns = $record->update($trigger, $lar_id);

        $code = $responseIns->getHttpStatusCode(); // To get http response code
        $status =  $responseIns->getStatus(); // To get response status

        Log::debug(sprintf(" code: %s, status: %s", $code, $status));

        if ($code <= 201) {
            $zohoId =  $record->getEntityId();

            Log::debug('Record with email: ' . $customer->email . ' saved as ' . $customer->customer_type);
        } else {
            Log::error(sprintf('%s:%s error occurred while creating %s, email: %s', __CLASS__, __LINE__,  $customer->customer_type, $customer->email));
            Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));
            Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
        }

        if (!$customer->is_company) {

            $record = ZCRMRecord::getInstance("Contacts", $customer->zoho_contact_id ?? null);

            $record->setFieldValue('First_Name', $customer->given_name ?? '');
            $record->setFieldValue('Last_Name', $customer->family_name ?? '');
            $record->setFieldValue('Position', $customer->position ?? '');
            $record->setFieldValue('Department', $customer->department ?? '');
            $record->setFieldValue('Notes', $customer->description ?? '');
            $record->setFieldValue('Simpro_ID', $customer->sim_id ?? '');
            $record->setFieldValue('Simpro_URL', $customer->sim_url ?? '');
            $record->setFieldValue('Email', $customer->email ?? '');
            $record->setFieldValue('Work_Phone', $customer->workphone ?? '');
            $record->setFieldValue('Mobile_Phone', $customer->mobile ?? '');
            $record->setFieldValue('Home_Phone', $customer->altphone ?? '');
            $record->setFieldValue('Fax', $customer->fax ?? '');

            if ($customer->title != '') {
                $title = (strpos($customer->title, '.') === false) ? $customer->title . '.' : $customer->title;
                $record->setFieldValue('Salutation', $title == '.' ? '' : $title);
            }
          
            $trigger = array(); //triggers to include
            $lar_id = null; //lead assignment rule id

            if (!$customer->zoho_contact_id)
                $responseIns = $record->create($trigger, $lar_id);
            else
                $responseIns = $record->update($trigger, $lar_id);

            $code = $responseIns->getHttpStatusCode(); // To get http response code
            $status =  $responseIns->getStatus(); // To get response status

            Log::debug(sprintf(" code: %s, status: %s", $code, $status));

            if ($code <= 201) {
                $zohoConatctId =  $record->getEntityId();
                $customer->zoho_contact_id = $zohoConatctId;
                Log::debug('Record with email: ' . $customer->email . ' saved as ' . $customer->customer_type);
            } else {
                Log::error(sprintf('%s:%s error occurred while creating %s, email: %s', __CLASS__, __LINE__,  $customer->customer_type, $customer->email));
                Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));
                Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
            }
        }
        $customer->zoho_reference_id = $zohoId;

        $customer->save();
    }

    public function middleware()
    {
        if ($this->customer) {
            return [(new WithoutOverlapping($this->customer->id))->releaseAfter(10)];
        }
    }
}
