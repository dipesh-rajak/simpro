<?php

namespace App\Http\Controllers;

use App\Jobs\SyncSalesperson;
use App\Jobs\SyncSimProSites;
use App\Jobs\UpdateZohoIdInSimpro;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\Site;
use AppHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Expr\Print_;
use Symfony\Component\Console\Helper\Helper;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

class TestController extends Controller
{

    protected $token, $buildURL, $currentPage, $totalPages, $pageSize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

        /*   $this->token = '3b7197a18bb4b2044c62bd119db6711f2df299ef'; //config('services.simpro.api_key');
        $this->buildURL = 'https://element.simprosuite.com/'; //config('services.simpro.client_url'); */
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

       
        /* $provider = AppHelper::getSimProProvider();
        //$this->getSimproCustomFields($provider);

        $employeeGetURL = "/api/v1.0/companies/0/customers/individuals/1414";

        $employeeInfo = $provider->fetchJSON($provider->fetchRequest($employeeGetURL)); //Can combine into one line.

        echo "<pre>";
        //print_r($employeeInfo);

        $customer = Customer::where('sim_id', 1414)->first();

        print_r($customer->toArray());

        print_r($customer->leads->toArray());

        print_r($customer->leads->first()->toArray()); */


        

        // $this->syncSites();
        // $this->getLeadDetail(2479466000000722001);

       // $this->getEmployees(AppHelper::getSimProProvider());
        //SyncSalesperson::dispatchAfterResponse();

       /*  $provider = AppHelper::getSimProProvider();
        $url = "/api/v1.0/companies/0/quotes/115552";
        $json = $provider->fetchJSON($provider->fetchRequest($url));

        echo "<pre>";
        print_r($json);
        echo "</pre>"; */

        //DB::enableQueryLog();
        /* $customers = Customer::whereNotNull('zoho_reference_id')->whereNotNull('sim_id')
        ->where('customer_type', 'customer')->get();

        //dd(DB::getQueryLog());
       // exit();
        foreach($customers as $customer){
            
            UpdateZohoIdInSimpro::dispatch($customer);
        } */

       $this->getAllFields();

      

    }

    private function getLeadDetail($leadId)
    {
        //AppHelper::initAuth();

        //$record = ZCRMRecord::getInstance("Lead", $request->input('ID'));
        //$record = $response->getData();

        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Leads"); // To get module instance

        $response =  $moduleIns->getRecord($leadId); // To get module records
        $record = $response->getData(); // To get response data


        $user = $record->getOwner();



        $data =  $record->getData();
        echo "<pre>";
        print_r($data);
        echo ($user->getName());
        echo "</pre>";
        //echo $record->getFieldValue('Owner');
    }

    private function getEmployees($provider)
    {
        $url = '/api/v1.0/companies/0/employees/';
        $method = 'get';

       // $provider = AppHelper::getSimProProvider();
        $data = $provider->fetchJSON($provider->fetchRequest($url, $method));
        echo "<pre>";
        print_r($data);        
        echo "</pre>";
    }

    private function syncSites()
    {

        $sites = Site::whereNull('zoho_id')->get();

        /*  print_r($sites);
        exit(); */
        foreach ($sites as $site) {
            if ($site->customer_id) {
                $customer = Customer::find($site->customer_id);
                if ($customer) {
                    SyncSimProSites::dispatch($customer, $site->sim_id);
                }
            }
        }
    }


    private function getZohoSubscriptions()
    {
    }

    private function setup_custom_fields($orgProvider = null, $currProvider = null)
    {
        if (!$orgProvider)
            $orgProvider = $this->getProvider(true);


        $module = 'leads';

        $url = '/api/v1.0/companies/0/customers/individuals/1450';
        $method = 'get';

        $request = $orgProvider->fetchRequest($url, $method); //PSR7 Request object.
        $response = $orgProvider->fetchResponse($request);

        //print_r(json_decode($response->getBody()));

        $data = json_decode($response->getBody());

        echo "<pre>";
        print_r($data);
        exit();

        if (!empty($data)) {

            foreach ($data as $site) {
                $id = $site->ID;

                $this->getFieldDetail($module, $id,  $orgProvider);
            }
        }
    }

    private function getFieldDetail($module, $fieldId, $orgProvider)
    {
        $url = "/api/v1.0/companies/0/setup/customFields/" . $module . "/" . $fieldId;
        $method = 'get';

        $request = $orgProvider->fetchRequest($url, $method); //PSR7 Request object.
        $response = $orgProvider->fetchResponse($request);

        $data = json_decode($response->getBody());
        if ($data) {
            echo "<pre>";
            print_r($data);

           // $this->create_custom_field($module, $data);
        }
    }



    private function create_custom_field($module, $data)
    {
        $url = "/api/v1.0/companies/0/setup/customFields/" . $module . "/";

        $method = 'post';
        $provider = $this->getProvider();

        unset($data->ID);

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders();

        print_r($headers);

        print_r(json_decode($response->getBody()));
    }






    public function getAllFields()
    {
        AppHelper::initAuth();

        $client = ZCRMRestClient::getInstance();

        $moduleArr = $client->getAllModules()->getData();
        foreach ($moduleArr as $module) {
            echo "ModuleName:" . $module->getModuleName() . '--'.  $module->getAPIName() . '<br/>';
        }


        $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance("Leads"); // To get module instance
        $response = $moduleIns->getAllFields(); // to get the field
        $fields = $response->getData(); // to get the array of ZCRMField instances
        foreach ($fields as $field) { // each field

            /*  if(!$field->isCustomField()){
                continue;
            } */
            echo $field->getApiName() . ' '; // to get the field api name
            echo $field->getLength() . ' '; // to get the length of the field value
            echo $field->isVisible() . ' '; // to check if the field is visible
            echo $field->getFieldLabel() . ' '; // to get the field label name
            echo $field->getCreatedSource() . ' '; // to get the created source
            echo $field->isMandatory() . ' '; // to check if the field is mandatory
            echo $field->getSequenceNumber() . ' '; // to get fields sequence number
            echo $field->isReadOnly() . ' '; // to check if the field is read only
            echo $field->getDataType() . ' '; // to get the field data type
            echo $field->getId() . ' '; // to get the field id
            echo $field->isCustomField() . ' '; // to check if the field is custom field
            echo $field->isBusinessCardSupported() . ' '; // to check if the field is BusinessCard Supported
            echo $field->getDefaultValue() . ' '; // to get the default value of the field
            $permissions = $field->getFieldLayoutPermissions(); // get field layout permissions.array of permissions list like CREATE,EDIT,VIEW,QUICK_CREATE etc.
            foreach ($permissions as $permission) { // for each permission
                echo $permission . ' ';
            }
            $lookupfield = $field->getLookupField(); // to get the field lookup information
            if ($field->getDataType() == "Lookup") {
                echo $lookupfield->getModule() . ' '; // to get the module name of lookupfield
                echo $lookupfield->getDisplayLabel() . ' '; // to get the display label of the lookup field
                echo $lookupfield->getId() . ' '; // to get the id of the lookup field
            }
            $picklistfieldvalues = $field->getPickListFieldValues(); // to get the pick list values of the field
            foreach ($picklistfieldvalues as $picklistfieldvalue) {
                echo $picklistfieldvalue->getDisplayValue() . ' '; // to get display value of the pick list
                echo $picklistfieldvalue->getSequenceNumber() . ' '; // to get the sequence number of the pick list
                echo $picklistfieldvalue->getActualValue() . ' '; // to get the actual value of the pick list
                echo $picklistfieldvalue->getMaps() . ' ';
            }
            echo $field->isUniqueField() . ' '; // to check if the field is unique
            echo $field->isCaseSensitive() . ' '; // to check if the field is case sensitive
            echo $field->isCurrencyField() . ' '; // to check if the field is currency field
            echo $field->getPrecision() . ' '; // to get the precision of the field
            echo $field->getRoundingOption() . ' '; // to get the rounding option of the field
            echo $field->isFormulaField() . ' '; // to check if the field is a formula field
            if ($field->isFormulaField()) {
                echo $field->getFormulaReturnType() . ' '; // to get the return type of the formula
                echo $field->getFormulaExpression() . ' '; // to get the formula expression
            }
            echo $field->isAutoNumberField() . ' '; // to check if the field is auto numbering
            if ($field->isAutoNumberField()) {
                echo $field->getPrefix() . ' '; // to get the prefix value
                echo $field->getSuffix() . ' '; // to get the suffix value
                echo $field->getStartNumber() . ' '; // to get the start number
            }
            echo $field->getDecimalPlace() . ' '; // to get the decimal place
            echo $field->getJsonType() . ' '; // to get the json type of the field
            echo "<br/>";
        }
    }

    function getSimproCustomFields($provider)
    {
        $url = "/api/v1.0/companies/0/setup/customFields/customers/";

        //$url ="/api/v1.0/companies/0/customers/individuals/4";
        $method = 'get';

        $request = $provider->fetchRequest($url, $method); //PSR7 Request object.
        $response = $provider->fetchResponse($request);
        echo "<pre>";

        $data = json_decode($response->getBody());
        echo "<pre>";
        print_r($data);
        echo "</pre>";

        /* $sourceArray = Arr::where($data->CustomFields, function ($value, $key){
             return $value->CustomField->Name == 'How Customer Was Acquired';

         });
         print_r($sourceArray);

         echo $data->CustomFields[0]->CustomField->Name; */
    }

    function getJobs($provider)
    {
        //$url = "/api/v1.0/companies/0/customerPayments/2797";
        $url = '/api/v1.0/companies/0/setup/customFields/customerContacts/';
        $method = 'get';

        $request = $provider->fetchRequest($url, $method); //PSR7 Request object.
        $response = $provider->fetchResponse($request);
        echo "<pre>";
        print_r(json_decode($response->getBody()));
    }

    function updateCustomerCustomField($simId, $customer, $provider)
    {
        $url = "/api/v1.0/companies/0/customers/$simId/customFields/2";
        $method = "PATCH";
        $data = ['Value' => $customer->source];
        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders();

        print_r($headers);
    }

    function createCustomer($provider, $customer)
    {
        //Company ID is 0 for single-company builds. If you are querying against a multi-company build, adjust the company id accordingly.
        $url = '/api/v1.0/companies/0/customers/individuals/?createSite=true';
        $method = 'post';

        // unset($customer)
        $data = [
            'GivenName' => $customer->given_name, 'FamilyName' => $customer->family_name,
            'Address' => [
                'Address' => $customer->address->address,
                'City' => $customer->address->city,
                'State' => $customer->address->state,
                'PostalCode' => $customer->address->postalCode,
                'Country' => $customer->address->country,

            ],
            'BillingAddress' => [
                'Address' => $customer->billingAddress->address,
                'City' => $customer->billingAddress->city,
                'State' => $customer->billingAddress->state,
                'PostalCode' => $customer->billingAddress->postalCode,
                'Country' => $customer->billingAddress->country,
            ],
            'Email' => $customer->email,

            /*  'EIN' => $customer->ein,
            'Website' => $customer->website, */
            /*  'Phone' => $customer->phone */

            'Sites' => []
        ];

        print '<strong>' . $method . ' ' . $url . '</strong><br /><pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders(); //Array of headers which were sent in the response.

        $location = $headers['Location'][0]; //Eg. /api/v1.0/companies/0/customers/individuals/1999
        $resourceBaseURI = $headers['Resource-Base-URI'][0]; //Eg. /api/v1.0/companies/0/customers/individuals/
        $resourceID = $headers['Resource-ID'][0]; //Eg. 1999
        $json = json_decode((string)$response->getBody()); //Full json representation of the customer we just inserted.

        print 'New Customer:<br /><pre>' . json_encode($json, JSON_PRETTY_PRINT) . '</pre>';
        print 'Customer ID: ' . $resourceID . '<br />';
        print 'Accessed by URL: ' . $location . '<br />';
        print '<br />';

        $customer->sim_id = $resourceID;
        $customer->save();

        return $resourceID;
    }
}
