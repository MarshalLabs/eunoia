<?php

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\Checks\CurrencyPrecisionCheck;
use Eunoia\Core\Doctor\Checks\DatabaseCheck;
use Eunoia\Core\Doctor\Checks\LocaleCheck;
use Eunoia\Core\Doctor\Checks\QueueWorkerCheck;
use Eunoia\Core\Doctor\Doctor;
use Illuminate\Support\Facades\DB;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

it('passes the database check', function () {
    expect(app(DatabaseCheck::class)->run()->status)->toBe(CheckResult::OK);
});

it('fails the currency check when JOD rounding is not 0.001', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['rounding' => 0]);

    $result = app(CurrencyPrecisionCheck::class)->run();

    expect($result->status)->toBe(CheckResult::FAIL)
        ->and($result->message)->toContain('JOD');
});

it('fails the currency check when JOD is missing', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['name' => 'JOX']);

    expect(app(CurrencyPrecisionCheck::class)->run()->status)->toBe(CheckResult::FAIL);
});

it('sees the queue heartbeat', function () {
    expect(app(QueueWorkerCheck::class)->withTimeout(2)->run()->status)->toBe(CheckResult::OK);
});

it('warns when the locale is not Arabic', function () {
    config(['app.locale' => 'en']);

    expect(app(LocaleCheck::class)->run()->status)->toBe(CheckResult::WARN);
});

it('accepts Arabic locale with Amman timezone', function () {
    config(['app.locale' => 'ar', 'app.timezone' => 'Asia/Amman']);

    expect(app(LocaleCheck::class)->run()->status)->toBe(CheckResult::OK);
});

it('lets other plugins register checks', function () {
    $doctor = app(Doctor::class);

    expect($doctor->checks())->toContain(DatabaseCheck::class, CurrencyPrecisionCheck::class, QueueWorkerCheck::class, LocaleCheck::class);
});

it('exits with failure when a check fails', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['rounding' => 0]);

    $this->artisan('eunoia:doctor', ['--timeout' => 2])->assertFailed();
});
