<?php

use Eunoia\LocalizationJo\Database\Seeders\JordanChartOfAccountsSeeder;
use Eunoia\LocalizationJo\Enums\JordanTaxKind;
use Eunoia\LocalizationJo\Models\JordanAccountProfile;
use Eunoia\LocalizationJo\Models\JordanTaxProfile;
use Illuminate\Support\Facades\Artisan;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Tax;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    /** Upstream caches installed plugins statically; DB rolls back between tests, so refresh it (as upstream tests do). */
    Package::$plugins = Plugin::all()->keyBy('name');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    Package::syncPostgresSequences();
    Company::query()->first()->update(['country_id' => Country::query()->where('code', 'JO')->value('id')]);
    Artisan::call('eunoia-localization-jo:install', ['--no-interaction' => true]);
});

it('names upstream accounts in Arabic and keeps the English name (JO-04)', function () {
    $sales = Account::query()->where('code', '400000')->firstOrFail();

    expect($sales->name)->toBe('إيرادات المبيعات')
        ->and(JordanAccountProfile::query()->where('account_id', $sales->id)->value('name_en'))->toBe('Product Sales');
});

it('adds Jordan-specific accounts with the right types', function () {
    expect(Account::query()->where('code', '253000')->value('account_type'))->toBe(AccountType::LIABILITY_CURRENT)
        ->and(Account::query()->where('code', '133000')->value('account_type'))->toBe(AccountType::ASSET_CURRENT)
        ->and(Account::query()->where('code', '255000')->value('name'))->toBe('اشتراكات الضمان الاجتماعي المستحقة')
        ->and(Account::query()->where('code', '101405')->value('account_type'))->toBe(AccountType::ASSET_CASH);
});

it('books Jordanian GST to output and input GST accounts', function () {
    $taxAccount = function (TypeTaxUse $use): ?string {
        $tax = Tax::query()->where('type_tax_use', $use)
            ->whereIn('id', JordanTaxProfile::query()->where('kind', JordanTaxKind::General)->select('tax_id'))
            ->firstOrFail();

        return Account::query()->whereKey($tax->invoiceRepartitionLines()->where('repartition_type', 'tax')->value('account_id'))->value('code');
    };

    expect($taxAccount(TypeTaxUse::SALE))->toBe(JordanChartOfAccountsSeeder::OUTPUT_GST)
        ->and($taxAccount(TypeTaxUse::PURCHASE))->toBe(JordanChartOfAccountsSeeder::INPUT_GST);
});

it('does not rename an account the user renamed', function () {
    Account::query()->where('code', '612000')->update(['name' => 'إيجار المحل - العبدلي']);

    app(JordanChartOfAccountsSeeder::class)->run();

    expect(Account::query()->where('code', '612000')->value('name'))->toBe('إيجار المحل - العبدلي');
});

it('is idempotent', function () {
    $count = Account::query()->count();

    app(JordanChartOfAccountsSeeder::class)->run();

    expect(Account::query()->count())->toBe($count)
        ->and(Account::query()->where('code', '253000')->count())->toBe(1);
});
