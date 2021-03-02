<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function customer(){

        return $this->belongsTo('Customer', 'customer');
    }
    public function site(){

        return $this->belongsTo('Site', 'site');
    }
}
