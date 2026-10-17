<?php

namespace Eunoia\Theme;

use Eunoia\Core\Support\InstalledPlugins;
use Filament\Contracts\Plugin;
use Filament\FontProviders\LocalFontProvider;
use Filament\Panel;
use Filament\Support\Facades\FilamentColor;

class ThemePlugin implements Plugin
{
    public function getId(): string
    {
        return 'eunoia-theme';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! app(InstalledPlugins::class)->has($this->getId())) {
            return;
        }

        $panel
            ->defaultAvatarProvider(LocalAvatarProvider::class)
            ->font(
                'IBM Plex Sans Arabic',
                url: asset(ThemeServiceProvider::fontStylesheetPath()),
                provider: LocalFontProvider::class,
            );
    }

    /**
     * Colours are applied at boot: register() runs inside ->plugin(), before the panel provider's own
     * ->colors() call, and Panel::boot() has already registered colours by the time plugins boot.
     */
    public function boot(Panel $panel): void
    {
        if (! app(InstalledPlugins::class)->has($this->getId())) {
            return;
        }

        $panel->colors(Tokens::panelColors());

        FilamentColor::register(Tokens::panelColors());
    }
}
