<?php

namespace Eunoia\JoFotara;

use Filament\Panel;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class JoFotaraServiceProvider extends PackageServiceProvider
{
    public static string $name = 'eunoia-jofotara';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasMigrations([
                '2026_10_17_200001_create_eunoia_jofotara_tables',
            ])
            ->runsMigrations()
            ->hasSettings([
                '2026_10_17_200001_create_eunoia_jofotara_settings',
            ])
            ->runsSettings()
            ->hasDependencies([
                'eunoia-core',
                'eunoia-localization-jo',
                'invoices',
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/eunoia-jofotara.php', 'eunoia-jofotara');

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(JoFotaraPlugin::make());
        });
    }
}
