<?php

namespace Eunoia\Arabic;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ArabicPlugin implements Plugin
{
    public function getId(): string
    {
        return 'eunoia-arabic';
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
