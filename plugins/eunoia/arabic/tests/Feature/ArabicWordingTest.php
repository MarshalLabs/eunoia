<?php

use Eunoia\Arabic\ArabicPluralizer;
use Eunoia\Arabic\ArabicServiceProvider;
use Eunoia\Core\Support\InstalledPlugins;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    Artisan::call('eunoia-arabic:install', ['--no-interaction' => true]);
    app(InstalledPlugins::class)->forget();
    app('translator')->setLoaded([]);
    ArabicPluralizer::install(ArabicServiceProvider::plurals());
});

afterEach(function () {
    Pluralizer::useLanguage('english');
});

it('pluralises Arabic labels from the glossary and never adds an English s', function (string $singular, string $plural) {
    expect(Str::plural($singular))->toBe($plural);
})->with([
    ['فاتورة', 'فواتير'],
    ['إشعار دائن', 'إشعارات دائنة'],
    ['منتج', 'منتجات'],
    ['جلسة', 'ورديات'],
    ['كلمة غير معروفة', 'كلمة غير معروفة'],
]);

it('keeps English pluralisation working', function () {
    expect(Str::plural('invoice'))->toBe('invoices')
        ->and(Str::plural('category'))->toBe('categories')
        ->and(Str::singular('invoices'))->toBe('invoice');
});

it('writes grammatical Arabic empty states', function () {
    app()->setLocale('ar');

    expect(__('filament-tables::table.empty.heading', ['model' => Str::plural('فاتورة')]))->toBe('لا توجد فواتير بعد');
});

it('overrides upstream strings with the glossary', function () {
    app()->setLocale('ar');

    expect(__('point-of-sale::enums/stock-update-mode.at-closing'))->toBe('عند إغلاق الوردية');
});

it('leaves English and other locales alone', function () {
    app()->setLocale('en');

    expect(__('filament-tables::table.empty.heading', ['model' => 'invoices']))->toBe('No invoices');
});

it('never renames other countries VAT numbers', function () {
    $overrides = collect(glob(base_path('plugins/eunoia/arabic/resources/overrides/ar/*/*.php')) ?: [])
        ->map(fn (string $file): string => file_get_contents($file))
        ->implode('');

    expect($overrides)->not->toContain('vat-number');
});

it('never touches the word stage (مرحلة)', function () {
    $overrides = collect(glob(base_path('plugins/eunoia/arabic/resources/overrides/ar/*/*.php')) ?: [])
        ->map(fn (string $file): string => file_get_contents($file))
        ->implode('');

    expect($overrides)->not->toContain('معتمدة المرحلة');
});

it('replaces every "posted" form, with or without diacritics', function () {
    app()->setLocale('ar');

    $upstream = collect(glob(base_path('plugins/webkul/*/resources/lang/ar/**/*.php')) ?: [])
        ->merge(glob(base_path('plugins/webkul/*/resources/lang/ar/*.php')) ?: []);

    $remaining = $upstream->unique()->filter(function (string $file): bool {
        preg_match('#plugins/webkul/([^/]+)/resources/lang/ar/(.+)\.php$#', $file, $m);

        return collect(Arr::dot((array) __("{$m[1]}::{$m[2]}")))
            ->contains(fn ($value): bool => is_string($value) && preg_match('/رح[\x{064E}\x{0651}]+ل/u', $value) === 1);
    });

    expect($remaining->values()->all())->toBe([]);
});
