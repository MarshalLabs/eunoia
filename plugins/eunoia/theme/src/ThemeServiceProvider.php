<?php

namespace Eunoia\Theme;

use Eunoia\Core\Doctor\Checks\FontsCheck;
use Eunoia\Core\Doctor\Doctor;
use Eunoia\Core\Support\InstalledPlugins;
use Eunoia\Theme\Doctor\ThemeFontsCheck;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Font;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class ThemeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'eunoia-theme';

    public const ASSET_PACKAGE = 'eunoia/theme';

    public const FONT_ID = 'eunoia-fonts';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasTranslations()
            ->hasDependencies([
                'eunoia-core',
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->endWith(function (InstallCommand $command): void {
                        Artisan::call('filament:assets');

                        $changed = BrandDefaults::apply();

                        $command->info(__('eunoia-theme::theme.install.done', ['count' => count($changed)]));
                    });
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {});
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ThemePlugin::make());
        });
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Font::make(static::FONT_ID, __DIR__.'/../resources/fonts'),
            Css::make('eunoia-theme', __DIR__.'/../resources/css/eunoia-theme.css'),
        ], static::ASSET_PACKAGE);

        if (! app(InstalledPlugins::class)->has(static::$name)) {
            return;
        }

        View::prependNamespace('support', __DIR__.'/../resources/views/overrides/support');

        $this->app->resolving(Doctor::class, function (Doctor $doctor): void {
            $doctor->replace(FontsCheck::class, ThemeFontsCheck::class);
        });

        if ($this->app->resolved(Doctor::class)) {
            $this->app->make(Doctor::class)->replace(FontsCheck::class, ThemeFontsCheck::class);
        }
    }

    public static function fontStylesheetPath(): string
    {
        return Font::make(static::FONT_ID, '')->package(static::ASSET_PACKAGE)->getRelativePublicPath().'/index.css';
    }
}
