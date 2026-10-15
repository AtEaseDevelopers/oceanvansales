<?php

use App\Models\Driver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOfflineSeriesToDriversTable extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('offline_series', 3)->nullable()->after('invoice_runningnumber');
            $table->unique(['company_id', 'offline_series']);
        });

        // Give every existing driver their own offline invoice series (B, C, D... per
        // company, in driver id order). "A" stays reserved for the old shared series.
        $codes = Driver::offlineSeriesCodes();
        DB::table('drivers')->orderBy('id')->get(['id', 'company_id'])
            ->groupBy('company_id')
            ->each(function ($drivers) use ($codes) {
                foreach ($drivers->values() as $i => $driver) {
                    DB::table('drivers')->where('id', $driver->id)->update(['offline_series' => $codes[$i]]);
                }
            });
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'offline_series']);
            $table->dropColumn('offline_series');
        });
    }
}
