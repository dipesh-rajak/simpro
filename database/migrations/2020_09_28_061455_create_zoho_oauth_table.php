<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZohoOauthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zoho_oauth', function (Blueprint $table) {
            $table->id();
            $table->string('useridentifier', 100);
            $table->string('accesstoken', 100);
            $table->string('refreshtoken', 100);
            $table->bigInteger('expirytime');

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
        Schema::dropIfExists('zoho_oauth');
    }
}
