<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Setting;
use AppHelper;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateZohoIdInSimpro implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {

            if(!$this->customer){
                throw new Exception('customer is required. ');
            }
            $provider = AppHelper::getSimProProvider();
            $this->updateCustomerCustomFields($this->customer->sim_id, $this->customer, $provider);

        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

   



    function updateCustomerCustomFields($simId, $customer, $provider)
    {
        $customFieldSetting = Setting::where('slug', 'simpro.fields')->first();
        if ($customFieldSetting) {
            $customFields = json_decode($customFieldSetting->value);
            foreach ($customFields as $name => $id) {
                $value = '';
                /**
                 * Update only Zoho ID
                 */
                /* if ($name == 'How Customer Was Acquired') {
                    $value = $customer->source;
                } else if ($name == 'Have Subscription?') {
                    $value = $customer->have_subscription ? "Yes" : "No";
                } else  */
                
                if ($name == 'Zoho ID') {
                    $value = $customer->zoho_reference_id ?? '';
                    $this->updateCustomerCustomField($simId, $customer, $provider, $id, $value);
                }

                
            }
        }
    }

    function updateCustomerCustomField($simId, $customer, $provider, $cfID, $value)
    {
        try {
            $url = "/api/v1.0/companies/0/customers/$simId/customFields/" . $cfID;
            $method = "PATCH";
            $data = ['Value' => (string)$value];
            $response = $provider->fetchResponse($provider->fetchRequest($url, $method, $data)); //PSR7 Response object.

            Log::debug(sprintf(' %s custom field id %d=> %s updated. Status: %d ', $customer->email, $cfID, $value, $response->getStatusCode()));
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
        }
    }

}
