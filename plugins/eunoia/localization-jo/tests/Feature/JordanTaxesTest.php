<?php

use Eunoia\LocalizationJo\Database\Seeders\JordanTaxSeeder;
use Eunoia\LocalizationJo\Enums\JoFotaraTaxCategory;
use Eunoia\LocalizationJo\Enums\JordanTaxKind;
use Eunoia\LocalizationJo\Models\JordanTaxProfile;
use Illuminate\Support\Facades\Artisan;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Tax;
use Webkul\Account\Settings\TaxesSettings;
use Webkul\PluginManager\Package;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    Package::syncPostgresSequences();
    Artisan::call('eunoia-localization-jo:install', ['--no-interaction' => true]);

    $this->company = Company::query()->first();
    $this->company->update(['country_id' => Country::query()->where('code', 'JO')->value('id')]);
});

function jordanTax(Company $company, JordanTaxKind $kind, TypeTaxUse $use): ?Tax
{
    return Tax::query()
        ->where('company_id', $company->id)
        ->where('type_tax_use', $use)
        ->whereIn('id', JordanTaxProfile::query()->where('kind', $kind)->pluck('tax_id'))
        ->first();
}

it('creates the Jordanian sales and purchase tax set (JO-02)', function () {
    app(JordanTaxSeeder::class)->run();

    foreach ([TypeTaxUse::SALE, TypeTaxUse::PURCHASE] as $use) {
        expect((float) jordanTax($this->company, JordanTaxKind::General, $use)->amount)->toBe(16.0)
            ->and((float) jordanTax($this->company, JordanTaxKind::Reduced4, $use)->amount)->toBe(4.0)
            ->and((float) jordanTax($this->company, JordanTaxKind::Reduced1, $use)->amount)->toBe(1.0)
            ->and((float) jordanTax($this->company, JordanTaxKind::ZeroRated, $use)->amount)->toBe(0.0)
            ->and((float) jordanTax($this->company, JordanTaxKind::Exempt, $use)->amount)->toBe(0.0);
    }
});

it('maps taxes to JoFotara categories S / O / Z (JoFotara meaning, not Peppol)', function () {
    app(JordanTaxSeeder::class)->run();

    $category = fn (JordanTaxKind $kind): JoFotaraTaxCategory => JordanTaxProfile::query()
        ->where('tax_id', jordanTax($this->company, $kind, TypeTaxUse::SALE)->id)->firstOrFail()->jofotara_category;

    expect($category(JordanTaxKind::General))->toBe(JoFotaraTaxCategory::Standard)
        ->and($category(JordanTaxKind::Reduced4))->toBe(JoFotaraTaxCategory::Standard)
        ->and($category(JordanTaxKind::ZeroRated))->toBe(JoFotaraTaxCategory::ZeroRated)
        ->and($category(JordanTaxKind::Exempt))->toBe(JoFotaraTaxCategory::Exempt)
        ->and(JoFotaraTaxCategory::ZeroRated->value)->toBe('O')
        ->and(JoFotaraTaxCategory::Exempt->value)->toBe('Z');
});

it('names taxes General Sales Tax, never VAT (JO-03)', function () {
    app(JordanTaxSeeder::class)->run();

    $tax = jordanTax($this->company, JordanTaxKind::General, TypeTaxUse::SALE);

    expect($tax->name)->toBe('ضريبة المبيعات العامة 16%')
        ->and($tax->invoice_label)->toContain('ضريبة المبيعات العامة')
        ->and($tax->profile->name_en)->toBe('General Sales Tax 16%')
        ->and(Tax::query()->where('company_id', $this->company->id)->where('is_active', true)->pluck('name')->implode(' '))
        ->not->toContain('VAT')->not->toContain('القيمة المضافة');
});

it('replaces the upstream 15 % defaults', function () {
    app(JordanTaxSeeder::class)->run();

    $settings = app(TaxesSettings::class);

    expect(Tax::query()->where('company_id', $this->company->id)->where('amount', 15)->where('is_active', true)->count())->toBe(0)
        ->and($settings->account_sale_tax_id)->toBe(jordanTax($this->company, JordanTaxKind::General, TypeTaxUse::SALE)->id)
        ->and($settings->account_purchase_tax_id)->toBe(jordanTax($this->company, JordanTaxKind::General, TypeTaxUse::PURCHASE)->id);
});

it('books tax to the tax accounts with base and tax lines for invoices and refunds', function () {
    app(JordanTaxSeeder::class)->run();

    $tax = jordanTax($this->company, JordanTaxKind::General, TypeTaxUse::SALE);
    $lines = $tax->invoiceRepartitionLines->merge($tax->refundRepartitionLines);

    expect($lines)->toHaveCount(4)
        ->and($lines->where('repartition_type', 'tax')->pluck('account_id')->filter()->unique())->toHaveCount(1);
});

it('keeps exempt and zero-rated taxes at 0 % and adds an inactive special tax placeholder', function () {
    app(JordanTaxSeeder::class)->run();

    $special = jordanTax($this->company, JordanTaxKind::Special, TypeTaxUse::SALE);

    expect($special)->not->toBeNull()
        ->and($special->is_active)->toBeFalse();
});

it('is idempotent', function () {
    app(JordanTaxSeeder::class)->run();
    $count = Tax::query()->count();

    app(JordanTaxSeeder::class)->run();

    expect(Tax::query()->count())->toBe($count);
});

it('leaves non-Jordanian companies alone', function () {
    JordanTaxProfile::query()->delete();
    $this->company->update(['country_id' => Country::query()->where('code', 'PS')->value('id')]);

    app(JordanTaxSeeder::class)->run();

    expect(JordanTaxProfile::query()->count())->toBe(0);
});
