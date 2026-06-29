<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompositeUniqueIndexesForMultitenant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // drivers: employeeid and ic unique per company
        Schema::table('drivers', function (Blueprint $table) {
            $table->unique(['employeeid', 'company_id'], 'drivers_employeeid_company_unique');
            $table->unique(['ic', 'company_id'], 'drivers_ic_company_unique');
        });

        // products: code unique per company
        Schema::table('products', function (Blueprint $table) {
            $table->unique(['code', 'company_id'], 'products_code_company_unique');
        });

        // lorrys: lorryno unique per company
        Schema::table('lorrys', function (Blueprint $table) {
            $table->unique(['lorryno', 'company_id'], 'lorrys_lorryno_company_unique');
        });

        // kelindans: employeeid and ic unique per company
        Schema::table('kelindans', function (Blueprint $table) {
            $table->unique(['employeeid', 'company_id'], 'kelindans_employeeid_company_unique');
            $table->unique(['ic', 'company_id'], 'kelindans_ic_company_unique');
        });

        // agents: employeeid and ic unique per company
        Schema::table('agents', function (Blueprint $table) {
            $table->unique(['employeeid', 'company_id'], 'agents_employeeid_company_unique');
            $table->unique(['ic', 'company_id'], 'agents_ic_company_unique');
        });

        // supervisors: employeeid and ic unique per company
        Schema::table('supervisors', function (Blueprint $table) {
            $table->unique(['employeeid', 'company_id'], 'supervisors_employeeid_company_unique');
            $table->unique(['ic', 'company_id'], 'supervisors_ic_company_unique');
        });

        // invoices: invoiceno unique per company
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['invoiceno', 'company_id'], 'invoices_invoiceno_company_unique');
        });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique('drivers_employeeid_company_unique');
            $table->dropUnique('drivers_ic_company_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_code_company_unique');
        });

        Schema::table('lorrys', function (Blueprint $table) {
            $table->dropUnique('lorrys_lorryno_company_unique');
        });

        Schema::table('kelindans', function (Blueprint $table) {
            $table->dropUnique('kelindans_employeeid_company_unique');
            $table->dropUnique('kelindans_ic_company_unique');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropUnique('agents_employeeid_company_unique');
            $table->dropUnique('agents_ic_company_unique');
        });

        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropUnique('supervisors_employeeid_company_unique');
            $table->dropUnique('supervisors_ic_company_unique');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_invoiceno_company_unique');
        });
    }
}
