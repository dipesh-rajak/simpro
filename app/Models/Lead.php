<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $guarded = ['id'];

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function getSimUrlAttribute()
    {
        if (!$this->sim_id)
            return '';

        $url =  config('services.simpro.client_url') .   'staff';
        $url .= '/editLead.php?leadID=' . $this->sim_id;

        $url = substr($url, strlen('https://'));

        return $url;
    }
}
