<?php

namespace Eunoia\Core\Doctor\Checks;

use Eunoia\Core\Doctor\CheckResult;
use Eunoia\Core\Doctor\DoctorCheck;
use Eunoia\Core\Jobs\QueueHeartbeat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Dispatches a heartbeat job and waits for a worker to run it.
 */
class QueueWorkerCheck implements DoctorCheck
{
    protected int $timeout = 10;

    public function withTimeout(int $seconds): static
    {
        $this->timeout = max(1, $seconds);

        return $this;
    }

    public function label(): string
    {
        return __('eunoia-core::doctor.checks.queue');
    }

    public function run(): CheckResult
    {
        $connection = (string) config('queue.default');

        if ($connection === 'sync') {
            return CheckResult::warn(__('eunoia-core::doctor.messages.queue_sync'));
        }

        if (config('cache.default') === 'array') {
            return CheckResult::warn(__('eunoia-core::doctor.messages.queue_array_cache'));
        }

        $token = (string) Str::uuid();
        $key = QueueHeartbeat::cacheKey($token);

        QueueHeartbeat::dispatch($token);

        $deadline = microtime(true) + $this->timeout;

        do {
            if (Cache::get($key)) {
                Cache::forget($key);

                return CheckResult::ok(__('eunoia-core::doctor.messages.queue_ok', ['connection' => $connection]));
            }

            usleep(250_000);
        } while (microtime(true) < $deadline);

        return CheckResult::fail(__('eunoia-core::doctor.messages.queue_timeout', [
            'connection' => $connection,
            'seconds'    => $this->timeout,
            'command'    => 'php artisan queue:work --queue=jofotara,messages,default',
        ]));
    }
}
