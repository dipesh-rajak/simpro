<?php

use App\Jobs\CleanUp;
use App\Jobs\CreateSimProWebhooks;
use App\Jobs\ExportCustomersToZoho;
use App\Jobs\GetSimProCustomers;
use App\Jobs\GetZohoCustomers;
use App\Jobs\GetZohoSubscriptions;
use App\Jobs\GetZSCustomers;
use App\Jobs\SyncSalesperson;
use App\Jobs\SyncZohoSubscriptionPlans;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('getSimCustomers', function () {
    GetSimProCustomers::dispatch();
})->purpose('Display an inspiring quote');

Artisan::command('zoho', function () {
   GetZohoCustomers::dispatch();
})->purpose('Get Zoho Customers');

Artisan::command('export-zoho', function () {
    ExportCustomersToZoho::dispatch();
 })->purpose('Export to Zoho');

 Artisan::command('zoho-subs', function () {
    GetZohoSubscriptions::dispatch();
 })->purpose('View Subscriptions');

 Artisan::command('zoho-plans', function () {
   SyncZohoSubscriptionPlans::dispatch();
})->purpose('Sync Subscription Plans');

 Artisan::command('zs-customers', function () {
   GetZSCustomers::dispatch();
})->purpose('View Subscription Customers');

Artisan::command('sim-webhooks', function () {
   CreateSimProWebhooks::dispatch();
})->purpose('Display an inspiring quote');
Artisan::command('sales', function () {
   SyncSalesperson::dispatch();
})->purpose('Gets SimPro Salesperson');

Artisan::command('cleanup', function () {
   CleanUp::dispatch();
})->purpose('View Subscription Customers');