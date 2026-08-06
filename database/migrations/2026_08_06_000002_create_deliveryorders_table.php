<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryordersTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('deliveryorders');

        Schema::create('deliveryorders', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('company_id')->unsigned(false)->nullable();
            $table->string('invoiceno', 255);
            $table->datetime('date');
            $table->integer('customer_id');
            $table->integer('driver_id')->nullable();
            $table->integer('kelindan_id')->nullable();
            $table->integer('agent_id')->nullable();
            $table->integer('supervisor_id')->nullable();
            $table->string('paymentterm')->nullable();
            $table->bigInteger('status');
            $table->string('remark', 255)->nullable();
            $table->string('chequeno', 20)->nullable();
            $table->string('attachment')->nullable();
            $table->integer('trip_id')->nullable();
            $table->bigInteger('invoice_id')->unsigned(false)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->unique(['invoiceno', 'company_id'], 'deliveryorders_invoiceno_company_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('deliveryorders');
    }
}
