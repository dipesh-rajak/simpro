<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoSimproInvoice extends Model
{
    use HasFactory;
    protected $table = 'zoho_simpro_invoices';
    protected $fillable = [
        'id','name', 'subscription_number','status','amount','billing_mode','current_term_starts_at','product_id','customer_id','product_id',
    ];
}
