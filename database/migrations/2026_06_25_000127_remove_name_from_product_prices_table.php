<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveNameFromProductPricesTable extends Migration
{
    public function up()
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down()
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->string('name', 100)->after('company_id');
        });
    }
}
