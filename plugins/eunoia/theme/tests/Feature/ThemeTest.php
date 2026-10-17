<?php

use Eunoia\Core\Doctor\Checks\FontsCheck;
use Eunoia\Core\Doctor\CheckStatus;
use Eunoia\Core\Doctor\Doctor;
use Eunoia\Core\Support\InstalledPlugins;
use Eunoia\Theme\BrandDefaults;
use Eunoia\Theme\Doctor\ThemeFontsCheck;
use Eunoia\Theme\ThemePlugin;
use Eunoia\Theme\ThemeServiceProvider;
use Eunoia\Theme\Tokens;
use Filament\FontProviders\LocalFontProvider;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Facades\Artisan;
use Webkul\PluginManager\Models\Plugin;
use Webkul\Support\Settings\BrandSettings;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

function installTheme(): void
{
    Artisan::call('eunoia-theme:install', ['--no-interaction' => true]);
    app(InstalledPlugins::class)->forget();
}

it('installs the theme and its core dependency', function () {
    installTheme();

    expect(Plugin::query()->whereIn('name', ['eunoia-core', 'eunoia-theme'])->where('is_installed', true)->count())->toBe(2);
});

it('replaces upstream default brand colours with Eunoia tokens', function () {
    $settings = app(BrandSettings::class);
    $settings->primary_color = '#155dfc';
    $settings->warning_color = '#E17100';
    $settings->save();

    $changed = BrandDefaults::apply();

    $settings = app(BrandSettings::class)->refresh();

    expect($changed)->toContain('primary_color', 'warning_color')
        ->and(strtolower($settings->primary_color))->toBe(strtolower(Tokens::PRIMARY))
        ->and($settings->warning_color)->toBe(Tokens::brandColors()['warning_color']);
});

it('keeps a brand colour the owner chose', function () {
    $settings = app(BrandSettings::class);
    $settings->primary_color = '#7c3aed';
    $settings->save();

    BrandDefaults::apply();

    expect(app(BrandSettings::class)->refresh()->primary_color)->toBe('#7c3aed');
});

it('gives panels the Eunoia colours and the self-hosted font once installed', function () {
    installTheme();

    $panel = Panel::make()->id('eunoia-test')->colors(['primary' => Color::Blue]);
    app(ThemePlugin::class)->register($panel);
    app(ThemePlugin::class)->boot($panel);

    expect(Color::convertToHex(FilamentColor::getColor('primary')[600]))->toBe(strtolower(Tokens::PRIMARY))
        ->and(Color::convertToHex($panel->getColors()['primary'][600]))->toBe(strtolower(Tokens::PRIMARY))
        ->and($panel->getFontFamily())->toBe('IBM Plex Sans Arabic')
        ->and($panel->getFontProvider())->toBe(LocalFontProvider::class)
        ->and($panel->getFontUrl())->toContain('fonts/eunoia/theme/eunoia-fonts/index.css');
});

it('leaves panels alone when the theme is not installed', function () {
    Plugin::query()->where('name', 'eunoia-theme')->update(['is_installed' => false]);
    app(InstalledPlugins::class)->forget();

    $panel = Panel::make()->id('eunoia-test');
    app(ThemePlugin::class)->register($panel);

    expect($panel->getFontFamily())->not->toBe('IBM Plex Sans Arabic');
});

it('renders RTL styles without Google Fonts or Cairo', function () {
    installTheme();
    app()->getProvider(ThemeServiceProvider::class)->packageBooted();
    app()->setLocale('ar');

    $html = view('support::rtl.styles', ['rtlLocales' => ['ar']])->render();

    expect($html)->not->toContain('fonts.googleapis.com')
        ->and($html)->not->toContain('Cairo')
        ->and($html)->toContain('.fi-topbar');
});

it('publishes the fonts and passes the doctor fonts check', function () {
    installTheme();

    expect(is_file(public_path(ThemeServiceProvider::fontStylesheetPath())))->toBeTrue()
        ->and(app(ThemeFontsCheck::class)->run()->status)->toBe(CheckStatus::Ok);
});

it('keeps white text readable on primary buttons and their hover shade', function () {
    foreach (['primary', 'success', 'warning', 'danger', 'info'] as $key) {
        $palette = Tokens::panelColors()[$key];

        expect(Color::calculateContrastRatio($palette[600], '#ffffff'))->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT)
            ->and(Color::calculateContrastRatio($palette[500], '#ffffff'))->toBeGreaterThanOrEqual(Color::WCAG_AA_TEXT);
    }
});

it('swaps the placeholder fonts check for the real one', function () {
    $doctor = new Doctor([FontsCheck::class]);
    $doctor->replace(FontsCheck::class, ThemeFontsCheck::class);

    expect($doctor->checks())->toBe([ThemeFontsCheck::class]);
});
