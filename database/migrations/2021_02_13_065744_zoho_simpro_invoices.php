<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ZohoSimproInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zoho_simpro_invoices', function (Blueprint $table) {
            $table->id();


            $table->string('name');
            $table->string('subscription_number');
            $table->string('status')->nullable();
            $table->string('amount')->nullable();
            $table->string('billing_mode')->nullable();
            $table->string('current_term_starts_at')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('product_id')->nullable();
            $table->timestamps();
        });
        Schema::table('zoho_simpro_invoices', function (Blueprint $table) {
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zoho_simpro_invoices');
    }
}
