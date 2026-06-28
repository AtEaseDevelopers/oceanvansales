<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameDriverIdToLorryIdInAssignsTable extends Migration
{
    public function up()
    {
        Schema::table('assigns', function (Blueprint $table) {
            $table->renameColumn('driver_id', 'lorry_id');
        });
    }

    public function down()
    {
        Schema::table('assigns', function (Blueprint $table) {
            $table->renameColumn('lorry_id', 'driver_id');
        });
    }
}
