<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripAnekaQuantitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_aneka_quantities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('trip_id');
            $table->bigInteger('product_id');
            $table->bigInteger('company_id')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['trip_id', 'product_id']);
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trip_aneka_quantities');
    }
}
