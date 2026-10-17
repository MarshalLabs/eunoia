<?php

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\Checks\CurrencyPrecisionCheck;
use Eunoia\Core\Doctor\Checks\DatabaseCheck;
use Eunoia\Core\Doctor\Checks\LocaleCheck;
use Eunoia\Core\Doctor\Checks\QueueWorkerCheck;
use Eunoia\Core\Doctor\CheckStatus;
use Eunoia\Core\Doctor\Doctor;
use Eunoia\Core\Doctor\DoctorCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

it('passes the database check', function () {
    expect(app(DatabaseCheck::class)->run()->status)->toBe(CheckStatus::Ok);
});

it('fails the currency check when JOD rounding is not 0.001', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['rounding' => 0]);

    $result = app(CurrencyPrecisionCheck::class)->run();

    expect($result->status)->toBe(CheckStatus::Fail)
        ->and($result->message)->toContain('JOD');
});

it('fails the currency check when JOD is missing', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['name' => 'JOX']);

    expect(app(CurrencyPrecisionCheck::class)->run()->status)->toBe(CheckStatus::Fail);
});

it('warns instead of passing when the queue is sync (no worker involved)', function () {
    config(['queue.default' => 'sync']);

    expect(app(QueueWorkerCheck::class)->withTimeout(1)->run()->status)->toBe(CheckStatus::Warn);
});

it('warns when the cache store cannot be shared with a worker', function () {
    config(['queue.default' => 'database', 'cache.default' => 'array']);

    expect(app(QueueWorkerCheck::class)->withTimeout(1)->run()->status)->toBe(CheckStatus::Warn);
});

it('fails when no worker picks up the heartbeat', function () {
    Queue::fake();
    config(['queue.default' => 'database', 'cache.default' => 'file']);

    $result = app(QueueWorkerCheck::class)->withTimeout(1)->run();

    expect($result->status)->toBe(CheckStatus::Fail)
        ->and($result->message)->toContain('queue:work');
});

it('warns when the locale is not Arabic', function () {
    config(['app.locale' => 'en']);

    expect(app(LocaleCheck::class)->run()->status)->toBe(CheckStatus::Warn);
});

it('accepts Arabic locale with Amman timezone', function () {
    config(['app.locale' => 'ar', 'app.timezone' => 'Asia/Amman']);

    expect(app(LocaleCheck::class)->run()->status)->toBe(CheckStatus::Ok);
});

it('ships the core checks', function () {
    expect(app(Doctor::class)->checks())->toContain(DatabaseCheck::class, CurrencyPrecisionCheck::class, QueueWorkerCheck::class, LocaleCheck::class);
});

it('lets other plugins register a check once', function () {
    $check = new class implements DoctorCheck
    {
        public function label(): string
        {
            return 'demo';
        }

        public function run(): CheckResult
        {
            return CheckResult::fail('broken on purpose');
        }
    };

    app()->instance($check::class, $check);

    $doctor = new Doctor;
    $doctor->register($check::class)->register($check::class);

    $results = $doctor->run();

    expect($doctor->checks())->toHaveCount(1)
        ->and($results[0]['result']->status)->toBe(CheckStatus::Fail);
});

it('turns an exception in a check into a failure', function () {
    DB::shouldReceive('connection')->andThrow(new RuntimeException('db down'));

    $results = (new Doctor([DatabaseCheck::class]))->run();

    expect($results[0]['result']->status)->toBe(CheckStatus::Fail)
        ->and($results[0]['result']->message)->toBe('db down');
});

it('exits with failure when a check fails', function () {
    DB::table('currencies')->where('name', 'JOD')->update(['rounding' => 0]);

    $this->artisan('eunoia:doctor', ['--timeout' => 1])->assertFailed();
});
