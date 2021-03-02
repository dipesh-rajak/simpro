<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Contact;
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




class SyncSimproContact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sim_id, $site, $update,$siteprimarycontactid;
 
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Site $site, $sim_id, $update = false,$siteprimarycontactid)
    {
        $this->sim_id = $sim_id;
        $this->site = $site->refresh();
        $this->update = $update;
        $this->siteprimarycontactid=$siteprimarycontactid; 
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->sim_id  || !$this->site) {
            Log::error(__CLASS__ . ': ' . __LINE__ . "Contact simpro ID && Site is required. ");
            return;
        } 
        $primaryconval="";
        if($this->sim_id==$this->siteprimarycontactid){
            $primaryconval=true;
        }

        $provider = AppHelper::getSimProProvider();
        $url = "/api/v1.0/companies/0/contacts/{$this->sim_id}";
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

        $this->createZohoContact($dbSite);


      
    }

    function createZohoContact($dbContact)
    {

     // print_r($dbContact);
    //  print_r($this->site->zoho_id);
    //  print_r($this->site);
  
                
        
        $record = ZCRMRecord::getInstance("Contacts", $dbContact->zoho_reference_id ?? null);
       
        $record->setFieldValue('First_Name', $dbContact->given_name ?? '');
        $record->setFieldValue('Last_Name', $dbContact->family_name ?? '');
        $record->setFieldValue('Position', $dbContact->position ?? '');
        $record->setFieldValue('Department', $dbContact->department ?? '');
        $record->setFieldValue('Notes', $dbContact->notes ?? '');
        $record->setFieldValue('Simpro_ID', $dbContact->sim_id ?? '');
       $record->setFieldValue('Customer_ID', (string) $this->site->zoho_id ?? '');
       // $record->setFieldValue('Simpro_URL', $dbContact->sim_id);
        $record->setFieldValue('Email', $dbContact->email ?? '');
        $record->setFieldValue('Work_Phone', $dbContact->workphone ?? '');
        $record->setFieldValue('Mobile_Phone', $dbContact->cellphone ?? '');
        $record->setFieldValue('Home_Phone', $dbContact->altphone ?? '');
        $record->setFieldValue('Fax', $dbContact->fax ?? '');
        $record->setFieldValue('Primary_Contact', $dbContact->primary_contact ?? '');

      
       

        // if ($dbContact->title != '') {
        //     $title = (strpos($dbContact->title, '.') === false) ? $dbContact->title . '.' : $dbContact->title;
        //     $record->setFieldValue('Salutation', $title == '.' ? '' : $title);
        // }


        $trigger = array(); //triggers to include
        $lar_id = null; //lead assignment rule id

        if (!$dbContact->zoho_reference_id)       
        $responseIns = $record->create($trigger, $lar_id);
        else
        $responseIns = $record->update($trigger, $lar_id);

        $code = $responseIns->getHttpStatusCode(); // To get http response code
      
        $status =  $responseIns->getStatus(); // To get response status

        Log::debug(sprintf(" code: %s, status: %s", $code, $status));
 
        if ($code <= 201) {
            $zohoConatctId =  $record->getEntityId();
            $dbContact->zoho_site_id = $this->site->zoho_id;
            $dbContact->zoho_reference_id = $zohoConatctId;
            $dbContact->save();
            Log::debug('Record with email: ' . $dbContact->email . ' saved as contact');
        } else {
         
            Log::error(sprintf('%s:%s Error message %s', __CLASS__, __LINE__,   $responseIns->getMessage()));
            Log::error(sprintf('%s:%s Details %s', __CLASS__, __LINE__,    json_encode($responseIns->getDetails())));
        }
      
    } 

       
//create site ends
}
