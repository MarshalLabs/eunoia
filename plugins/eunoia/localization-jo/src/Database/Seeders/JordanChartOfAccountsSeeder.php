<?php

namespace Eunoia\LocalizationJo\Database\Seeders;

use Eunoia\LocalizationJo\Models\JordanAccountProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Models\Account;

/**
 * Jordanian SME chart of accounts (JO-04) — NEEDS ACCOUNTANT sign-off before production.
 *
 * Keeps upstream codes (journals, settings and POS reference them), gives every upstream account an Arabic
 * name (English kept in eunoia_jo_account_profiles) and adds Jordan-specific accounts. An account whose name
 * was changed by the user is not renamed. Idempotent.
 */
class JordanChartOfAccountsSeeder extends Seeder
{
    /**
     * Upstream code => [Arabic name, upstream English name].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const UPSTREAM = [
        '101000' => ['الموجودات المتداولة', 'Current Assets'],
        '101300' => ['ذمم مدينة (نقاط البيع)', 'Account Receivable (PoS)'],
        '101401' => ['البنك', 'Bank'],
        '101402' => ['حساب البنك المعلّق', 'Bank Suspense Account'],
        '101403' => ['مقبوضات قيد التحصيل', 'Outstanding Receipts'],
        '101404' => ['مدفوعات قيد الصرف', 'Outstanding Payments'],
        '101501' => ['الصندوق (النقدية)', 'Cash'],
        '101701' => ['تحويلات النقدية الداخلية', 'Liquidity Transfer'],
        '110100' => ['المخزون', 'Stock Valuation'],
        '110200' => ['مخزون وسيط (مستلم)', 'Stock Interim (Received)'],
        '110300' => ['مخزون وسيط (مُسلَّم)', 'Stock Interim (Delivered)'],
        '110400' => ['تكلفة الإنتاج', 'Cost of Production'],
        '110500' => ['إنتاج تحت التشغيل', 'Work in Progress'],
        '121000' => ['ذمم العملاء المدينة', 'Account Receivable'],
        '121100' => ['بضاعة قيد الاستلام', 'Products to receive'],
        '128000' => ['مصاريف مدفوعة مقدماً', 'Prepaid Expenses'],
        '131000' => ['ضريبة مدفوعة', 'Tax Paid'],
        '132000' => ['ضريبة مستردة', 'Tax Receivable'],
        '141000' => ['دفعات مقدمة', 'Prepayments'],
        '151000' => ['الموجودات الثابتة', 'Fixed Asset'],
        '191000' => ['الموجودات غير المتداولة', 'Non-current assets'],
        '201000' => ['المطلوبات المتداولة', 'Current Liabilities'],
        '201100' => ['بطاقة ائتمان', 'Credit Card'],
        '211000' => ['ذمم الموردين الدائنة', 'Account Payable'],
        '211100' => ['فواتير موردين قيد الاستلام', 'Bills to receive'],
        '212000' => ['إيرادات مؤجلة', 'Deferred Revenue'],
        '230000' => ['رواتب مستحقة', 'Salary Payable'],
        '230100' => ['استقطاعات رواتب الموظفين', 'Employee Payroll Taxes'],
        '230200' => ['التزامات صاحب العمل على الرواتب', 'Employer Payroll Taxes'],
        '251000' => ['ضريبة مقبوضة', 'Tax Received'],
        '252000' => ['ضريبة مستحقة الدفع', 'Tax Payable'],
        '291000' => ['المطلوبات غير المتداولة', 'Non-current Liabilities'],
        '301000' => ['رأس المال', 'Capital'],
        '302000' => ['أرباح موزعة', 'Dividends'],
        '400000' => ['إيرادات المبيعات', 'Product Sales'],
        '441000' => ['أرباح فروقات العملة', 'Foreign Exchange Gain'],
        '442000' => ['زيادة نقدية في الصندوق', 'Cash Difference Gain'],
        '443000' => ['خصم نقدي ممنوح', 'Cash Discount Loss'],
        '450000' => ['إيرادات أخرى', 'Other Income'],
        '500000' => ['تكلفة البضاعة المباعة', 'Cost of Goods Sold'],
        '600000' => ['المصاريف', 'Expenses'],
        '611000' => ['شراء معدات', 'Purchase of Equipments'],
        '612000' => ['الإيجار', 'Rent'],
        '620000' => ['عمولات بنكية', 'Bank Fees'],
        '630000' => ['رواتب وأجور', 'Salary Expenses'],
        '641000' => ['خسائر فروقات العملة', 'Foreign Exchange Loss'],
        '642000' => ['عجز نقدي في الصندوق', 'Cash Difference Loss'],
        '643000' => ['خصم نقدي مكتسب', 'Cash Discount Gain'],
        '961000' => ['مصاريف البحث والتطوير', 'RD Expenses'],
        '962000' => ['مصاريف البيع والتسويق', 'Sales Expenses'],
        '999999' => ['أرباح وخسائر غير موزعة', 'Undistributed Profits/Losses'],
    ];

    /**
     * New Jordan-specific accounts: code => [Arabic, English, type, reconcile].
     *
     * @var array<string, array{0: string, 1: string, 2: AccountType, 3: bool}>
     */
    public const JORDAN = [
        '101405' => ['تحويلات كليك (CliQ)', 'CliQ transfers', AccountType::ASSET_CASH, false],
        '110110' => ['مخزون الزيوت العطرية', 'Perfume oils stock', AccountType::ASSET_CURRENT, false],
        '110120' => ['مخزون الكحول والمواد الخام', 'Alcohol and raw materials stock', AccountType::ASSET_CURRENT, false],
        '110130' => ['مخزون العبوات والزجاجات', 'Bottles and packaging stock', AccountType::ASSET_CURRENT, false],
        '133000' => ['ضريبة المبيعات العامة على المشتريات (مدخلات)', 'Input General Sales Tax', AccountType::ASSET_CURRENT, false],
        '253000' => ['ضريبة المبيعات العامة المستحقة (مخرجات)', 'Output General Sales Tax payable', AccountType::LIABILITY_CURRENT, false],
        '254000' => ['ضريبة الدخل المقتطعة من الرواتب', 'Income tax withheld from salaries', AccountType::LIABILITY_CURRENT, false],
        '255000' => ['اشتراكات الضمان الاجتماعي المستحقة', 'Social Security contributions payable', AccountType::LIABILITY_CURRENT, false],
        '401000' => ['إيرادات مبيعات العطور', 'Perfume sales revenue', AccountType::INCOME, false],
        '402000' => ['إيرادات التركيبات الخاصة', 'Custom blend revenue', AccountType::INCOME, false],
        '613000' => ['مصاريف الكهرباء والمياه', 'Electricity and water', AccountType::EXPENSE, false],
        '614000' => ['عمولات أجهزة الدفع الإلكتروني', 'Card terminal fees', AccountType::EXPENSE, false],
        '631000' => ['حصة صاحب العمل في الضمان الاجتماعي', 'Employer Social Security share', AccountType::EXPENSE, false],
    ];

    public const OUTPUT_GST = '253000';

    public const INPUT_GST = '133000';

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (static::UPSTREAM as $code => [$arabic, $english]) {
                $account = Account::query()->where('code', $code)->first();

                if (! $account) {
                    continue;
                }

                if ($account->name === $english) {
                    $account->forceFill(['name' => $arabic])->save();
                }

                JordanAccountProfile::query()->updateOrCreate(['account_id' => $account->id], ['name_en' => $english, 'is_jordan_specific' => false]);
            }

            foreach (static::JORDAN as $code => [$arabic, $english, $type, $reconcile]) {
                $account = Account::query()->firstOrNew(['code' => $code]);

                if (! $account->exists) {
                    $account->forceFill([
                        'name'         => $arabic,
                        'account_type' => $type,
                        'reconcile'    => $reconcile,
                        'deprecated'   => false,
                        'non_trade'    => false,
                        'creator_id'   => DB::table('users')->orderBy('id')->value('id'),
                    ])->save();
                }

                JordanAccountProfile::query()->updateOrCreate(['account_id' => $account->id], ['name_en' => $english, 'is_jordan_specific' => true]);
            }
        });
    }
}
