<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Patch P1 (see PATCHES.md): currencies.rounding was decimal(8,2), so 3-decimal
 * currencies such as JOD (rounding 0.001) were stored as 0.00 and the POS till's
 * float helpers throw on a zero precision. Widen the column and re-apply the
 * rounding from the seed data for every currency whose stored value was truncated.
 *
 * Target location upstream: plugins/webkul/support/database/migrations/
 * (also register it in SupportServiceProvider::hasMigrations()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('rounding', 12, 6)->default(0.01)->change();
        });

        $path = base_path('plugins/webkul/security/src/Data/currencies.json');

        if (! File::exists($path)) {
            return;
        }

        $seed = collect(json_decode(File::get($path), true))->keyBy('name');

        DB::table('currencies')->orderBy('id')->get(['id', 'name', 'decimal_places', 'rounding'])
            ->each(function (object $currency) use ($seed): void {
                $expected = isset($seed[$currency->name]['rounding'])
                    ? (float) $seed[$currency->name]['rounding']
                    : (float) pow(10, -max((int) $currency->decimal_places, 0));

                if ((float) $currency->rounding <= 0 && $expected > 0) {
                    DB::table('currencies')->where('id', $currency->id)->update(['rounding' => $expected]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('rounding', 8, 2)->default(0.00)->change();
        });
    }
};
