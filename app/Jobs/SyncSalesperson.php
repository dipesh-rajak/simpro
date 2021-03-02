<?php

namespace App\Jobs;

use App\Models\Salesperson;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSalesperson implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            $url = '/api/v1.0/companies/0/employees/';
            $method = 'get';

            $provider = AppHelper::getSimProProvider();
            $data = $provider->fetchJSON($provider->fetchRequest($url, $method));
            if($data && (count($data) > 0)) {
                foreach($data as $staff) {
                    $this->setup_staff($staff);
                }
            }
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
            Log::error($ex->getTraceAsString());
        }
    }

    private function setup_staff($staff){
      $salesperson =   Salesperson::updateOrCreate(['id' => $staff->ID], [
            'id' => $staff->ID,
            'name' => $staff->Name,
            'type' => 'employee'
        ]);
        if($salesperson->wasRecentlyCreated) {
            Log::debug('Salesperson ' . $salesperson->id . ' created');
        }
        else {
            Log::debug('Salesperson ' . $salesperson->id . ' saved');
        }
    }
}
