<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->bigInteger('product_id');
            $table->string('account_id');
            $table->string('account_name');
            $table->string('setup_fee_account_id');
            $table->string('setup_fee_account_name');
            $table->string('trial_period');
            $table->double('setup_fee');
            $table->double('recurring_price');
            $table->tinyInteger('interval');
            $table->enum('interval_unit', ['months', 'years'])->default('months');
            $table->integer('billing_cycles');
            $table->string('url')->nullable();
            $table->bigInteger('tax_id')->nullable();
           

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
        Schema::dropIfExists('plans');
    }
}
