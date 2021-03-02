<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function customer() {

       return $this->belongsTo(Customer::class);
    }

    public function billing_address()
    {

        return  $this->belongsTo('App\Models\Address', 'billingAddress_id');
    }

    public function address()
    {
        return  $this->belongsTo('App\Models\Address', 'address_id');
    }

    protected $casts = [
        'archived' => 'boolean',
    ];

}
