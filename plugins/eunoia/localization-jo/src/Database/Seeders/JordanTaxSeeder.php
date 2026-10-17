<?php

namespace Eunoia\LocalizationJo\Database\Seeders;

use Eunoia\LocalizationJo\Enums\JordanTaxKind;
use Eunoia\LocalizationJo\Models\JordanTaxProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Account\Enums\AmountType;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Tax;
use Webkul\Account\Models\TaxGroup;
use Webkul\Account\Settings\TaxesSettings;
use Webkul\Support\Models\Company;

/**
 * Jordanian tax set for every company whose country is JO (JO-02, JO-03). Idempotent:
 * existing Eunoia taxes are matched through eunoia_jo_tax_profiles and updated in place.
 * Upstream's generic 15 % taxes are deactivated, never deleted (they may be on documents).
 */
class JordanTaxSeeder extends Seeder
{
    public function run(): void
    {
        Company::query()
            ->whereHas('country', fn ($query) => $query->where('code', 'JO'))
            ->each(fn (Company $company) => DB::transaction(fn () => $this->seedCompany($company)));
    }

    protected function seedCompany(Company $company): void
    {
        $group = TaxGroup::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'ضريبة المبيعات العامة'],
            ['country_id' => $company->country_id, 'sort' => 1, 'creator_id' => $this->userId()],
        );

        $accounts = [
            TypeTaxUse::SALE->value     => $this->taxAccountId($company, [JordanChartOfAccountsSeeder::OUTPUT_GST, '251000']),
            TypeTaxUse::PURCHASE->value => $this->taxAccountId($company, [JordanChartOfAccountsSeeder::INPUT_GST, '131000']),
        ];

        $defaults = [];

        foreach ([TypeTaxUse::SALE, TypeTaxUse::PURCHASE] as $use) {
            foreach (JordanTaxKind::cases() as $sort => $kind) {
                $tax = $this->upsertTax($company, $group, $use, $kind, $sort + 1, $accounts[$use->value]);

                if ($kind === JordanTaxKind::General) {
                    $defaults[$use->value] = $tax->id;
                }
            }
        }

        /** Only upstream's seeded "15 %" taxes, never a 15 % tax the user created. */
        Tax::query()
            ->where('company_id', $company->id)
            ->where('name', '15 %')
            ->where('amount', 15)
            ->whereNotIn('id', JordanTaxProfile::query()->select('tax_id'))
            ->update(['is_active' => false]);

        $this->writeCompanySettings($company, [
            'account_sale_tax_id'       => $defaults[TypeTaxUse::SALE->value],
            'account_purchase_tax_id'   => $defaults[TypeTaxUse::PURCHASE->value],
            'account_fiscal_country_id' => $company->country_id,
        ]);
    }

    /**
     * TaxesSettings are stored per company (CompanyAwareSettingsRepository); write this company's rows directly,
     * because seeders run without a current company.
     *
     * @param  array<string, int|null>  $values
     */
    protected function writeCompanySettings(Company $company, array $values): void
    {
        $hasCompanyColumn = Schema::hasColumn('settings', 'company_id');

        foreach ($values as $name => $value) {
            $match = ['group' => 'accounts_taxes', 'name' => $name] + ($hasCompanyColumn ? ['company_id' => $company->id] : []);

            DB::table('settings')->updateOrInsert($match, ['payload' => json_encode($value), 'locked' => false, 'updated_at' => now()]);
        }

        app()->forgetInstance(TaxesSettings::class);
    }

    protected function upsertTax(Company $company, TaxGroup $group, TypeTaxUse $use, JordanTaxKind $kind, int $sort, ?int $accountId): Tax
    {
        $tax = Tax::query()
            ->where('company_id', $company->id)
            ->where('type_tax_use', $use)
            ->whereIn('id', JordanTaxProfile::query()->where('kind', $kind)->select('tax_id'))
            ->first();

        if ($tax) {
            /** Re-runs keep the user's changes (name, label, amount, group, active flag, accounts). */
            JordanTaxProfile::query()->updateOrCreate(
                ['tax_id' => $tax->id],
                ['kind' => $kind, 'jofotara_category' => $kind->category(), 'name_en' => $kind->nameEn()],
            );

            if ($accountId) {
                DB::table('accounts_tax_partition_lines')
                    ->where('tax_id', $tax->id)
                    ->where('repartition_type', 'tax')
                    ->whereIn('account_id', $this->upstreamTaxAccountIds($company))
                    ->update(['account_id' => $accountId, 'updated_at' => now()]);
            }

            return $tax;
        }

        $tax = new Tax;

        $tax->forceFill([
            'company_id'          => $company->id,
            'tax_group_id'        => $group->id,
            'country_id'          => $company->country_id,
            'creator_id'          => $this->userId(),
            'type_tax_use'        => $use,
            'amount_type'         => AmountType::PERCENT,
            'tax_exigibility'     => 'on_invoice',
            'name'                => $kind->nameAr(),
            'invoice_label'       => $kind->nameAr(),
            'amount'              => $kind->rate(),
            'is_active'           => $kind->isActiveByDefault(),
            'include_base_amount' => false,
            'is_base_affected'    => false,
            'sort'                => $sort,
        ])->save();

        JordanTaxProfile::query()->updateOrCreate(
            ['tax_id' => $tax->id],
            ['kind' => $kind, 'jofotara_category' => $kind->category(), 'name_en' => $kind->nameEn()],
        );

        $this->createRepartitionLines($tax, $accountId);

        return $tax;
    }

    protected function createRepartitionLines(Tax $tax, ?int $accountId): void
    {
        foreach (['invoice', 'refund'] as $documentType) {
            foreach ([['base', null, null, false, 1], ['tax', $accountId, 100.0, true, 2]] as [$type, $account, $percent, $closing, $sort]) {
                DB::table('accounts_tax_partition_lines')->insert([
                    'repartition_type'   => $type,
                    'document_type'      => $documentType,
                    'use_in_tax_closing' => $closing,
                    'factor'             => 1.0,
                    'factor_percent'     => $percent,
                    'tax_id'             => $tax->id,
                    'account_id'         => $account,
                    'sort'               => $sort,
                    'company_id'         => $tax->company_id,
                    'creator_id'         => $tax->creator_id,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }
    }

    /**
     * First account (owned by or shared with the company) among the codes: Jordanian GST accounts, then upstream.
     *
     * @param  array<int, string>  $codes
     */
    protected function taxAccountId(Company $company, array $codes): ?int
    {
        return Account::withoutGlobalScopes()
            ->where(owned_by_company($company->id))
            ->whereIn('code', $codes)
            ->orderByRaw('case when code = ? then 0 else 1 end', [$codes[0]])
            ->value('id');
    }

    /**
     * @return array<int, int>
     */
    protected function upstreamTaxAccountIds(Company $company): array
    {
        return Account::withoutGlobalScopes()->where(owned_by_company($company->id))->whereIn('code', ['251000', '131000'])->pluck('id')->all();
    }

    protected function userId(): ?int
    {
        return DB::table('users')->orderBy('id')->value('id');
    }
}
