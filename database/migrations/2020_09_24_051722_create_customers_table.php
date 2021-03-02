<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sim_id')->nullable();
            $table->bigInteger('zoho_reference_id')->nullable();
         
            $table->bigInteger('zoho_sub_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('given_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->boolean('dnd')->nullable();
            $table->string("customer_type");
            $table->boolean('archived')->default(false);
            $table->string('ein')->nullable();
            $table->string("website")->nullable();
            $table->string("fax")->nullable();
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
        Schema::dropIfExists('customers');
    }
}
