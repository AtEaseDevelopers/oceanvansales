<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Offline numbers become "A" + (offline_range * 10000 + seq): the mobile app only reads the
 * digits after "/A", so the per-driver letters from 2026_10_15_000001 broke it.
 */
class ReplaceOfflineSeriesWithOfflineRangeOnDriversTable extends Migration
{
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->unsignedSmallInteger('offline_range')->nullable()->after('invoice_runningnumber');
            $table->unique(['company_id', 'offline_range']);
        });

        $hasSeries = Schema::hasColumn('drivers', 'offline_series');

        // Letters were handed out B, C, D... then BA, BB...; a driver keeps that position as
        // their range (B = 1, C = 2, ...). Drivers without a letter take the lowest free range.
        $letters = array_slice(range('A', 'Z'), 1);
        $codes = $letters;
        foreach ($letters as $first) {
            foreach (range('A', 'Z') as $second) {
                $codes[] = $first . $second;
            }
        }
        $positionOf = array_flip($codes);

        $columns = $hasSeries ? ['id', 'company_id', 'offline_series'] : ['id', 'company_id'];
        DB::table('drivers')->orderBy('id')->get($columns)
            ->groupBy('company_id')
            ->each(function ($drivers) use ($positionOf) {
                $used = [];
                $withoutLetter = [];
                foreach ($drivers as $driver) {
                    $letter = $driver->offline_series ?? null;
                    if ($letter !== null && isset($positionOf[$letter])) {
                        $range = $positionOf[$letter] + 1;
                        $used[$range] = true;
                        DB::table('drivers')->where('id', $driver->id)->update(['offline_range' => $range]);
                    } else {
                        $withoutLetter[] = $driver->id;
                    }
                }
                $range = 1;
                foreach ($withoutLetter as $id) {
                    while (isset($used[$range])) {
                        $range++;
                    }
                    $used[$range] = true;
                    DB::table('drivers')->where('id', $id)->update(['offline_range' => $range]);
                }
            });

        if ($hasSeries) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'offline_series']);
                $table->dropColumn('offline_series');
            });
        }
    }

    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('offline_series', 3)->nullable()->after('invoice_runningnumber');
            $table->unique(['company_id', 'offline_series']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'offline_range']);
            $table->dropColumn('offline_range');
        });
    }
}
