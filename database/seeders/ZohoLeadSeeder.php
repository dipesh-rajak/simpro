<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Customer;
use App\Models\ZohoSubscription;
use AppHelper;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuthClient;

class ZohoLeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AppHelper::initAuth();
        $oAuthClient = ZohoOAuthClient::getInstanceWithOutParam();
        $accessToken = $oAuthClient->getAccessToken(config('services.zoho.user_email_id'));



        $customers =  Customer::factory()->count(1)->make(['customer_type' => 'lead']);

        print_r($customers->toArray());
       // $address = Address::factory()->make(['type' => 'shipping']);
       


        foreach ($customers as $customer) {
            try {

                $address = Address::factory()->make(['type' => 'shipping']);
                print_r($address->toArray());
                exit();

                // $billing_address = Address::factory()->make(['type' => 'billing']);

                // $customer->shipping_address = $address;
                // $customer->billing_address = $billing_address;


                if ($customer->customer_type == "customer") {

                    $record = ZCRMRecord::getInstance("Contacts", $customer->zoho_reference_id ?? null);
                    if ($customer->is_company) {
                        //Company_Phone
                        $record->setFieldValue('Company_Phone', $customer->company_number);
                        //Company_Fax
                        $record->setFieldValue('Company_Fax', $customer->fax);
                        //Website
                        $record->setFieldValue('Website', $customer->website);
                        //ABN
                        $record->setFieldValue('ABN', $customer->ein);
                        $lastName = '';
                        $arr = explode(" ", $customer->company_name);
                        if (count($arr) > 1) {
                            $record->setFieldValue('First_Name', $arr[0]);
                            $lastName = $arr[1];
                        } else if (count($arr) == 1) {
                            $lastName = $arr[0];
                        }

                        Log::debug('Last Name: ' . $lastName);
                        $record->setFieldValue('Last_Name', $lastName);
                    }

                    //Type
                    $record->setFieldValue('Type', $customer->is_company ? 'Company' : 'Individual');
                    //How_Customer_Was_Acquired
                    $record->setFieldValue('How_Customer_Was_Acquired', $customer->source ?? '');
                    $record->setFieldValue('Lead_Source', $customer->source ?? '');

                    //Do_Not_Call
                    $record->setFieldValue('Do_Not_Call', $customer->dnd ?? '');
                    //SimPro_Customer_Id
                    $record->setFieldValue('SimPro_Customer_Id', $customer->sim_id);
                    //SimPro_URL
                    $record->setFieldValue('SimPro_URL', $customer->sim_url);

                    $record->setFieldValue('Have_Subscription', $customer->have_subscription);

                    $record->setFieldValue('Mailing_Street', $address->address ?? '');
                    $record->setFieldValue('Mailing_City',  $address->city ?? '');
                    $record->setFieldValue('Mailing_State',  $address->state ?? '');
                    $record->setFieldValue('Mailing_Zip', $$address->postalCode ?? '');
                    $record->setFieldValue('Mailing_Country', $$address->country ?? '');
                } else {

                    $record = ZCRMRecord::getInstance("Leads", $customer->zoho_reference_id ?? null);

                    $record->setFieldValue('Street', $address->address ?? '');
                    $record->setFieldValue('City',  $address->city ?? '');
                    $record->setFieldValue('State',  $address->state ?? '');
                    $record->setFieldValue('Zip_Code', $address->postalCode ?? '');
                    $record->setFieldValue('Country', $address->country ?? '');
                    $record->setFieldValue('Lead_Source', $customer->source ?? '');
                    $record->setFieldValue('Company', $customer->company_name ?? '');
                }

                $record->setFieldValue('Email', $customer->email);

                $record->setFieldValue('First_Name', $customer->given_name);
                $record->setFieldValue('Last_Name', $customer->family_name);

                $record->setFieldValue('Account_Name', $customer->name);
                $record->setFieldValue('Phone', $customer->phone);





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
                    $customer->zoho_reference_id = $zohoId;
                    // $customer->save();
                    Log::debug('Record with email: ' . $customer->email . ' pushed to zoho as ' . $customer->customer_type .  ' Zoho ID: ' . $zohoId);
                } else {
                    Log::error(sprintf('%s:%s error occurred while creating %s, email: %s', __CLASS__, __LINE__,  $customer->customer_type, $customer->email));
                    Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));

                    Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
                }
            } catch (ZCRMException $ex) {


                $code =  $ex->getExceptionCode();
                $details = $ex->getExceptionDetails();

                Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
                Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
            } catch (Exception $ex) {

                echo $ex->getMessage();
            }
        }
    }
}
