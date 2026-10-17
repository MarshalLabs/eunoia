<?php

namespace Eunoia\LocalizationJo\Database\Seeders;

use Eunoia\LocalizationJo\Enums\JordanTaxKind;
use Eunoia\LocalizationJo\Models\JordanTaxProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
            TypeTaxUse::SALE->value     => $this->taxAccountId([JordanChartOfAccountsSeeder::OUTPUT_GST, '251000'], TypeTaxUse::SALE),
            TypeTaxUse::PURCHASE->value => $this->taxAccountId([JordanChartOfAccountsSeeder::INPUT_GST, '131000'], TypeTaxUse::PURCHASE),
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

        Tax::query()
            ->where('company_id', $company->id)
            ->where('amount', 15)
            ->whereNotIn('id', JordanTaxProfile::query()->select('tax_id'))
            ->update(['is_active' => false]);

        $settings = app(TaxesSettings::class);
        $settings->account_sale_tax_id = $defaults[TypeTaxUse::SALE->value];
        $settings->account_purchase_tax_id = $defaults[TypeTaxUse::PURCHASE->value];
        $settings->save();
    }

    protected function upsertTax(Company $company, TaxGroup $group, TypeTaxUse $use, JordanTaxKind $kind, int $sort, ?int $accountId): Tax
    {
        $tax = Tax::query()
            ->where('company_id', $company->id)
            ->where('type_tax_use', $use)
            ->whereIn('id', JordanTaxProfile::query()->where('kind', $kind)->select('tax_id'))
            ->first() ?? new Tax;

        $tax->forceFill([
            'company_id'          => $company->id,
            'tax_group_id'        => $group->id,
            'country_id'          => $company->country_id,
            'creator_id'          => $tax->creator_id ?? $this->userId(),
            'type_tax_use'        => $use,
            'amount_type'         => AmountType::PERCENT,
            'tax_exigibility'     => 'on_invoice',
            'name'                => $kind->nameAr(),
            'invoice_label'       => $kind->nameAr(),
            'amount'              => $kind->rate(),
            'is_active'           => $tax->exists ? $tax->is_active : $kind->isActiveByDefault(),
            'include_base_amount' => false,
            'is_base_affected'    => false,
            'sort'                => $sort,
        ])->save();

        JordanTaxProfile::query()->updateOrCreate(
            ['tax_id' => $tax->id],
            ['kind' => $kind, 'jofotara_category' => $kind->category(), 'name_en' => $kind->nameEn()],
        );

        if ($tax->invoiceRepartitionLines()->doesntExist()) {
            $this->createRepartitionLines($tax, $accountId);
        } elseif ($accountId) {
            DB::table('accounts_tax_partition_lines')
                ->where('tax_id', $tax->id)
                ->where('repartition_type', 'tax')
                ->update(['account_id' => $accountId, 'updated_at' => now()]);
        }

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
     * First existing account among the codes (Jordanian GST accounts from E1-08, then upstream 251000/131000).
     *
     * @param  array<int, string>  $codes
     */
    protected function taxAccountId(array $codes, TypeTaxUse $use): ?int
    {
        return Account::query()->whereIn('code', $codes)->orderByRaw('case when code = ? then 0 else 1 end', [$codes[0]])->value('id')
            ?? DB::table('accounts_tax_partition_lines')
                ->join('accounts_taxes', 'accounts_taxes.id', '=', 'accounts_tax_partition_lines.tax_id')
                ->where('accounts_taxes.type_tax_use', $use->value)
                ->where('repartition_type', 'tax')
                ->value('account_id');
    }

    protected function userId(): ?int
    {
        return DB::table('users')->orderBy('id')->value('id');
    }
}
