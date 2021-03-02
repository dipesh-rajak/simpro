<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;

class SyncSimProSites implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sim_id, $customer, $update;
    protected $strBillingContact;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, $sim_id, $update = false)
    {
        $this->sim_id = $sim_id;
        $this->customer = $customer->refresh();
        $this->update = $update;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->sim_id  || !$this->customer) {
            Log::error(__CLASS__ . ': ' . __LINE__ . "Site simpro ID && Customer is required. ");
            return;
        }
        $provider = AppHelper::getSimProProvider();
        $url = "/api/v1.0/companies/0/sites/{$this->sim_id}";
        $simSite = $provider->fetchJSON($provider->fetchRequest($url));

         Log::debug('simSite data: '. PHP_EOL. print_r($simSite, true));
        // Log::debug('simSite subscription :'.   $simSite->CustomFields['1']->Value);

        if ($simSite) {
            $this->strBillingContact = $simSite->BillingContact;
            $subscription=false;

            if(!empty($simSite->CustomFields['1']->Value)){         
          
            $check=$simSite->CustomFields['1']->Value;
            if($check=='Yes'){
                $subscription=true;
            }
        }
            

            $dbSite =   Site::updateOrCreate([
                'sim_id' => $simSite->ID
            ], [
                'sim_id' => $simSite->ID,
                'name' => ($simSite->Name == '') ? $this->customer->name : $simSite->Name,
                'billing_contact' => $this->strBillingContact??$this->customer->name,
                'address_id' => $this->customer->address->id,
                'billingAddress_id' => $this->customer->billingAddress->id,
                'public_notes' => $simSite->PublicNotes ?? '',
                'private_notes' => $simSite->PrivateNotes ?? '',
                'archived' => $simSite->Archived ?? false,
                'customer_id' => $this->customer->id,
                'subscription_service_available' => $subscription
            ]);

            

            if ($this->update) {
                $dbSite = $dbSite->load(['address', 'billing_address']);
                $address = $dbSite->address;
                if ($address) {
                    Log::debug( sprintf('%s:%d: found address id: %d',__CLASS__, __LINE__, $address->id));
                    $address->address = $simSite->Address->Address ?? '';
                    $address->city = $simSite->Address->City ?? '';
                    $address->state = $simSite->Address->State ?? '';
                    $address->postalCode = $simSite->Address->PostalCode ?? '';
                    $address->country = $simSite->Address->Country ?? '';
                    $address->save();
                    Log::debug( sprintf('%s:%d: address %d updated in database',__CLASS__, __LINE__, $address->id));
                }
                $billingAddress = $dbSite->billing_address;
                if ($billingAddress) {
                    Log::debug( sprintf('%s:%d: found address id: %d',__CLASS__, __LINE__, $billingAddress->id));
                    $billingAddress->address = $simSite->BillingAddress->Address ?? '';
                    $billingAddress->city = $simSite->BillingAddress->City ?? '';
                    $billingAddress->state = $simSite->BillingAddress->State ?? '';
                    $billingAddress->postalCode = $simSite->BillingAddress->PostalCode ?? '';
                    $billingAddress->country = $simSite->BillingAddress->Country ?? '';
                    $billingAddress->save();
                    Log::debug( sprintf('%s:%d: billing address %d updated in database',__CLASS__, __LINE__, $billingAddress->id));
                }
            }

            Log::debug('Site saved in database. SimPro ID: ' . $simSite->ID);

          //  $this->createZohoSite($dbSite);




         $site = $dbSite->load(['address', 'billing_address']);

          Log::debug(print_r($site->toArray(), true));
          try {
  
  
              $record = ZCRMRecord::getInstance("Sites", $dbSite->zoho_id ?? null);
              Log::debug("ugfefreigfiuregfiure");
        Log::debug(print_r( $record, true));
              $record->setFieldValue('Name', $dbSite->name);
               $record->setFieldValue('Subscription_Service_Available', $dbSite->subscription_service_available);
               $record->setFieldValue('Postal_Contact', $dbSite->billing_contact);

 
  
               $record->setFieldValue('Postal_Address', $simSite->Address->Address ?? '');
               $record->setFieldValue('Postal_Suburb', $simSite->Address->City ?? '');
               $record->setFieldValue('Postal_State', $simSite->Address->State ?? '');
               $record->setFieldValue('Postal_Postcode', $simSite->Address->PostalCode ?? '');
               $record->setFieldValue('Country', $simSite->Address->Country ?? '');
              
   
               $record->setFieldValue('Street_Address' ,$simSite->BillingAddress->Address ?? '');
               $record->setFieldValue('Suburb', $simSite->BillingAddress->City ?? '');
               $record->setFieldValue('State', $simSite->BillingAddress->State ?? '');
               $record->setFieldValue('Postcode', $simSite->BillingAddress->PostalCode ?? '');
              
  
  
  
  
  
              // $record->setFieldValue('Street_Address', $site->address->address);
              // $record->setFieldValue('Suburb',  $site->address->city);
              // $record->setFieldValue('State', $site->address->state);
              // $record->setFieldValue('Postcode', $site->address->postalCode);
              // $record->setFieldValue('Country', $site->address->country);
             
  
              // $record->setFieldValue('Postal_Address', $site->billing_address ? $site->billing_address->address : '');
              // $record->setFieldValue('Postal_State', $site->billing_address->state);
              // $record->setFieldValue('Postal_Postcode', $site->billing_address->postalCode);
              // $record->setFieldValue('Postal_Suburb', $site->billing_address->city);
              // $record->setFieldValue('Postal_Contact', $site->billing_contact);
              
              $record->setFieldValue('Customer_ID', (string)$this->customer->zoho_reference_id);
  
              $record->setFieldValue('Simpro_Site_ID', $site->sim_id);
  
  
              $trigger = array(); //triggers to include
              $lar_id = ""; //lead assignment rule id
              if (!$site->zoho_id)
                  $responseIns = $record->create($trigger, $lar_id);
              else
                  $responseIns = $record->update($trigger, $lar_id);
  
              $code = $responseIns->getHttpStatusCode(); // To get http response code
              $status =  $responseIns->getStatus(); // To get response status
              Log::debug(sprintf(" code: %s, status: %s", $code, $status));
  
              if ($code <= 201) {
                  $zohoId =  $record->getEntityId();
                  $site->zoho_id = $zohoId;
                  $site->save();
                  if (!$this->customer->is_company) {
                  $record = ZCRMRecord::getInstance("Contacts",$this->customer->zoho_contact_id ?? null);
                  $record->setFieldValue('Customer_ID', (string)$zohoId);
                  $trigger = array(); //triggers to include
                  $lar_id = "";
                  $responseIns = $record->update($trigger, $lar_id);
                  }
                  Log::debug("Site created/updated in zoho. SimPro ID: $site->sim_id, Zoho ID: $zohoId");
              } else {
  
                  Log::error(sprintf('%s:%s error occurred while creating site %s', __CLASS__, __LINE__, $this->customer->email));
                  Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));
  
                  Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
              }
          } catch (ZCRMException $ex) {
  
  
              $code =  $ex->getExceptionCode();
              $details = $ex->getExceptionDetails();
  
              Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
              Log::error(sprintf('%s:%s Error message:', __CLASS__, __LINE__,   $ex->getMessage()));
              Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
  
              if ($code == 'DUPLICATE_DATA') {
                  if (isset($details[0]) && ($details[0]->api_name == 'Simpro_Site_ID')) {
                      $id = $details[0]->ID;
                      $site->zoho_id = $id;
                      $site->save();
                  }
              }
          } catch (Exception $ex) {
              echo "error: " . $ex->getMessage() . '<br/>';
              Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
              Log::error($ex->getTraceAsString());
          }

        }
        else {
            Log::error( sprintf('%s:%d: no data received for site: id: ',__CLASS__, __LINE__, $this->sim_id));
        }
    }

    function createZohoSite($site)
    {


        $site = $site->load(['address', 'billing_address']);

        Log::debug(print_r($site->toArray(), true));
        try {


            $record = ZCRMRecord::getInstance("Sites", $site->zoho_id ?? null);
            Log::debug("ugfefreigfiuregfiure");
      Log::debug(print_r( $record, true));
            $record->setFieldValue('Name', $site->name);
             $record->setFieldValue('Subscription_Service_Available', $site->subscription_service_available);
             $record->setFieldValue('Postal_Contact', $site->billing_contact);


             


             $record->setFieldValue('Postal_Address', $site->address->address);
             $record->setFieldValue('Postal_Suburb',  $site->address->city);
             $record->setFieldValue('Postal_State', $site->address->state);
             $record->setFieldValue('Postal_Postcode', $site->address->postalCode);
             $record->setFieldValue('Country', $site->address->country);
            
 
             $record->setFieldValue('Street_Address', $site->billing_address ? $site->billing_address->address : '');
             $record->setFieldValue('Suburb', $site->billing_address->state);
             $record->setFieldValue('State', $site->billing_address->postalCode);
             $record->setFieldValue('Postcode', $site->billing_address->city);
            





            // $record->setFieldValue('Street_Address', $site->address->address);
            // $record->setFieldValue('Suburb',  $site->address->city);
            // $record->setFieldValue('State', $site->address->state);
            // $record->setFieldValue('Postcode', $site->address->postalCode);
            // $record->setFieldValue('Country', $site->address->country);
           

            // $record->setFieldValue('Postal_Address', $site->billing_address ? $site->billing_address->address : '');
            // $record->setFieldValue('Postal_State', $site->billing_address->state);
            // $record->setFieldValue('Postal_Postcode', $site->billing_address->postalCode);
            // $record->setFieldValue('Postal_Suburb', $site->billing_address->city);
            // $record->setFieldValue('Postal_Contact', $site->billing_contact);
            
            $record->setFieldValue('Customer_ID', (string)$this->customer->zoho_reference_id);

            $record->setFieldValue('Simpro_Site_ID', $site->sim_id);


            $trigger = array(); //triggers to include
            $lar_id = ""; //lead assignment rule id
            if (!$site->zoho_id)
                $responseIns = $record->create($trigger, $lar_id);
            else
                $responseIns = $record->update($trigger, $lar_id);

            $code = $responseIns->getHttpStatusCode(); // To get http response code
            $status =  $responseIns->getStatus(); // To get response status
            Log::debug(sprintf(" code: %s, status: %s", $code, $status));

            if ($code <= 201) {
                $zohoId =  $record->getEntityId();
                $site->zoho_id = $zohoId;
                $site->save();
                if (!$this->customer->is_company) {
                $record = ZCRMRecord::getInstance("Contacts",$this->customer->zoho_contact_id ?? null);
                $record->setFieldValue('Customer_ID', (string)$zohoId);
                $trigger = array(); //triggers to include
                $lar_id = "";
                $responseIns = $record->update($trigger, $lar_id);
                }
                Log::debug("Site created/updated in zoho. SimPro ID: $site->sim_id, Zoho ID: $zohoId");
            } else {

                Log::error(sprintf('%s:%s error occurred while creating site %s', __CLASS__, __LINE__, $this->customer->email));
                Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));

                Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
            }
        } catch (ZCRMException $ex) {


            $code =  $ex->getExceptionCode();
            $details = $ex->getExceptionDetails();

            Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
            Log::error(sprintf('%s:%s Error message:', __CLASS__, __LINE__,   $ex->getMessage()));
            Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);

            if ($code == 'DUPLICATE_DATA') {
                if (isset($details[0]) && ($details[0]->api_name == 'Simpro_Site_ID')) {
                    $id = $details[0]->ID;
                    $site->zoho_id = $id;
                    $site->save();
                }
            }
        } catch (Exception $ex) {
            echo "error: " . $ex->getMessage() . '<br/>';
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
            Log::error($ex->getTraceAsString());
        }
    } //create site ends
}
