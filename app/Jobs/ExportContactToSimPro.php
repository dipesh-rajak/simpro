<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Contact;
use App\Models\Site;
use App\Models\Setting;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/***
 * Updates Customer in SimPro
 */
class ExportContactToSimPro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contact;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (!$this->contact) {
                throw new Exception('contact is required.');
            }

            $provider = AppHelper::getSimProProvider();
            $this->createSimContact($provider, $this->contact);
        } catch (Exception  $ex) {
            Log::error(__CLASS__ . ':' . __LINE__ . ': Error occurred.');
            Log::error($ex->getMessage());
            Log::debug($ex->getTraceAsString());
        }
    }

    function createSimContact($provider, $contact)
    {
        try {

        $site = Site::where('zoho_id',$contact->zoho_site_id )
              ->First();

        //Company ID is 0 for single-company builds. If you are querying against a multi-company 
        $siteId =$contact->zoho_site_id;
     
        $url = "/api/v1.0/companies/0/sites/{$site->sim_id}/contacts/";
        $method = 'post';
     

       // Log::debug(print_r($contact, true));

        if ($contact->sim_id) {
            $url = "/api/v1.0/companies/0/sites/{$site->sim_id}/contacts/".$contact->sim_id;
            $method = 'patch';
            Log::debug(sprintf('%s:%s contact sim id %d exists.', __CLASS__, __LINE__, $contact->sim_id));
        }
       // $title = trim(str_replace(".", '', $contact->title));

        $data = [  
            'Title' => $contact->title,
            'GivenName' => $contact->given_name,
            'FamilyName' => $contact->family_name,
            'Email' => $contact->email,
            'WorkPhone' => $contact->workphone,
            'Fax' => $contact->fax,
            'CellPhone' => $contact->cellphone,
            'AltPhone' => $contact->altphone,
            'Department' => $contact->department,
            'Position' => $contact->position,
            'Notes' => $contact->notes
          
        ];


        

        // Log::debug(' title: ' . $title);
        $provider = AppHelper::getSimProProvider();    

        $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
        $response = $provider->fetchResponse($request); //PSR7 Response object.
        $headers = $response->getHeaders(); //Array of headers which were sent in the response.

        $resourceID = 0;

        if (isset($headers['Resource-ID']) && (isset($headers['Resource-ID'][0]))) {
            $resourceID = $headers['Resource-ID'][0]; //Eg. 1999
        }

        if (!$resourceID) {
            throw new Exception(__CLASS__ . __LINE__ . ': Contact could not be created in Simpro.', $headers);
        }

        $json = json_decode((string)$response->getBody()); //Full json representation of the customer we just inserted.

        Log::debug(print_r($json, true));

        if ($resourceID) {
            $contact->sim_id = $resourceID;
            $contact->save();
        }

    } catch (\Exception  $ex) {

        Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
    }
    }
 

    public function middleware()
    {

        return [(new WithoutOverlapping($this->contact->id))->releaseAfter(10)];
    }
}
