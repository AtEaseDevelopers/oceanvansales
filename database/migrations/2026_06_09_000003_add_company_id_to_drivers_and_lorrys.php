<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToDriversAndLorrys extends Migration
{
    public function up()
    {
        foreach (['drivers', 'lorrys'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->bigInteger('company_id')->unsigned(false)->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        foreach (['drivers', 'lorrys'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
}
