<?php

namespace Eunoia\JoFotara;

use Eunoia\Core\Support\InstalledPlugins;
use Filament\Contracts\Plugin;
use Filament\Panel;

class JoFotaraPlugin implements Plugin
{
    public function getId(): string
    {
        return 'eunoia-jofotara';
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

        $panel->when($panel->getId() === 'admin', function (Panel $panel): void {
            $panel
                ->discoverResources(in: __DIR__.'/Filament/Resources', for: 'Eunoia\\JoFotara\\Filament\\Resources')
                ->discoverPages(in: __DIR__.'/Filament/Pages', for: 'Eunoia\\JoFotara\\Filament\\Pages');
        });
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
