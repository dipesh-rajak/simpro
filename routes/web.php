<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SimProSettingsController;
use App\Http\Controllers\SPWebhookController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ZohoAuthController;
use App\Http\Controllers\ZohoWebhookController;
use App\Http\Controllers\ZohoSubscriptionWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

URL::forceScheme('https');

Route::get('/', function () {
    return view('welcome');
});

 
Route::get('/simpro/settings', [SimProSettingsController::class, 'index'])->name('simpro.settings');
Route::post('/simpro/settings', [SimProSettingsController::class, 'update'])->name('simpro.settings.post');

Route::get('/authorize/zoho/init', [ZohoAuthController::class, 'index'])->name('initZoho');
Route::post('/authorize/zoho/submit', [ZohoAuthController::class, 'authApp'])->name('authorizeZoho');
Route::get('/authorize/zoho/redirect', [ZohoAuthController::class, 'code'])->name('redirectZoho');
Route::post('/zoho/push/{customer}', [CustomerController::class, 'pushToZoho'])->name('pushToZoho');
Route::post('/simpro/push/{customer}', [CustomerController::class, 'pushToSimPro'])->name('pushToSimPro');

Route::post('/webhooks/simpro', [SPWebhookController::class, 'index'])->name('simpro.webhook');
Route::post('/webhooks/simpro/individual', [SPWebhookController::class, 'index'])->name('simpro.webhook.individual');
Route::post('/webhooks/simpro/others', [SPWebhookController::class, 'index'])->name('simpro.webhook.others');
Route::post('/webhooks/simpro/sites', [SPWebhookController::class, 'index'])->name('simpro.webhook.sites');
Route::get('/webhooks/simpro/contacts', [SPWebhookController::class, 'contact'])->name('simpro.webhook.contacts');
//Route::get('/webhooks/simpro/test', [SPWebhookController::class, 'test'])->name('simpro.webhook.test');

Route::post('/webhooks/zoho', [ZohoWebhookController::class, 'index'])->name('zoho.webhook');
Route::post('/webhooks/zohoaccount', [ZohoWebhookController::class, 'account'])->name('zoho.webhook.account');
Route::post('/webhooks/zohocontact', [ZohoWebhookController::class, 'contact'])->name('zoho.webhook.contact');
Route::post('/webhooks/zohosite', [ZohoWebhookController::class, 'site'])->name('zoho.webhook.site');

//Zoho Subscription Webhook Api Route

Route::post('/webhooks/zsubscriptionwebhook', [ZohoSubscriptionWebhookController::class, 'getSubcriptionData'])->name('zoho.webhook.subscription');
//Route::post('/webhooks/zsinvoicewebhook', [ZohoSubscriptionWebhookController::class, 'getInvoiceData'])->name('zoho.webhook.invoice');;
 
//Zoho Subscription Webhook Api Route
Route::resource('test', TestController::class);

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


