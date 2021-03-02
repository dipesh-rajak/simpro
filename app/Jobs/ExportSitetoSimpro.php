<?php

namespace App\Jobs;

use App\Models\Site;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @param Site $site
 */
class ExportSitetoSimpro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $site;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Site $site)
    {

        $this->site = $site;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (!$this->site) {
                throw new Exception(' Site is required to push to SimPro. ');
            }
            $this->pushToSimPro($this->site);
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

    function pushToSimPro(Site $site)
    {

        try {
            if($site->sim_id){
                $url = "/api/v1.0/companies/0/sites/{$site->sim_id}";
                $method = 'patch';
            }else{
                $url = "/api/v1.0/companies/0/sites/";
                $method = 'post';
            }

            $data = [
                'Name' => $site->name,
                'Archived' => $site->archived,
                'PublicNotes' => $site->public_notes,
                'PrivateNotes' => $site->private_notes,
                'BillingContact' => $site->billing_contact,
            ];
            if ($site->address) {
                $data['Address'] = [
                    "Address" => $site->address->address,
                    "City" => $site->address->city,
                    "State" => $site->address->state,
                    "PostalCode" => $site->address->postalCode,
                    "Country" => $site->address->country
                ];
            }

            if ($site->billing_address) {
                $data['BillingAddress'] = [
                    "Address" => $site->billing_address->address,
                    "City" => $site->billing_address->city,
                    "State" => $site->billing_address->state,
                    "PostalCode" => $site->billing_address->postalCode,
                   // "Country" => $site->billing_address->country
                ];
            }

            if ($site->customer) {
                Log::debug('simpro '.$site);
                $data['Customers'] = [
                    "ID" => $site->customer->sim_id,
                ];
            }

            $provider = AppHelper::getSimProProvider();

            $request = $provider->fetchRequest($url, $method, $data); //PSR7 Request object.
            $response = $provider->fetchResponse($request); //PSR7 Response object.
            $headers = $response->getHeaders();
            $resourceID = 0;

            if (isset($headers['Resource-ID']) && (isset($headers['Resource-ID'][0]))) {
                $resourceID = $headers['Resource-ID'][0]; //Eg. 1999
            }


            if (!$resourceID) {
                throw new Exception(__CLASS__ . __LINE__ . ': Customer could not be created in Simpro.', $headers);
            }
    
            $json = json_decode((string)$response->getBody()); //Full json representation of the customer we just inserted.
    
            Log::debug(print_r($json, true));
            Log::debug(print_r($resourceID, true));
    
            if ($resourceID) {
                $site->sim_id = $resourceID;
                $site->save();
              
            }
        } catch (\Exception  $ex) {

            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }
    public function middleware()
    {
        return [(new WithoutOverlapping($this->site->id))->releaseAfter(10)];
    }
}
