<?php

namespace Eunoia\LocalizationJo;

use Filament\Contracts\Plugin;
use Filament\Panel;

class LocalizationJoPlugin implements Plugin
{
    public function getId(): string
    {
        return 'eunoia-localization-jo';
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
