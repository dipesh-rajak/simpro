<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory;
    
    protected $fillable = ['zoho_reference_id','zoho_site_id','company_name','zoho_reference_id','title','given_name','family_name','email','workphone','fax','cellphone','altphone','department','position','altphone','notes'];
    protected $guarded = ['id'];
    public function site() {

        return $this->belongsTo(Site::class);
     }

     protected $casts = [
        'archived' => 'boolean',
    ];

}
  