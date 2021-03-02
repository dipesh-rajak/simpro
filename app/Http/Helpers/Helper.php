<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;

class AppHelper
{
    public static function generateAccessTokenFromGrantToken($grantToken)
    {

        Log::debug('grant token: '. $grantToken);

        $oAuthClient = ZohoOAuth::getClientInstance($grantToken);
        
        Log::debug('oauth token: ', [$oAuthClient]);
        $oAuthTokens = $oAuthClient->generateAccessToken($grantToken);
        Log::debug( __CLASS__. ': '. __LINE__. 'token: ', [$oAuthTokens]);
        return $oAuthTokens;
    }


    public static function initAuth($client_id = null, $client_secret = null, $user_email_id = null)
    {
        $settings =         Setting::pluck('value', 'slug');

        if (!$client_id) {
            $client_id = $settings['zoho.app.id'] ?? config('services.zoho.client_id');
        }
        if (!$client_secret) {
            $client_secret = $settings['zoho.app.secret'] ?? config('services.zoho.client_secret');
        }
        if (!$user_email_id)
            $user_email_id = $settings['zoho.app.user_email'] ?? config('services.zoho.user_email_id');
        $accounts_url = $settings['zoho.app.server'] ?? config('services.zoho.api_domain');

        $redirect_url = secure_url(route('redirectZoho'));

        $configuration = array(
            "client_id" => $client_id,
            "client_secret" => $client_secret,
            "redirect_uri" => $redirect_url,
            "currentUserEmail" => $user_email_id,
            "access_type" => 'offline',
            'token_persistence_path' => storage_path('app'),
            'accounts_url' => $accounts_url,
            'sandbox' => false,
            'applicationLogFilePath' => storage_path('logs'),
            'apiBaseUrl' => 'zohoapis.com'
        );

        //\Log::debug(print_r($configuration, true));
        $init =  ZCRMRestClient::initialize($configuration);

        return $configuration;
    }



    public static function simProAuth($debug = false)
    {
        $settings = Setting::pluck('value', 'slug');

        $config = [
            'apiKey' => $settings['simpro.api.key'] ?? config('services.simpro.api_key'),
            'url'   => $settings['simpro.api.url'] ?? config('services.simpro.client_url')
        ];

        if ($debug) {
            $config['apiKey'] = config('services.simpro.api_key');
            $config['url'] = config('services.simpro.client_url');
        }

        return $config;
    }

    public static function getSimProEvents()
    {
        $events = [

            'company' =>

            [
                'url' => secure_url(route('simpro.webhook')),
                'events' => [
                    'company.customer.created',
                    "company.customer.deleted",
                    "company.customer.updated",
                ]

            ],

            'individual' =>

            [
                'url' => secure_url(route('simpro.webhook.individual')),
                'events' => [
                    "individual.customer.created",
                    "individual.customer.updated",
                    "individual.customer.deleted",
                ]

            ],
            'others' =>

            [
                'url' => secure_url(route('simpro.webhook.others')),
                'events' => [

                    /* "job.created",
                    "job.deleted", */
                    "lead.created",
                    "lead.updated",
                    "lead.deleted"
                ]

            ],



        ];

        return $events;
    }

    public static function getWebhookSecret()
    {


        $settings = Setting::where('slug', 'simpro.webhook.secret')->first();
        if (!$settings) {
            $secret = config('services.simpro.webhook_secret');
            if (!$secret) {
                $secret = str_random(8);
            }
            $settings = Setting::create([
                'slug' => 'simpro.webhook.secret',
                'value' => $secret,
            ]);
        }

        return $settings->value;
    }

    public static function getSimProProvider($debug=false)
    {
        $config = self::simProAuth($debug);

        $provider = (new \simPRO\RestClient\OAuth2\APIKey())
            ->withBuildURL($config['url'])
            ->withToken($config['apiKey']);

        return $provider;
    }
}
