<?php

namespace Eunoia\Core;

use Filament\Contracts\Plugin;
use Filament\Panel;

class CorePlugin implements Plugin
{
    public function getId(): string
    {
        return 'eunoia-core';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
