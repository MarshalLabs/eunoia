<?php

namespace Eunoia\LocalizationJo;

use Eunoia\Core\Support\InstalledPlugins;
use Eunoia\LocalizationJo\Console\SyncJordanDataCommand;
use Eunoia\LocalizationJo\Database\Seeders\JordanChartOfAccountsSeeder;
use Eunoia\LocalizationJo\Database\Seeders\JordanSeedDataSeeder;
use Eunoia\LocalizationJo\Database\Seeders\JordanTaxSeeder;
use Eunoia\LocalizationJo\Filament\PartnerIdentitiesTab;
use Eunoia\LocalizationJo\Models\JordanTaxProfile;
use Eunoia\LocalizationJo\Models\PartnerIdentity;
use Eunoia\LocalizationJo\Observers\PartnerPhoneObserver;
use Filament\Panel;
use Webkul\Account\Models\Tax;
use Webkul\Partner\Filament\Resources\PartnerResource\Support\PartnerSchemaRegistry;
use Webkul\Partner\Models\Partner;
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
                '2026_10_17_100003_create_eunoia_partner_identities_table',
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
                        app(JordanSeedDataSeeder::class)->run();
                        app(JordanChartOfAccountsSeeder::class)->run();
                        app(JordanTaxSeeder::class)->run();

                        $command->info(__('eunoia-localization-jo::localization.install.accounts'));
                        $command->info(__('eunoia-localization-jo::localization.install.taxes'));
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageBooted(): void
    {
        if (! $this->app->make(InstalledPlugins::class)->has(static::$name)) {
            return;
        }

        Partner::observe(PartnerPhoneObserver::class);

        PartnerSchemaRegistry::form('tabs.append', fn () => PartnerIdentitiesTab::make(), 30);
    }

    public function packageRegistered(): void
    {
        Tax::resolveRelationUsing('profile', fn (Tax $tax) => $tax->hasOne(JordanTaxProfile::class, 'tax_id'));
        Partner::resolveRelationUsing('identities', fn (Partner $partner) => $partner->hasMany(PartnerIdentity::class, 'partner_id'));

        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(LocalizationJoPlugin::make());
        });
    }
}
