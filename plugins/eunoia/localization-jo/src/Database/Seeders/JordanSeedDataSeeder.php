<?php

namespace Eunoia\LocalizationJo\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Jordan/Palestine reference data (JO-05, U3): Arabic governorate names, Palestinian governorates,
 * optional postcode for JO/PS, Arabic names for seeded journals, payment terms and the demo company.
 * Only rows still carrying the upstream English name are renamed; the English name is kept in
 * eunoia_jo_name_translations. Idempotent.
 */
class JordanSeedDataSeeder extends Seeder
{
    /**
     * @var array<string, array{0: string, 1: string}> ISO 3166-2 code => [Arabic, English]
     */
    public const JO_GOVERNORATES = [
        'JO-AJ' => ['عجلون', 'Ajloun'], 'JO-AM' => ['عمّان', 'Amman'], 'JO-AQ' => ['العقبة', 'Aqaba'],
        'JO-AT' => ['الطفيلة', 'Tafileh'], 'JO-AZ' => ['الزرقاء', 'Zarqa'], 'JO-BA' => ['البلقاء', 'Balqa'],
        'JO-IR' => ['إربد', 'Irbid'], 'JO-JA' => ['جرش', 'Jerash'], 'JO-KA' => ['الكرك', 'Karak'],
        'JO-MA' => ['المفرق', 'Mafraq'], 'JO-MD' => ['مادبا', 'Madaba'], 'JO-MN' => ['معان', 'Maan'],
    ];

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    public const PS_GOVERNORATES = [
        'PS-JEN' => ['جنين', 'Jenin'], 'PS-TBS' => ['طوباس', 'Tubas'], 'PS-TKM' => ['طولكرم', 'Tulkarm'],
        'PS-NBS' => ['نابلس', 'Nablus'], 'PS-QQA' => ['قلقيلية', 'Qalqilya'], 'PS-SLT' => ['سلفيت', 'Salfit'],
        'PS-RBH' => ['رام الله والبيرة', 'Ramallah and Al-Bireh'], 'PS-JRH' => ['أريحا والأغوار', 'Jericho and Al Aghwar'],
        'PS-JEM' => ['القدس', 'Jerusalem'], 'PS-BTH' => ['بيت لحم', 'Bethlehem'], 'PS-HBN' => ['الخليل', 'Hebron'],
        'PS-NGZ' => ['شمال غزة', 'North Gaza'], 'PS-GZA' => ['غزة', 'Gaza'], 'PS-DEB' => ['دير البلح', 'Deir al-Balah'],
        'PS-KYS' => ['خان يونس', 'Khan Yunis'], 'PS-RFH' => ['رفح', 'Rafah'],
    ];

    /**
     * @var array<string, array{0: string, 1: string}> journal code => [Arabic, English]
     */
    public const JOURNALS = [
        'INV'  => ['فواتير العملاء', 'Customer Invoices'],
        'BILL' => ['فواتير الموردين', 'Vendor Bills'],
        'MISC' => ['قيود متنوعة', 'Miscellaneous Operations'],
        'EXCH' => ['فروقات العملة', 'Exchange Difference'],
        'BANK' => ['حركات البنك', 'Bank Transactions'],
        'CASH' => ['حركات الصندوق', 'Cash Transactions'],
    ];

    /**
     * @var array<string, string> English => Arabic
     */
    public const PAYMENT_TERMS = [
        'Immediate Payment'               => 'دفع فوري',
        '15 Days'                         => '15 يوماً',
        '21 Days'                         => '21 يوماً',
        '30 Days'                         => '30 يوماً',
        '45 Days'                         => '45 يوماً',
        'End of Following Month'          => 'نهاية الشهر التالي',
        '10 Days after End of Next Month' => '10 أيام بعد نهاية الشهر التالي',
        '30% Now, Balance 60 Days'        => '30% الآن والباقي خلال 60 يوماً',
        '2/7 Net 30'                      => 'خصم 2% خلال 7 أيام، الصافي خلال 30 يوماً',
        '90 Days, on the 10th'            => '90 يوماً، في اليوم العاشر',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $countries = DB::table('countries')->whereIn('code', ['JO', 'PS'])->pluck('id', 'code');

            DB::table('countries')->whereIn('code', ['JO', 'PS'])->update(['zip_required' => false]);

            foreach (static::JO_GOVERNORATES as $code => [$arabic, $english]) {
                $this->renameWhere('states', ['code' => $code], $arabic, $english);
            }

            if ($countries->has('PS')) {
                foreach (static::PS_GOVERNORATES as $code => [$arabic, $english]) {
                    if (DB::table('states')->where('code', $code)->doesntExist()) {
                        DB::table('states')->insert(['country_id' => $countries['PS'], 'name' => $english, 'code' => $code, 'created_at' => now(), 'updated_at' => now()]);
                    }

                    $this->renameWhere('states', ['code' => $code], $arabic, $english);
                }
            }

            foreach (static::JOURNALS as $code => [$arabic, $english]) {
                $this->renameWhere('accounts_journals', ['code' => $code], $arabic, $english);
            }

            foreach (static::PAYMENT_TERMS as $english => $arabic) {
                $this->renameWhere('accounts_payment_terms', [], $arabic, $english);
            }

            $this->renameWhere('companies', [], 'شركتي', 'My Company');
        });
    }

    /**
     * Renames rows that still have the upstream English name, remembering the English name.
     *
     * @param  array<string, mixed>  $where
     */
    protected function renameWhere(string $table, array $where, string $arabic, string $english): void
    {
        $query = DB::table($table)->where($where)->where('name', $english);

        foreach ($query->pluck('id') as $id) {
            DB::table($table)->where('id', $id)->update(['name' => $arabic]);

            DB::table('eunoia_jo_name_translations')->updateOrInsert(
                ['table_name' => $table, 'record_id' => $id],
                ['name_en' => $english, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
