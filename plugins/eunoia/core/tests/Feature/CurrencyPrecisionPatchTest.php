<?php

use Eunoia\Core\Doctor\Checks\CurrencyPrecisionCheck;
use Eunoia\Core\Doctor\CheckStatus;
use Illuminate\Support\Facades\DB;
use Webkul\Support\Models\Currency;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensureERPInstalled();
});

/**
 * Patch P1 / finding F1 / JO-01: currencies.rounding must keep 0.001 for JOD.
 * Note: up() runs ALTER TABLE; on MySQL that commits the test transaction (CI uses PostgreSQL).
 */
function runCurrencyPrecisionPatch(): void
{
    $migration = require base_path('plugins/webkul/support/database/migrations/2026_10_17_000001_widen_currencies_rounding_precision.php');

    $migration->up();
}

it('stores JOD with 3 decimals and rounding 0.001', function () {
    $jod = Currency::query()->where('name', 'JOD')->firstOrFail();

    expect((int) $jod->decimal_places)->toBe(3)
        ->and((float) $jod->rounding)->toBe(0.001);
});

it('leaves no currency with rounding <= 0', function () {
    expect(Currency::query()->where('rounding', '<=', 0)->pluck('name')->all())->toBe([]);
});

it('restores the seed rounding of every 3+ decimal currency', function (string $name, float $rounding) {
    expect((float) Currency::query()->where('name', $name)->value('rounding'))->toBe($rounding);
})->with([
    ['JOD', 0.001], ['KWD', 0.001], ['BHD', 0.001], ['OMR', 0.001], ['TND', 0.001],
    ['IQD', 0.001], ['LYD', 0.001], ['CLF', 0.0001], ['UYI', 0.0001], ['UYW', 0.0001],
]);

it('repairs a truncated rounding when the migration runs again', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['rounding' => 0]);

    runCurrencyPrecisionPatch();

    expect((float) Currency::query()->where('name', 'JOD')->value('rounding'))->toBe(0.001);
});

it('does not touch a currency whose rounding is already set', function () {
    DB::table('currencies')->where('name', 'USD')->update(['rounding' => 0.05]);

    runCurrencyPrecisionPatch();

    expect((float) Currency::query()->where('name', 'USD')->value('rounding'))->toBe(0.05);
});

it('does JOD maths in fils with the stored rounding', function () {
    $rounding = (float) Currency::query()->where('name', 'JOD')->value('rounding');

    expect(float_round(1.255 * 3, precisionRounding: $rounding))->toBe(3.765)
        ->and(float_compare(3.7649, 3.765, precisionRounding: $rounding))->toBe(0)
        ->and(float_round(0.0005, precisionRounding: $rounding))->toBe(0.001);
});

it('passes the doctor currency check', function () {
    expect(app(CurrencyPrecisionCheck::class)->run()->status)->toBe(CheckStatus::Ok);
});
