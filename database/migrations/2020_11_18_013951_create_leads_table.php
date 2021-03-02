<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sim_id')->nullable()->unique();
            $table->bigInteger('zoho_id')->nullable()->unique();
            $table->string('name');
            $table->string('stage');
            $table->json('status')->nullable();
            $table->text('notes')->nullable();
            $table->date('followupdate')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('site_id')->nullable()->constrained();
            $table->foreignId('salesperson_id')->nullable()->constrained();
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
        Schema::dropIfExists('leads');
    }
}
