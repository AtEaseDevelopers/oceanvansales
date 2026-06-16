<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTinAndPhonesToCompaniesTable extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('tin', 50)->nullable()->after('ssm');
            $table->string('phone1', 20)->nullable()->after('tin');
            $table->string('phone2', 20)->nullable()->after('phone1');
            $table->string('phone3', 20)->nullable()->after('phone2');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['tin', 'phone1', 'phone2', 'phone3']);
        });
    }
}
