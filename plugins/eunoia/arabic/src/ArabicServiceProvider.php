<?php

namespace Eunoia\Arabic;

use Eunoia\Arabic\Translation\OverlayLoader;
use Eunoia\Core\Support\InstalledPlugins;
use Filament\Panel;
use Illuminate\Contracts\Translation\Loader;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class ArabicServiceProvider extends PackageServiceProvider
{
    public static string $name = 'eunoia-arabic';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasDependencies([
                'eunoia-core',
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command->installDependencies();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        $this->app->extend('translation.loader', fn (Loader $loader): Loader => new OverlayLoader(
            $loader,
            [
                __DIR__.'/../resources/overrides',
                __DIR__.'/../resources/overrides-manual',
            ],
            fn (): bool => $this->app->make(InstalledPlugins::class)->has(static::$name),
        ));

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ArabicPlugin::make());
        });
    }

    public function packageBooted(): void
    {
        if (! $this->app->make(InstalledPlugins::class)->has(static::$name)) {
            return;
        }

        ArabicPluralizer::install(static::plurals());
    }

    /**
     * @return array<string, string>
     */
    public static function plurals(): array
    {
        return require __DIR__.'/../resources/glossary/plurals.php';
    }
}
