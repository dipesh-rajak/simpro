<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoSubscription extends Model
{
    use HasFactory;

    public function billingAddress()
    {

        return  $this->hasOne('App\Models\Address', 'customer')->where('type', 'billing');
    }

    public function address()
    {
        return  $this->hasOne('App\Models\Address', 'customer')->where('type', 'address');
    }
}
