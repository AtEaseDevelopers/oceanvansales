<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user')->nullable();
            $table->string('action', 20);
            $table->string('model', 100);
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('changes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['model', 'model_id']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
