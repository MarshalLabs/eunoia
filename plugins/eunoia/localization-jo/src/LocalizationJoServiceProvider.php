<?php

namespace Eunoia\LocalizationJo;

use Eunoia\LocalizationJo\Console\SyncJordanDataCommand;
use Eunoia\LocalizationJo\Database\Seeders\JordanChartOfAccountsSeeder;
use Eunoia\LocalizationJo\Database\Seeders\JordanTaxSeeder;
use Eunoia\LocalizationJo\Models\JordanTaxProfile;
use Filament\Panel;
use Webkul\Account\Models\Tax;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class LocalizationJoServiceProvider extends PackageServiceProvider
{
    public static string $name = 'eunoia-localization-jo';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasCommands([
                SyncJordanDataCommand::class,
            ])
            ->hasMigrations([
                '2026_10_17_100001_create_eunoia_jo_tax_profiles_table',
                '2026_10_17_100002_create_eunoia_jo_account_profiles_table',
            ])
            ->runsMigrations()
            ->hasDependencies([
                'eunoia-core',
                'accounts',
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations()
                    ->endWith(function (InstallCommand $command): void {
                        app(JordanChartOfAccountsSeeder::class)->run();
                        app(JordanTaxSeeder::class)->run();

                        $command->info(__('eunoia-localization-jo::localization.install.accounts'));
                        $command->info(__('eunoia-localization-jo::localization.install.taxes'));
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        Tax::resolveRelationUsing('profile', fn (Tax $tax) => $tax->hasOne(JordanTaxProfile::class, 'tax_id'));

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(LocalizationJoPlugin::make());
        });
    }
}
