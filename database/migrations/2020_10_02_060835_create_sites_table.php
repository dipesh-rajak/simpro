<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sim_id');
            $table->bigInteger('zoho_id')->nullable();
            $table->string('name');
            $table->string('billing_contact')->nullable();
            $table->foreignId('address_id')->constrained();
            $table->foreignId('billingAddress_id')->constrained('addresses');
            $table->text('public_notes')->nullable();
            $table->text('private_notes')->nullable();
            $table->boolean('archived')->default(false);
            $table->foreignId('customer_id')->constrained();
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
        Schema::dropIfExists('sites');
    }
}
