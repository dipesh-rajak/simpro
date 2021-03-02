<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sim_id')->nullable();
            $table->bigInteger('zoho_reference_id')->nullable();
            $table->bigInteger('zoho_sub_id')->nullable();
            $table->bigInteger('zoho_site_id')->nullable();
            $table->boolean('archived')->default(false);
            $table->string('company_name')->nullable();
            $table->string('title')->nullable();
            $table->string('given_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('email')->nullable();
            $table->string('workphone')->nullable();
            $table->string('fax')->nullable();    
            $table->string('cellphone')->nullable();
            $table->string('altphone')->nullable();                 
            $table->string('department')->nullable();
            $table->string("position")->nullable();
            $table->string("notes")->nullable();
            
            $table->string("primary_contact")->nullable();
            $table->string('company_number')->nullable();
            $table->softDeletesTz();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
}
