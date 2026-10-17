<?php

namespace Eunoia\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeat implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 1;

    public function __construct(public string $token) {}

    public static function cacheKey(string $token): string
    {
        return 'eunoia:doctor:heartbeat:'.$token;
    }

    public function handle(): void
    {
        Cache::put(static::cacheKey($this->token), true, now()->addMinutes(5));
    }
}
