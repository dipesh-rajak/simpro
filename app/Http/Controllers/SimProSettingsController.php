<?php

namespace App\Http\Controllers;

use App\Jobs\CreateSimProWebhooks;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SimProSettingsController extends Controller
{
    //

    protected $api_key_slug, $url_slug;
    function __construct()
    {
        $this->middleware('auth');
        $this->api_key_slug = 'simpro.api.key';
        $this->url_slug = 'simpro.url';
    }

    function index(){
        
        $settings = Setting::pluck('value', 'slug');
        return view('simpro.index', ['settings' => $settings]);
    }

    function update(Request $request){

        $this->validate($request, [
            'api_key' => 'required|string',
            'url'=> 'required|url'
        ]);

        try {

            $url = $request->get('url');
            $api_key = $request->get('api_key');
            $provider = (new \simPRO\RestClient\OAuth2\APIKey())
            ->withBuildURL($url)
            ->withToken($api_key);

            $companyURL = '/api/v1.0/companies/';
            Log::debug("Now calling URL {$companyURL}" . PHP_EOL);
            $request = $provider->fetchRequest($companyURL); //PSR7 Request Object
            $response = $provider->fetchResponse($request); //PSR7 Response Object
            $companyArray = json_decode((string)$response->getBody());
            Log::debug("Response is:" . PHP_EOL);
            Log::debug(json_encode($companyArray, JSON_PRETTY_PRINT) . PHP_EOL );

            foreach ($companyArray as $companyObject) {
                Log::debug("Company with ID " . $companyObject->ID . " is named " . $companyObject->Name . PHP_EOL);
            }
    
            //Select which company ID you wish to use.
            $companyID = $companyArray[count($companyArray) - 1]->ID;
            Log::debug("Using company id {$companyID}" . PHP_EOL );
            Log::debug("company id ". ($companyID !== false) . PHP_EOL );
            if($companyID !==false) {
                Setting::updateOrCreate([
                    'slug' => $this->api_key_slug
                ], [
                    'slug' => $this->api_key_slug,
                    'value' => $api_key
                ]);

                Setting::updateOrCreate([
                    'slug' => $this->url_slug
                ], [
                    'slug' => $this->url_slug,
                    'value' => $url
                ]);

                $customFieldsUrl = "/api/v1.0/companies/$companyID/setup/customFields/customers/";
                $response = $provider->fetchJSON($provider->fetchRequest($customFieldsUrl));
                if($response){
                    $arr = Arr::pluck($response, 'ID', 'Name');
                    Setting::updateOrCreate(['slug'=> 'simpro.fields'], ['slug'=> 'simpro.fields',
                    'value' => json_encode($arr)
                    ]);
                }
                
                CreateSimProWebhooks::dispatchAfterResponse();
                return redirect(route('dashboard'))->with('success', 'SimPro API configured successfully');

            }else {
                return redirect()->back()->with('error', 'Some error occurred. Please check the API details and try again');
            }

        }
        catch(Exception $ex) {
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }
}
