<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToDataTables extends Migration
{
    protected array $tables = [
        'kelindans',
        'agents',
        'supervisors',
        'products',
        'customers',
        'special_prices',
        'focs',
        'assigns',
        'invoices',
        'invoice_details',
        'invoice_payments',
        'tasks',
        'trips',
        'inventory_balances',
        'inventory_transactions',
        'inventory_transfers',
        'task_transfers',
    ];

    public function up()
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->bigInteger('company_id')->unsigned(false)->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
}
