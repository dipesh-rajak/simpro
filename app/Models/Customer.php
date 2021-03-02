<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];


 
    public function billingAddress()
    {

        return  $this->hasOne('App\Models\Address', 'customer')->where('type', 'address');
    }

    public function address()
    {

        return  $this->hasOne('App\Models\Address', 'customer')->where('type', 'billing');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getUrlAttribute()
    {
        if (!$this->zoho_reference_id)
            return '';

        $setting = Setting::where('slug', 'zoho.org.id')->first();
        $url = '';
        if ($setting) {
            $url = 'https://crm.zoho.com/crm/org' . $setting->value . '/tab';
        }

        if ($this->customer_type == 'lead') {
            $url .= '/Leads/' . $this->zoho_reference_id;
        } else {
            $url .= '/Contacts/' . $this->zoho_reference_id;
        }

        return $url;
    }

    public function getSubUrlAttribute()
    {
        if (!$this->zoho_sub_id)
            return '';

        $url = 'https://subscriptions.zoho.com/app#/customers/' . $this->zoho_sub_id;

        return $url;
    }

    //used by ZOHO
    public function getSimUrlAttribute()
    {
        if (!$this->sim_id)
            return '';

        $url =  config('services.simpro.client_url') .   'staff';

        if ($this->customer_type == 'customer') {
            $url .= '/customer.php?uID=' . $this->sim_id;
        } else {
            $url .= '/editLead.php?leadID=' . $this->sim_lead_id;
        }

        $url = substr($url, strlen('https://'));

        return $url;
    }

    //used in the app
    public function getSimCustomerUrlAttribute()
    {
        if (!$this->sim_id)
            return '';

        $url =  config('services.simpro.client_url') .   'staff';
        $url .= '/customer.php?uID=' . $this->sim_id;
        //$url = substr($url, strlen('https://'));

        return $url;
    }

    public function getNameAttribute()
    {
        return $this->given_name . ' ' . $this->family_name;
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    protected $casts = [
        'is_company' => 'boolean',
        'dnd' => 'boolean',
        'have_subscription' => 'boolean',
        'archived' => 'boolean'
    ];

    public function salesperson(){
        return $this->belongsTo(Salesperson::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    } 
}
