<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //

    public function index()
    {
        $customers = Customer::latest('updated_at')->paginate(6);
        $customers->withPath(secure_url(route('dashboard')));
        //echo count($customers);

        $setting = Setting::where('slug', 'zoho.org.id')->first();




        return view('dashboard', ['data' => $customers]);
    }
}
