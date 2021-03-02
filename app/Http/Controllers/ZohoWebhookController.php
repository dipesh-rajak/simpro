<?php

namespace App\Http\Controllers;

use App\Jobs\ExportCustomersToZoho;
use App\Jobs\ExportLeadsToSimPro;
use App\Jobs\ExportCustomerToSimPro;
use App\Jobs\ExportContactToSimPro;
use App\Jobs\ExportLeadToZoho;
use App\Jobs\ExportSitetoSimpro;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Salesperson;
use App\Models\Setting;
use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

/**
 * Handle Zoho Webhook Requests
 */
class ZohoWebhookController extends Controller
{


    public  function index(Request $request)
    {

        Log::debug('zoho webhook received', $request->all());
        $this->validate($request, [
            'OrgID' => 'required|numeric',
            'ID' => 'required|numeric'
        ]);

        $settings = Setting::pluck('value', 'slug');

        $orgId = $request->input('OrgID');
        $leadId = $request->input('ID');

        if ($orgId !== $settings['zoho.org.id'])
            return response()->json(['error' => 'Org ID' . $orgId . ' does not exist or not configured properly'], 404);

        AppHelper::initAuth();

        //$record = ZCRMRecord::getInstance("Lead", $request->input('ID'));
        //$record = $response->getData();

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Leads"); // To get module instance

        $response = $moduleIns->getRecord($leadId); // To get module records
        $record = $response->getData(); // To get response data

        try {

            $data =  $record->getData();
            //Log::debug('Lead data: ' . PHP_EOL .  print_r($data, true));

            $title = $data['Salutation'] ?? '';
            if ($title != '') {
                $title = trim(str_replace('.', ' ', $title));
            }

            $leaduser = $record->getOwner();
            if ($leaduser) {
                $userName = $leaduser->getName();
                Log::debug(sprintf('Lead Id owner: %s', $userName));
                $salesperson = Salesperson::where('name', $userName)->first();
                if ($salesperson) {
                    $salesperson_id = $salesperson->id;
                    Log::debug(sprintf('Lead Id salesperson: %s id: %d', $userName, $salesperson_id));
                } else {
                    Log::debug(sprintf('Lead Id %d salesperson %s not found in staff ', $leadId, $userName));
                }
            } else {
               //Log::debug(sprintf('Lead Id %d owner not found. ', $leadId));
            }
           

            $dbCustomer = Customer::updateOrCreate([
                'zoho_reference_id' => $leadId
            ], [
                'zoho_reference_id' => $leadId,
                'email' => $data['Email'] ?? '',
                'given_name' => $data['First_Name'] ?? '',
                'family_name' => $data['Last_Name'] ?? '',
                'title' => $title,

                'customer_type' => 'lead',
                'company_name' => $data['Company'] ?? '',
                'phone' => $data['Phone'] ?? '',
                'source' => $data['Lead_Source'] ?? '',
                'is_company' => isset($data['Type']) ? ($data['Type'] == 'Company' ? true : false) : false,
                'altphone' => $data['Other_Phone'] ?? '',
                'mobile' => $data['Mobile'] ?? '',
                'description' => $data['Description'] ?? '',
                'salesperson_id' => $salesperson_id ?? null,


            ]);

            if ($dbCustomer) {

                Log::debug('Lead testworking 1');
                /* $dbCustomer->address()->updateOrCreate(['type' => 'address', 'customer' => $dbCustomer->id], [
                    'address' =>  $data['Street'] ?? '',
                    'city' => $data['City'] ?? '',
                    'state' => $data['State'] ?? '',
                    'postalCode' => $data['Zip_Code'] ?? '',
                    'country' => $data['Country'] ?? '',
                    'type' => 'address'
                ]);

                //save billing address
                $dbCustomer->billingAddress()->updateOrCreate(['type' => 'billing', 'customer' => $dbCustomer->id], [
                    'address' =>  $data['Street'] ?? '',
                    'city' => $data['City'] ?? '',
                    'state' => $data['State'] ?? '',
                    'postalCode' => $data['Zip_Code'] ?? '',
                    'country' => $data['Country'] ?? '',
                    'type' => 'billing'
                ]); */

                $address_updated = $billing_updated = false;
                Log::debug('Lead testworking 2');
                $address = Address::where('customer', $dbCustomer->id)->where('type', 'address')->first();
                if (!$address) {
                    $address = Address::create([
                        'address' =>  $data['Street'] ?? '',
                        'city' => $data['City'] ?? '',
                        'state' => $data['State'] ?? '',
                        'postalCode' => $data['Zip_Code'] ?? '',
                        'country' => $data['Country'] ?? '',
                        'type' => 'address',
                        'customer' => $dbCustomer->id
                    ]);
                } else {
                    $address->address = $data['Street'] ?? '';
                    $address->city = $data['City'] ?? '';
                    $address->state = $data['State'] ?? '';
                    $address->postalCode = $data['Zip_Code'] ?? '';
                    $address->country = $data['Country'] ?? '';
                    $address->save();
                }

                if($address->wasRecentlyCreated || $address->wasChanged()){
                    $address_updated = true;
                }

                $billingAddress = Address::where('customer', $dbCustomer->id)->where('type', 'billing')->first();
                if (!$billingAddress) {
                    $billingAddress = Address::create([
                        'address' =>  $data['Street'] ?? '',
                        'city' => $data['City'] ?? '',
                        'state' => $data['State'] ?? '',
                        'postalCode' => $data['Zip_Code'] ?? '',
                        'country' => $data['Country'] ?? '',
                        'type' => 'billing',
                        'customer' => $dbCustomer->id
                    ]);
                } else {
                    $billingAddress->address = $data['Street'] ?? '';
                    $billingAddress->city = $data['City'] ?? '';
                    $billingAddress->state = $data['State'] ?? '';
                    $billingAddress->postalCode = $data['Zip_Code'] ?? '';
                    $billingAddress->country = $data['Country'] ?? '';
                    $billingAddress->save();
                }
                if($billingAddress->wasRecentlyCreated || $billingAddress->wasChanged()){
                    $billing_updated = true;
                }
                Log::debug('Lead testworking 3');
                
                Log::debug($leadId);
                Log::debug($dbCustomer->id);
                $lead =  Lead::updateOrCreate(['zoho_id' => $leadId, 'customer_id' => $dbCustomer->id], [
                    'zoho_id' => $leadId,
                    'name' => $dbCustomer->is_company ? $dbCustomer->company_name : $dbCustomer->name,                   
                    'customer_id' => $dbCustomer->id,
                ]);
                Log::debug('Lead testworking 4');
                //Log::debug(sprintf('%s:%s Lead saved in lead table: ID:%d, Zoho ID: %d ', __CLASS__, __LINE__, $lead->id, $leadId));

                //ExportLeadsToSimPro::dispatch($dbCustomer);

                /* if ($dbCustomer->wasRecentlyCreated) {
                    Log::debug(sprintf('%s:%s %s email:%s , type=%s created from zoho lead id: %d ', __CLASS__, __LINE__, $dbCustomer->customer_type, $dbCustomer->email, $dbCustomer->is_company ? 'Company' : 'Individual',  $leadId));

                    Bus::chain([
                        new  ExportLeadsToSimPro($dbCustomer),
                        new ExportCustomersToZoho($dbCustomer)
                    ])->dispatch();
                } else if ($dbCustomer->wasChanged()) {
                    Log::debug('customer info changed. changes: ', $dbCustomer->getChanges());
                    //ExportLeadsToSimPro::dispatch($dbCustomer);
                    Bus::chain([new  ExportLeadsToSimPro($dbCustomer), new ExportCustomersToZoho($dbCustomer)])->dispatch();
                } else if (!$dbCustomer->sim_lead_id) {
                    Log::debug('customer sim pro lead not found. creating new');
                    //ExportLeadsToSimPro::dispatch($dbCustomer);
                    Bus::chain([new  ExportLeadsToSimPro($dbCustomer), new ExportCustomersToZoho($dbCustomer)])->dispatch();
                } else {
                    Log::debug('No change in the customer information: ' . $dbCustomer->email);
                } */
                 Log::debug('Lead testworking 5');
                 Log::debug($dbCustomer->wasRecentlyCreated);


                if ($dbCustomer->wasRecentlyCreated || $dbCustomer->wasChanged() || $address_updated || $billing_updated) {
                    Log::debug('Lead testworking 6');
                    if ($dbCustomer->wasChanged())
                        Log::debug(__CLASS__ . __LINE__ . ': customer info changed. changes: ', $dbCustomer->getChanges());
                    ExportLeadsToSimPro::dispatch($dbCustomer, $lead);
                } else {
                    Log::debug(__CLASS__ . __LINE__ . ': No change in the customer information: ' . $dbCustomer->email);
                }
            } //if $dbCustomer ends
            else {
                //Log::error(__CLASS__ . __LINE__ . ': No customer found or could not be created.');
            }
        } catch (ZCRMException $ex) {
            //Log::debug($ex->getMessage());
        } catch (Exception $ex) {
            //Log::debug($ex->getMessage());
        }
    }

    public  function contact(Request $request)
    {

        Log::debug('contact zoho webhook received', $request->all());
        $this->validate($request, [
            'OrgID' => 'required|numeric',
            'ID' => 'required|numeric'
        ]);

        $settings = Setting::pluck('value', 'slug');

        $orgId = $request->input('OrgID');
        $contactId = $request->input('ID');

        if ($orgId !== $settings['zoho.org.id'])
            return response()->json(['error' => 'Org ID' . $orgId . ' does not exist or not configured properly'], 404);

        AppHelper::initAuth();

        //$record = ZCRMRecord::getInstance("Lead", $request->input('ID'));
        //$record = $response->getData();

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Contacts"); // To get module instance

        $response = $moduleIns->getRecord($contactId); // To get module records
        $record = $response->getData(); // To get response data

        try {

            $data =  $record->getData();
            Log::debug('Contact data: ' . PHP_EOL .  print_r($data, true));

            
            $title = $data['Salutation'] ?? '';
            if ($title != '') {
                $title = trim(str_replace('.', ' ', $title));
            }
            Log::debug('Contact id: ' . PHP_EOL .  print_r($contactId, true));
            $dbCustomer = Contact::updateOrCreate(
                [
                'zoho_reference_id' => $contactId,
                ], [
                'zoho_site_id' =>  $data['Customer_ID'] ?? '',
                'company_name' => $data['Company'] ?? '',
                'zoho_reference_id' => $contactId,
                'title' => $title,
                'given_name' => $data['First_Name'] ?? '',
                'family_name' => $data['Last_Name'] ?? '',
                'email' => $data['Email'] ?? '',
                'workphone' => $data['Work_Phone'] ?? '',
                'fax' => $data['Fax'] ?? '',
                'cellphone' => $data['Mobile_Phone'] ?? '',
                'altphone' => $data['Alt_Phone'] ?? '',
                'department' => $data['Department'] ?? '',
                'position' => $data['Position'] ?? '',
                'altphone' => $data['Other_Phone'] ?? '',
                'notes' => $data['Notes'] ?? ''        
        
              

            ]);
            Log::debug('inserted Contact data: ' . PHP_EOL .  print_r($dbCustomer, true));
            if ($dbCustomer) {


                

                if (($dbCustomer->wasRecentlyCreated) || ($dbCustomer->wasChanged()) ) {
               

                    ExportContactToSimPro::dispatch($dbCustomer);
                } else if (!$dbCustomer->sim_id) {
                    Log::debug(sprintf('%s:%s email:%s simpro id not found. zoho contact id: %d ', __CLASS__, __LINE__, $dbCustomer->email, $contactId));
                 
                    ExportContactToSimPro::dispatch($dbCustomer);
                } else {
                    Log::debug(__CLASS__ . __LINE__ . ': No change in the contact information: ' . $dbCustomer->email);
                }

                // if ($data['SimPro_ID'] == '') {
                //     ExportCustomersToZoho::dispatch($dbCustomer);
                // }
            } //if $dbCustomer ends
            else {
                Log::emergency(__CLASS__ . __LINE__ . 'Customer not created / saved in db: Zoho customer Id: ' . $contactId);
            }
        } catch (ZCRMException $ex) {

           // Log::error(__CLASS__ . ':' . __LINE__ . ': ' . $ex->getMessage());
        } catch (Exception $ex) {
            //Log::error(__CLASS__ . ':' . __LINE__ . ': ' . $ex->getMessage());
        }
    }



    
    public  function account(Request $request)
    {

        Log::debug('Account zoho webhook received account', $request->all());
        $this->validate($request, [
            'OrgID' => 'required|numeric',
            'ID' => 'required|numeric'
        ]);

        $settings = Setting::pluck('value', 'slug');

        $orgId = $request->input('OrgID');
        $customerId = $request->input('ID');

        if ($orgId !== $settings['zoho.org.id'])
            return response()->json(['error' => 'Org ID' . $orgId . ' does not exist or not configured properly'], 404);

        AppHelper::initAuth();

        //$record = ZCRMRecord::getInstance("Lead", $request->input('ID'));
        //$record = $response->getData();

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Accounts"); // To get module instance

        $response = $moduleIns->getRecord($customerId); // To get module records
        $record = $response->getData(); // To get response data

        try {

            $data =  $record->getData();
            Log::debug('Customer data: ' . PHP_EOL .  print_r($data, true));

            /* $title = $data['Salutation']??'';
            if($title !='') {
                $title = trim(str_replace('.', ' ', $title));
            }

            $leaduser = $record->getOwner();
            if($leaduser) {
                $userName = $leaduser->getName();
                Log::debug(sprintf('Lead Id owner: %s', $userName));
                $salesperson = Salesperson::where('name', $userName)->first();  
                if($salesperson)               {
                    $salesperson_id = $salesperson->id;
                    Log::debug(sprintf('Lead Id salesperson: %s id: %d', $userName, $salesperson_id));
                }
                else {
                    Log::debug(sprintf('Lead Id %d salesperson %s not found in staff ', $leadId, $userName));
                }
                
            }
            else {
                Log::debug(sprintf('Lead Id %d owner not found. ', $leadId));
            } */

            $title = $data['Salutation'] ?? '';
            if ($title != '') {
                $title = trim(str_replace('.', ' ', $title));
            }

            $dbCustomer = Customer::updateOrCreate([
                'zoho_reference_id' => $customerId
            ], [
                'zoho_reference_id' => $customerId,
                'email' => $data['Email'] ?? '',
                'given_name' => $data['First_Name'] ?? '',
                'family_name' => $data['Last_Name'] ?? '',
                'title' => $title,
                'customer_type' => 'customer',
                'company_name' => $data['Company'] ?? '',
                'phone' => $data['Phone'] ?? '',
                //'source' => $data['Lead_Source'] ?? '',
                'is_company' => isset($data['Type']) ? ($data['Type'] == 'Company' ? true : false) : false,
                'altphone' => $data['Other_Phone'] ?? '',
                'mobile' => $data['Mobile'] ?? '',
                'description' => $data['Description'] ?? '',
                'salesperson_id' => $salesperson_id ?? null,
                'source' => $data['How_Customer_Was_Acquired'] ?? ($data['Lead_Source'] ?? null),
                'have_subscription' => $data['Have_Subscription'] ?? false,

            ]);

            if ($dbCustomer) {


                /* $dbCustomer->address()->updateOrCreate(['type' => 'address', 'customer' => $dbCustomer->id], [
                    'address' =>  $data['Other_Street'] ?? '',
                    'city' => $data['Other_City'] ?? '',
                    'state' => $data['Other_State'] ?? '',
                    'postalCode' => $data['Other_Zip'] ?? '',
                    'country' => $data['Other_Country'] ?? '',
                    'type' => 'address'
                ]);

                //save billing address
                $dbCustomer->billingAddress()->updateOrCreate(['type' => 'billing', 'customer' => $dbCustomer->id], [
                    'address' =>  $data['Mailing_Street'] ?? '',
                    'city' => $data['Mailing_City'] ?? '',
                    'state' => $data['Mailing_State'] ?? '',
                    'postalCode' => $data['Mailing_Zip'] ?? '',
                    'country' => $data['Mailing_Country'] ?? '',
                    'type' => 'billing'
                ]); */

                $address_updated = $billing_updated = false;

                $address = Address::where('customer', $dbCustomer->id)->where('type', 'address')->first();
                if (!$address) {
                    $address = Address::create([
                        'address' =>  $data['Shipping_Street'] ?? '',
                        'city' => $data['Shipping_City'] ?? '',
                        'state' => $data['Shipping_State'] ?? '',
                        'postalCode' => $data['Shipping_Code'] ?? '',
                        'country' => $data['Shipping_Country'] ?? '',
                        'type' => 'address',
                        'customer' => $dbCustomer->id
                    ]);
                } else {
                    $address->address = $data['Shipping_Street'] ?? '';
                    $address->city = $data['Shipping_City'] ?? '';
                    $address->state = $data['Shipping_State'] ?? '';
                    $address->postalCode = $data['Shipping_Code'] ?? '';
                    $address->country = $data['Shipping_Country'] ?? '';
                    $address->save();
                }

                if($address->wasRecentlyCreated || $address->wasChanged()){
                    $address_updated = true;
                }

                $billingAddress = Address::where('customer', $dbCustomer->id)->where('type', 'billing')->first();
                if (!$billingAddress) {
                    $billingAddress = Address::create([
                        'address' =>  $data['Billing_Street'] ?? '',
                        'city' => $data['Billing_City'] ?? '',
                        'state' => $data['Billing_State'] ?? '',
                        'postalCode' => $data['Billing_Code'] ?? '',
                        'country' => $data['Billing_Country'] ?? '',
                        'type' => 'billing',
                        'customer' => $dbCustomer->id
                    ]);
                } else {
                    $billingAddress->address = $data['Billing_Street'] ?? '';
                    $billingAddress->city = $data['Mailing_City'] ?? '';
                    $billingAddress->state = $data['Billing_State'] ?? '';
                    $billingAddress->postalCode = $data['Billing_Code'] ?? '';
                    $billingAddress->country = $data['Billing_Country'] ?? '';
                    $billingAddress->save();
                }
                if($billingAddress->wasRecentlyCreated || $billingAddress->wasChanged()){
                    $billing_updated = true;
                }

                $dbCustomer = $dbCustomer->load(['address', 'billingAddress']);

                //ExportLeadsToSimPro::dispatch($dbCustomer);

                if (($dbCustomer->wasRecentlyCreated) || ($dbCustomer->wasChanged()) || ($address_updated || $billing_updated)) {
                    Log::debug(sprintf('%s:%s %s email:%s , type=%s created/updated from zoho customer id: %d ', __CLASS__, __LINE__, $dbCustomer->customer_type, $dbCustomer->email, $dbCustomer->is_company ? 'Company' : 'Individual',  $customerId));

                    ExportCustomerToSimPro::dispatch($dbCustomer);
                } else if (!$dbCustomer->sim_id) {
                    Log::debug(sprintf('%s:%s email:%s simpro id not found. zoho customer id: %d ', __CLASS__, __LINE__, $dbCustomer->email, $customerId));
                    //ExportLeadsToSimPro::dispatch($dbCustomer);
                    /* Bus::chain([new  ExportCustomerToSimPro($dbCustomer), new ExportCustomersToZoho($dbCustomer)])->dispatch(); */
                    ExportCustomerToSimPro::dispatch($dbCustomer);
                } else {
                    Log::debug(__CLASS__ . __LINE__ . ': No change in the customer information: ' . $dbCustomer->email);
                }

                if ($data['SimPro_ID'] == '') {
                    ExportCustomersToZoho::dispatch($dbCustomer);
                }
            } //if $dbCustomer ends
            else {
                Log::emergency(__CLASS__ . __LINE__ . 'Customer not created / saved in db: Zoho customer Id: ' . $customerId);
            }
        } catch (ZCRMException $ex) {

            Log::error(__CLASS__ . ':' . __LINE__ . ': ' . $ex->getMessage());
        } catch (Exception $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': ' . $ex->getMessage());
        }
    }

    public  function site(Request $request)
    {

        Log::debug('Sites zoho webhook received', $request->all());
        $this->validate($request, [
            'OrgID' => 'required|numeric',
            'ID' => 'required|numeric'
        ]);

        $settings = Setting::pluck('value', 'slug');

        $orgId = $request->input('OrgID');
        $siteId = $request->input('ID');

        if ($orgId !== $settings['zoho.org.id'])
            return response()->json(['error' => 'Org ID' . $orgId . ' does not exist or not configured properly'], 404);

        AppHelper::initAuth();

        //$record = ZCRMRecord::getInstance("Lead", $request->input('ID'));
        //$record = $response->getData();

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Sites"); // To get module instance

        $response = $moduleIns->getRecord($siteId); // To get module records
        $record = $response->getData(); // To get response data

        try {

            $data =  $record->getData();
            //Log::debug('Site data: ' . PHP_EOL .  print_r($data, true));

            $dbCustomer = Customer::where('zoho_reference_id', $data['Customer_ID'])->first();
            if (!$dbCustomer) {
                Log::error('no customer found with zoho Id: ' . $data['Customer_ID']);
                return;
            }

            $dbSite = Site::where('zoho_id', $siteId)->first();

            if (!$dbSite) {
                $address = $dbCustomer->address;
                if ($address) {
                    Log::debug(sprintf('%s:%d: found address id: %d', __CLASS__, __LINE__, $address->id));
                    $address->address = $data['Street_Address'] ?? '';
                    $address->city = $data['Suburb'] ?? '';
                    $address->state = $data['State'] ?? '';
                    $address->postalCode = $data['Postcode'] ?? '';
                    $address->country = $data['Country'] ?? '';
                    $address->save();
                    Log::debug(sprintf('%s:%d: address %d updated in database', __CLASS__, __LINE__, $address->id));
                } else {
                    $address = Address::create([
                        'address' => $data['Street_Address'] ?? '',
                        'city' => $data['Suburb'] ?? '',
                        'state' => $data['State'] ?? '',
                        'postalCode' => $data['Postcode'] ?? '',
                        'country' => $data['Country'] ?? '',
                        'customer' => $dbCustomer->id,
                        'type' => 'address'
                    ]);
                }
                $billingAddress = $dbCustomer->billing_address;
                if ($billingAddress) {
                    Log::debug(sprintf('%s:%d: found address id: %d', __CLASS__, __LINE__, $billingAddress->id));
                    $billingAddress->address = $data['Postal_Address'] ?? '';
                    $billingAddress->city = $data['Postal_Suburb'] ?? '';
                    $billingAddress->state = $data['Postal_State'] ?? '';
                    $billingAddress->postalCode = $data['Postal_Postcode'] ?? '';
                    $billingAddress->country = $data['Country'] ?? '';
                    $billingAddress->save();
                    Log::debug(sprintf('%s:%d: billing address %d updated in database', __CLASS__, __LINE__, $billingAddress->id));
                } else {
                    $billingAddress = Address::create([
                        'address' => $data['Postal_Address'] ?? '',
                        'city' => $data['Postal_Suburb'] ?? '',
                        'state' => $data['Postal_State'] ?? '',
                        'postalCode' => $data['Postal_Postcode'] ?? '',
                        'country' => $data['Country'] ?? '',
                        'customer' => $dbCustomer->id,
                        'type' => 'billing'
                    ]);
                }

                $dbSite = Site::create([
                    'zoho_id' => $siteId,
                    'name' => isset($data['Name']) ? $data['Name'] : $dbCustomer->name,
                    'billing_contact' => $dbCustomer->id,
                    'address_id' => $address->id,
                    'billingAddress_id' => $billingAddress->id,
                    'archived' => false,
                    'customer_id' => $dbCustomer->id,
                    'billing_contact' => $data['Postal_Contact'] ?? '',

                ]);
            } else {
                $dbSite = $dbSite->load(['address', 'billing_address']);
                $address = $dbSite->address;
                if ($address) {
                    Log::debug(sprintf('%s:%d: found address id: %d', __CLASS__, __LINE__, $address->id));
                    $address->address = $data['Street_Address'] ?? '';
                    $address->city = $data['Suburb'] ?? '';
                    $address->state = $data['State'] ?? '';
                    $address->postalCode = $data['Postcode'] ?? '';
                    $address->country = $data['Country'] ?? '';
                    $address->save();
                    Log::debug(sprintf('%s:%d: address %d updated in database', __CLASS__, __LINE__, $address->id));
                }
                $billingAddress = $dbSite->billing_address;
                if ($billingAddress) {
                    Log::debug(sprintf('%s:%d: found address id: %d', __CLASS__, __LINE__, $billingAddress->id));
                    $billingAddress->address = $data['Postal_Address'] ?? '';
                    $billingAddress->city = $data['Postal_Suburb'] ?? '';
                    $billingAddress->state = $data['Postal_State'] ?? '';
                    $billingAddress->postalCode = $data['Postal_Postcode'] ?? '';
                    $billingAddress->country = $data['Country'] ?? '';
                    $billingAddress->save();
                    Log::debug(sprintf('%s:%d: billing address %d updated in database', __CLASS__, __LINE__, $billingAddress->id));
                }

                $dbSite->name = $data['Name'] ?? '';
                $dbSite->billing_contact = $data['Postal_Contact'] ?? '';
                $dbSite->save();
            }

            ExportSitetoSimpro::dispatch($dbSite);

            
        } catch (ZCRMException $ex) {

            Log::error(__CLASS__ . ':' . __LINE__ . ': ' . $ex->getMessage());
        } catch (Exception $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': ' . $ex->getMessage());
        }
    }
}
