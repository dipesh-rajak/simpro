<?php

namespace App\Jobs;

use AppHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

class GetZohoCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //

        AppHelper::initAuth();

        $client = ZCRMRestClient::getInstance();

        $moduleArr = $client->getAllModules()->getData();
        foreach ($moduleArr as $module) {
            echo "ModuleName:" . $module->getModuleName() . PHP_EOL;
        }
    }
}
