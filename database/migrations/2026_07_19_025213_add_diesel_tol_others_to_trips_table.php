<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDieselTolOthersToTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->decimal('diesel', 10, 2)->nullable()->after('advance_amount');
            $table->json('diesel_images')->nullable()->after('diesel');
            $table->decimal('tol', 10, 2)->nullable()->after('diesel_images');
            $table->json('tol_images')->nullable()->after('tol');
            $table->decimal('others', 10, 2)->nullable()->after('tol_images');
            $table->json('others_images')->nullable()->after('others');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['diesel', 'diesel_images', 'tol', 'tol_images', 'others', 'others_images']);
        });
    }
}
