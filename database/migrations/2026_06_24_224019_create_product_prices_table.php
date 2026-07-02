<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPricesTable extends Migration
{
    public function up()
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('company_id');
            $table->string('name', 100);
            $table->decimal('price', 10, 2);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_prices');
    }
}
