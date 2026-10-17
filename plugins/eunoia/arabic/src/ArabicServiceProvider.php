<?php

namespace Eunoia\Arabic;

use Carbon\Carbon;
use Eunoia\Arabic\Translation\OverlayLoader;
use Eunoia\Core\Support\InstalledPlugins;
use Filament\Forms\Components\DateTimePicker;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Foundation\Events\LocaleUpdated;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
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
        $this->mergeConfigFrom(__DIR__.'/../config/eunoia-arabic.php', 'eunoia-arabic');

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

        $this->useLevantineDates($this->app->getLocale());

        Event::listen(LocaleUpdated::class, fn (LocaleUpdated $event) => $this->useLevantineDates($event->locale));

        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultDateDisplayFormat(ArabicDates::SHORT_FORMAT)
                ->defaultDateTimeDisplayFormat(ArabicDates::SHORT_DATETIME_FORMAT);
        });

        DateTimePicker::configureUsing(function (DateTimePicker $picker): void {
            $picker->displayFormat(fn (DateTimePicker $component): string => $component->hasTime()
                ? ArabicDates::SHORT_DATETIME_FORMAT
                : ArabicDates::SHORT_FORMAT);
        });

        Schema::configureUsing(function (Schema $schema): void {
            $schema
                ->defaultDateDisplayFormat(ArabicDates::SHORT_FORMAT)
                ->defaultDateTimeDisplayFormat(ArabicDates::SHORT_DATETIME_FORMAT);
        });
    }

    /**
     * Laravel sets Carbon's locale to the app locale; for Arabic we use ar_JO (Levantine month names).
     */
    protected function useLevantineDates(string $locale): void
    {
        if ($locale !== 'ar') {
            return;
        }

        Carbon::setLocale(ArabicDates::CARBON_LOCALE);
        Date::setLocale(ArabicDates::CARBON_LOCALE);
    }

    /**
     * @return array<string, string>
     */
    public static function plurals(): array
    {
        return require __DIR__.'/../resources/glossary/plurals.php';
    }
}
