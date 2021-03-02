<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('zoho_id');            
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->double('amount')->nullable();
            $table->tinyInteger('interval');
            $table->enum('interval_unit', ['months', 'years'])->default('months');
            $table->boolean('auto_collect')->default(true);
            $table->string('reference_id')->nullable();
            $table->string('salesperson_id')->nullable();
            $table->string('salesperson_name')->nullable();
            $table->string('child_invoice_id')->nullable();
            $table->dateTimeTz('zoho_created_at');
            $table->dateTimeTz('activated_at');
            $table->date('current_term_starts_at');
            $table->date('current_term_ends_at');
            $table->date('last_billing_at');
            $table->date('next_billing_at');
            $table->date('expires_at');

            $table->bigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();
            
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
        Schema::dropIfExists('subscriptions');
    }
}
