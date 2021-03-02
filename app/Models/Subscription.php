<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{

    use HasFactory;
    
    protected $guarded = ['id'];
    public function plan()
    {
        return $this->belongsTo(Subscription::class);
    }

   public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
