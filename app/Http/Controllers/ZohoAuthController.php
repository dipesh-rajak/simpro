<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use AppHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use zcrmsdk\crm\crud\ZCRMModule;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\crm\utility\ZohoHTTPConnector;
use zcrmsdk\oauth\utility\ZohoOAuthTokens;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\oauth\ZohoOAuthClient;

class ZohoAuthController extends Controller
{

    protected $scopes = 'ZohoCRM.modules.ALL,aaaserver.profile.ALL,ZohoCRM.users.ALL,ZohoCRM.bulk.all,ZohoSubscriptions.fullaccess.all,ZohoCRM.settings.all,ZohoCRM.org.all';
    protected $redirect_url = '';

    function __construct()
    {
        $this->middleware('auth');
        //parent::__construct();
        $this->redirect_url = secure_url(route('redirectZoho'));
    }

    public function index()
    {

        $settings = Setting::pluck('value', 'slug');
        // print_r($settings->toArray());



        return view('zoho.init', ['settings' => $settings, 'scopes' => $this->scopes, 'redirect_url' => $this->redirect_url]);
    }

    //initiate authorization

    public function authApp(Request $request)
    {



        $this->validate($request, [
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'user_email' => 'required|email',
            'grant_token' => 'exclude_unless:self_client,on|required|string'

        ]);
        try {


            $client_id =  $request->input('client_id');
            $client_secret = $request->input('client_secret');
            $self_client = $request->input('self_client');
            $email = $request->input('user_email')??config('services.zoho.user_email_id');

            session(['zoho.config' => ['client_id' => $client_id, 'client_secret' => $client_secret, 'self' => $self_client, 'user_email' => $email]]);

            if ($request->has('self_client') && $request->has('grant_token')) {

                AppHelper::initAuth($client_id, $client_secret, $email);
                $oauthToken = AppHelper::generateAccessTokenFromGrantToken($request->input('grant_token'));
                if ($oauthToken) {

                    $this->saveZohoConfigs($client_id, $client_secret, $email, config('services.zoho.api_domain'));
                    return redirect(route('dashboard'))->with('success', 'App configured successfully');
                }
            } else {


                $url  = config('services.zoho.api_domain') . "/oauth/v2/auth?response_type=code&access_type=offline&client_id=" . $request->input('client_id') . "&scope=$this->scopes&redirect_uri=" . $this->redirect_url . "&prompt=consent&currentUserEmail=" . $email ;

                // echo $url;

               

                return redirect()->away($url, 302, []);
            }
        } catch (ZCRMException $ex) {
            $code =  $ex->getExceptionCode();
            $details = $ex->getExceptionDetails();

            Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
            Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);
           // echo $code;

           return redirect()->back()->with('error', $ex->getMessage());
        } catch (Exception $ex) {
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
            return redirect()->back()->with('error', $ex->getMessage());
        }
    }


    //callback

    public function code(Request $request)
    {

        Log::debug('data', $request->all());
        try {
            if ($request->has('code')) {
                $gt = $request->input('code');

                $zoho_config = session('zoho.config');
		        Log::debug(print_r(session('zoho.config'), true));

                if ($zoho_config && !empty($zoho_config)) {
                    $client_id = $zoho_config['client_id'];
                    $client_secret = $zoho_config['client_secret'];
                    $email = $zoho_config['user_email'];

                    AppHelper::initAuth($client_id, $client_secret);
                    $oauthToken = AppHelper::generateAccessTokenFromGrantToken($gt);

                    if ($oauthToken) {

                        $this->saveZohoConfigs($client_id, $client_secret,  $email, $request->get('accounts-server'));
                        return redirect(route('dashboard'))->with('success', 'App configured successfully');
                    }
                }
            }
        } catch (ZCRMException $ex) {
            $code =  $ex->getExceptionCode();
            $details = $ex->getExceptionDetails();

            Log::error(sprintf('%s:%s Error Message %s', __CLASS__, __LINE__,   $ex->getMessage()));
            Log::error(sprintf('%s:%s Error Code %s', __CLASS__, __LINE__,   $code));
            Log::error(sprintf('%s:%s Error Details:', __CLASS__, __LINE__),   $details);

//            Log::error(sprintf('%s:%s Error string %s', __CLASS__, __LINE__,  $ex->getTraceAsString()));


            return redirect()->route('initZoho')->with('error', $ex->getMessage());
            //initZoho
        } catch (Exception $ex) {
           // echo "error: " . $ex->getMessage() . '<br/>';
            Log::error(sprintf('%s:%s Error Occurred. %s', __CLASS__, __LINE__,   $ex->getMessage()));
            return redirect()->route('initZoho')->with('error', $ex->getMessage());

            
        }
    }

    private function saveZohoConfigs($client_id, $client_secret, $email, $server)
    {
        Setting::updateOrCreate([
            'slug' => 'zoho.app.id'
        ], [
            'slug' => 'zoho.app.id',
            'value' => $client_id
        ]);

        Setting::updateOrCreate([
            'slug' => 'zoho.app.secret'
        ], [
            'slug' => 'zoho.app.secret',
            'value' => $client_secret
        ]);

        Setting::updateOrCreate([
            'slug' => 'zoho.app.server'
        ], [
            'slug' => 'zoho.app.server',
            'value' => $server
        ]);

        Setting::updateOrCreate([
            'slug' => 'zoho.app.user_email'
        ], [
            'slug' => 'zoho.app.user_email',
            'value' => $email
        ]);

        $client = ZCRMRestClient::getInstance();

        $orgIns = $client->getOrganizationDetails()->getData(); //to get the organization in form of 
        if ($orgIns) {
            Setting::updateOrCreate([
                'slug' => 'zoho.org.id'
            ], [
                'slug' => 'zoho.org.id',
                'value' => $orgIns->getZgid()
            ]);
        }
    }
}
