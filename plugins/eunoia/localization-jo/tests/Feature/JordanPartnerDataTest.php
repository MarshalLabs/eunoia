<?php

use Eunoia\Core\Support\InstalledPlugins;
use Eunoia\LocalizationJo\Enums\IdentityType;
use Eunoia\LocalizationJo\LocalizationJoServiceProvider;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Webkul\Partner\Models\Partner;
use Webkul\PluginManager\Package;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Country;

require_once dirname(__DIR__, 5).'/plugins/webkul/support/tests/Helpers/TestBootstrapHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    Package::syncPostgresSequences();
    Company::query()->first()->update(['country_id' => Country::query()->where('code', 'JO')->value('id')]);
    Artisan::call('eunoia-localization-jo:install', ['--no-interaction' => true]);
    app(InstalledPlugins::class)->forget();
    app()->getProvider(LocalizationJoServiceProvider::class)->packageBooted();

    $this->jo = Country::query()->where('code', 'JO')->firstOrFail();
});

it('names Jordanian governorates in Arabic and keeps the English name (JO-05)', function () {
    expect(DB::table('states')->where('code', 'JO-AM')->value('name'))->toBe('عمّان')
        ->and(DB::table('eunoia_jo_name_translations')->where('table_name', 'states')->where('name_en', 'Amman')->exists())->toBeTrue();
});

it('adds the 16 Palestinian governorates (F16)', function () {
    $ps = Country::query()->where('code', 'PS')->firstOrFail();

    expect(DB::table('states')->where('country_id', $ps->id)->count())->toBe(16)
        ->and(DB::table('states')->where('code', 'PS-RBH')->value('name'))->toBe('رام الله والبيرة');
});

it('makes the postcode optional for Jordan and Palestine', function () {
    expect(DB::table('countries')->whereIn('code', ['JO', 'PS'])->pluck('zip_required')->unique()->all())->toBe([false]);
});

it('translates seeded journals, payment terms and the company (U3)', function () {
    expect(DB::table('accounts_journals')->where('code', 'INV')->value('name'))->toBe('فواتير العملاء')
        ->and(DB::table('accounts_payment_terms')->where('name', 'دفع فوري')->exists())->toBeTrue()
        ->and(DB::table('companies')->where('name', 'My Company')->exists())->toBeFalse();
});

it('stores partner phones in E.164 (JO-08)', function () {
    $partner = Partner::create(['name' => 'رنا الخطيب', 'account_type' => 'individual', 'country_id' => $this->jo->id, 'phone' => '0791234567', 'mobile' => '079 765 4321']);

    expect($partner->refresh()->phone)->toBe('+962791234567')
        ->and($partner->mobile)->toBe('+962797654321');
});

it('validates and cleans partner identities (JO-06)', function () {
    $partner = Partner::create(['name' => 'متجر العطور', 'account_type' => 'company', 'country_id' => $this->jo->id]);

    $identity = $partner->identities()->create(['type' => IdentityType::Tin, 'value' => '١٢٣ ٤٥٦ ٧٨']);

    expect($identity->value)->toBe('12345678')
        ->and($partner->identities()->count())->toBe(1);

    $partner->identities()->create(['type' => IdentityType::NationalId, 'value' => '12345']);
})->throws(InvalidArgumentException::class);

it('checks identity formats', function (IdentityType $type, string $value, bool $valid) {
    expect($type->isValid($value))->toBe($valid);
})->with([
    [IdentityType::Tin, '12345678', true],
    [IdentityType::Tin, '123456789012', true],
    [IdentityType::Tin, '1234567', false],
    [IdentityType::Tin, '12A45678', false],
    [IdentityType::NationalId, '9851012345', true],
    [IdentityType::NationalId, '985101234', false],
    [IdentityType::CommercialRegistration, '12345/2020', true],
    [IdentityType::Passport, 'N123456', true],
]);

it('allows only one identity of each type per partner', function () {
    $partner = Partner::create(['name' => 'عميل', 'account_type' => 'individual', 'country_id' => $this->jo->id]);
    $partner->identities()->create(['type' => IdentityType::Tin, 'value' => '12345678']);
    $partner->identities()->create(['type' => IdentityType::Tin, 'value' => '87654321']);
})->throws(UniqueConstraintViolationException::class);
