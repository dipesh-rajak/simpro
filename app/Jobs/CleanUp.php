<?php

namespace App\Jobs;

use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanUp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sp_leads, $sp_customers, $sp_sites, $zoho_contacts, $zoho_sites, $zoho_leads;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($config = null)
    {
        if ($config) {
            $this->sp_leads = $config['sp_leads'] ?? false;
            $this->sp_customers = $config['sp_customers'] ?? false;
            $this->sp_sites = $config['sp_sites'] ?? false;
            $this->zoho_contacts = $config['zoho_contacts'] ?? false;
            $this->zoho_sites = $config['zoho_sites'] ?? false;
            $this->zoho_leads = $config['zoho_leads'] ?? false;
        } else {
            $this->sp_leads = true;
            $this->sp_customers = true;
            $this->sp_sites = true;
            $this->zoho_contacts = true;
            $this->zoho_sites = true;
            $this->zoho_leads = true;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {


            $provider = AppHelper::getSimProProvider();
            if ($this->sp_leads) {
                $url = '/api/v1.0/companies/0/leads/';
               
                $leads = $provider->fetchJSON($provider->fetchRequest($url));
                foreach ($leads as $lead) {
                    $deleteUrl = '/api/v1.0/companies/0/leads/' . $lead->ID;
                    $inMethod = 'delete';
                    $response = $provider->fetchResponse($provider->fetchRequest($deleteUrl, $inMethod));
                    echo 'lead '. $lead->ID . ' deleted.' . PHP_EOL;
                }
            }

            if ($this->sp_customers) {
                $url = '/api/v1.0/companies/0/customers/companies/';
               
                $customers = $provider->fetchJSON($provider->fetchRequest($url));
                foreach ($customers as $customer) {

                    $deleteUrl = '/api/v1.0/companies/0/customers/companies/' . $customer->ID;
                    $inMethod = 'delete';
                    $response = $provider->fetchResponse($provider->fetchRequest($deleteUrl, $inMethod));
                    echo 'Company customer '. $customer->ID . ' deleted.' . PHP_EOL;
                }
                $url = '/api/v1.0/companies/0/customers/individuals/';
               
                $customers = $provider->fetchJSON($provider->fetchRequest($url));
                foreach ($customers as $customer) {

                    $deleteUrl = '/api/v1.0/companies/0/customers/individuals/' . $customer->ID;
                    $inMethod = 'delete';
                    $response = $provider->fetchResponse($provider->fetchRequest($deleteUrl, $inMethod));
                    echo 'Individual customer '. $customer->ID . ' deleted.' . PHP_EOL;
                }


            }

            ///api/v1.0/companies/{companyID}/sites/

            if ($this->sp_sites) {
                $url = '/api/v1.0/companies/0/sites/';
               
                $sites = $provider->fetchJSON($provider->fetchRequest($url));
                foreach ($sites as $site) {
                    $deleteUrl = '/api/v1.0/companies/0/sites/' . $site->ID;
                    $inMethod = 'delete';
                    $response = $provider->fetchResponse($provider->fetchRequest($deleteUrl, $inMethod));
                    echo 'Site '. $site->ID . ' deleted.' . PHP_EOL;
                }
            }

        } catch (Exception $ex) {

            Log::debug(' Exception in cleanup '. $ex->getMessage());
        }
    }
}
