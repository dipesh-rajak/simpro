<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unique('sim_id', 'customers_sim_id_unique');
            $table->unique('sim_lead_id', 'customers_sim_lead_id_unique');
            $table->unique('zoho_reference_id', 'customers_zoho_reference_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_sim_id_unique');
            $table->dropIndex('customers_sim_lead_id_unique');
            $table->dropIndex('customers_zoho_reference_id_unique');
        });
    }
}
