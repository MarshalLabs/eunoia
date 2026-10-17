<?php

use Carbon\Carbon;
use Eunoia\Arabic\ArabicDates;
use Eunoia\Arabic\ArabicServiceProvider;
use Eunoia\Arabic\Digits;
use Eunoia\Core\Support\InstalledPlugins;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Pluralizer;
use Livewire\Component;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    Artisan::call('eunoia-arabic:install', ['--no-interaction' => true]);
    app(InstalledPlugins::class)->forget();
    app()->getProvider(ArabicServiceProvider::class)->packageBooted();
});

afterEach(function () {
    Pluralizer::useLanguage('english');
});

it('uses Levantine month names when the app is in Arabic (AR-07, F13)', function () {
    app()->setLocale('ar');

    expect(Carbon::parse('2026-10-17')->translatedFormat('j F Y'))->toBe('17 تشرين الأول 2026')
        ->and(Carbon::parse('2026-01-05')->translatedFormat('F'))->toBe('كانون الثاني');
});

it('switches back to English month names', function () {
    app()->setLocale('ar');
    app()->setLocale('en');

    expect(Carbon::parse('2026-10-17')->translatedFormat('F'))->toBe('October');
});

it('formats document dates in Arabic and tables as dd/mm/yyyy', function () {
    expect(ArabicDates::long(Carbon::parse('2026-10-17'), 'ar'))->toBe('17 تشرين الأول 2026')
        ->and(ArabicDates::long(Carbon::parse('2026-10-17'), 'en'))->toBe('17 October 2026')
        ->and(ArabicDates::short(Carbon::parse('2026-10-07')))->toBe('07/10/2026');
});

it('gives Filament tables the dd/mm/yyyy default', function () {
    app()->setLocale('ar');

    $table = Table::make(new class extends Component implements HasTable
    {
        use InteractsWithActions;
        use InteractsWithSchemas;
        use InteractsWithTable;

        public function render(): string
        {
            return '<div></div>';
        }
    });

    expect($table->getDefaultDateDisplayFormat())->toBe('d/m/Y')
        ->and($table->getDefaultDateTimeDisplayFormat())->toBe('d/m/Y H:i');
});

it('keeps Latin digits by default and converts on request', function () {
    expect(Digits::format('3.765 د.أ'))->toBe('3.765 د.أ')
        ->and(Digits::toArabicIndic('17/10/2026 3.765'))->toBe('١٧/١٠/٢٠٢٦ ٣٫٧٦٥');

    config(['eunoia-arabic.digits' => 'arabic']);

    expect(Digits::format('12'))->toBe('١٢');
});
