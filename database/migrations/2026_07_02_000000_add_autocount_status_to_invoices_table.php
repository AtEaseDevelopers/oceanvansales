<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAutocountStatusToInvoicesTable extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 0 = not synced, 1 = queued, 2 = synced, 3 = failed
            $table->tinyInteger('autocount_status')->default(0)->after('status');
            $table->string('autocount_docno')->nullable()->after('autocount_status');
            $table->text('autocount_error')->nullable()->after('autocount_docno');
            $table->timestamp('autocount_synced_at')->nullable()->after('autocount_error');
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'autocount_status',
                'autocount_docno',
                'autocount_error',
                'autocount_synced_at',
            ]);
        });
    }
}
