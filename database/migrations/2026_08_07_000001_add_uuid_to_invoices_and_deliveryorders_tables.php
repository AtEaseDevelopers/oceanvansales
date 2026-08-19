<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUuidToInvoicesAndDeliveryordersTables extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('invoiceno');
            $table->index('uuid');
        });

        Schema::table('deliveryorders', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('invoiceno');
            $table->index('uuid');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });

        Schema::table('deliveryorders', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
        });
    }
}
