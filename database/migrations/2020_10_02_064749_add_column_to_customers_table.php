<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('source')->after('phone');
            $table->string('title')->after('company_name')->nullable();
            $table->bigInteger('sim_lead_id')->after('sim_id')->nullable();
            $table->string('is_company')->after('phone')->default(false);
        });
        $all_customers = Customer::all();
        $sources = [
            'Adwords Wirelesshomealarms.com.au',
            'SEO Element Website',
            'Telemarketing Campaign',
            'Word of Mouth',
            'Trade Show',
            'Past Customer',
            'HIA Home Show 2017 Form',
            'HIA Home Show 2017 Scanner',
            'The Drop',
            'Cold Call',
            'Alarm.com referral',
            'Response to Email Marketing',
        ];

        foreach ($all_customers as $customer) {
            $index = random_int(0, 11);
            $customer->source = $sources[$index]??'';
            $customer->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('source');
            $table->dropColumn('is_company');
            $table->dropColumn('sim_lead_id');
            $table->dropColumn('title');
        });
    }
}
