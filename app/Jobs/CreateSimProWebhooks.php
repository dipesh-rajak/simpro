<?php

namespace App\Jobs;

use App\Models\SimProWebhooks;
use AppHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateSimProWebhooks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $provider;
    protected $events, $callback_url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {


       


        //$this->callback_url = secure_url(route('webhookSim'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $config = AppHelper::simProAuth();
        $provider = (new \simPRO\RestClient\OAuth2\APIKey())
            ->withBuildURL($config['url'])
            ->withToken($config['apiKey']);

        $this->provider = $provider;

       /*  echo secure_url('/webhooks');
        exit; */


        $url = '/api/v1.0/companies/0/setup/webhooks/';
        $method = 'get';

        $request = $this->provider->fetchRequest($url, $method);
        $response = $this->provider->fetchResponse($request);
        $data = json_decode($response->getBody());



        if (!empty($data)) {
            foreach ($data as $web) {
                //$this->getWebhookDetail($web->ID);
                $this->deleteWebhook($web);
            }


        }

        $events = AppHelper::getSimProEvents();
              
        foreach ($events as $key => $event) {
            $this->createWebhook($key, $event);
        }
    }

    function createWebhook($key, $event)
    {
        $url = '/api/v1.0/companies/0/setup/webhooks/';
        $method = 'post';


        $webhook = [

            "Name" => $key,
            "CallbackURL" => $event['url'],
            "Secret" => AppHelper::getWebhookSecret(),
           
            "Description" => "",
            "Events" => $event['events'],
            "Status" => "Enabled"

        ];

        Log::debug("Now calling URL {$url}" . PHP_EOL);
        $request = $this->provider->fetchRequest($url, $method, $webhook);
        $response = $this->provider->fetchResponse($request);
        $data = json_decode($response->getBody());
        if ($data) {
            $dbHook = SimProWebhooks::updateOrCreate([
                'id' => $data->ID
            ], [
                'id' => $data->ID,
                'name' => $data->Name,
                'callback_url' => $data->CallbackURL,
                'secret' => $data->Secret,
                'email' => $data->Email,
                'description' => $data->Description,
                'events' => json_encode($data->Events),
                'status' => $data->Status,

            ]);

            Log::debug(sprintf(' webhook id: %d created.', $dbHook->id));


        }
    }

    function deleteWebhook($web)
    {
        $url = '/api/v1.0/companies/0/setup/webhooks/' . $web->ID;
        $method = 'delete';
        $request = $this->provider->fetchRequest($url, $method);
        $response = $this->provider->fetchResponse($request);

        if ($response->getStatusCode() <= 204) {
            Log::debug('webhook ' . $web->Name . ' deleted');
        }

        $dbWebhook = SimProWebhooks::find($web->ID);
        if($dbWebhook)
        $dbWebhook->delete();
    }



    function getWebhookDetail($webhookId)
    {
        $url = '/api/v1.0/companies/0/setup/webhooks/' . $webhookId;
        $method = 'get';

        $request = $this->provider->fetchRequest($url, $method);
        $response = $this->provider->fetchResponse($request);
        $data = json_decode($response->getBody());



        if (!empty($data)) {


            $dbHook = SimProWebhooks::updateOrCreate([
                'id' => $webhookId
            ], [
                'id' => $webhookId,
                'name' => $data->Name,
                'callback_url' => $data->CallbackURL,
                'secret' => $data->Secret,
                'email' => $data->Email,
                'description' => $data->Description,
                'events' => json_encode($data->Events),
                'status' => $data->Status,

            ]);

            //$events = $data->events;

        }
    }
}
