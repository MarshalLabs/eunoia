<?php

namespace Eunoia\Core;

use Eunoia\Core\Console\Commands\DoctorCommand;
use Eunoia\Core\Console\Commands\EunoiaInstallCommand;
use Eunoia\Core\Console\Commands\LintRtlCommand;
use Eunoia\Core\Doctor\Checks\CurrencyPrecisionCheck;
use Eunoia\Core\Doctor\Checks\DatabaseCheck;
use Eunoia\Core\Doctor\Checks\FontsCheck;
use Eunoia\Core\Doctor\Checks\LocaleCheck;
use Eunoia\Core\Doctor\Checks\QueueWorkerCheck;
use Eunoia\Core\Doctor\Doctor;
use Filament\Panel;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class CoreServiceProvider extends PackageServiceProvider
{
    public static string $name = 'eunoia-core';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasCommands([
                EunoiaInstallCommand::class,
                DoctorCommand::class,
                LintRtlCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->installDependencies()
                    ->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command) {});
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Doctor::class, fn (): Doctor => new Doctor([
            DatabaseCheck::class,
            CurrencyPrecisionCheck::class,
            QueueWorkerCheck::class,
            LocaleCheck::class,
            FontsCheck::class,
        ]));

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(CorePlugin::make());
        });
    }
}
