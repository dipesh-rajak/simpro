<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimProWebhooks extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table="simpro_webhooks";
}
