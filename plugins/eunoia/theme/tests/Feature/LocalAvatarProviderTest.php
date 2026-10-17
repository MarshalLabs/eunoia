<?php

use Eunoia\Core\Support\InstalledPlugins;
use Eunoia\Theme\LocalAvatarProvider;
use Eunoia\Theme\ThemePlugin;
use Filament\Panel;
use Illuminate\Support\Facades\Artisan;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

function decodeAvatar(string $uri): string
{
    expect($uri)->toStartWith('data:image/svg+xml;base64,');

    return base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')));
}

it('builds Arabic and Latin initials', function (string $name, string $initials) {
    expect(LocalAvatarProvider::initials($name))->toBe($initials);
})->with([
    ['رنا الخطيب', 'ر ا'],
    ['أبو خالد', 'أ خ'],
    ['Admin', 'A'],
    ['mohammad abu aisheh', 'MA'],
    ['[SYSTEM] Admin', 'SA'],
    ['   ', '?'],
]);

it('returns a local SVG data URI, never a remote URL', function () {
    $svg = decodeAvatar(LocalAvatarProvider::forName('رنا الخطيب'));

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('ر ا')
        ->and($svg)->not->toContain('ui-avatars');
});

it('escapes names so they cannot inject markup', function () {
    $svg = decodeAvatar(LocalAvatarProvider::forName('<b> "x"'));

    expect($svg)->not->toContain('<b>');
});

it('is the default avatar provider once the theme is installed', function () {
    Artisan::call('eunoia-theme:install', ['--no-interaction' => true]);
    app(InstalledPlugins::class)->forget();

    $panel = Panel::make()->id('eunoia-avatar-test');
    app(ThemePlugin::class)->register($panel);

    expect($panel->getDefaultAvatarProvider())->toBe(LocalAvatarProvider::class);
});
