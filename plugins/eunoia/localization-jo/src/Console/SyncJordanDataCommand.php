<?php

namespace Eunoia\LocalizationJo\Console;

use Eunoia\LocalizationJo\Database\Seeders\JordanChartOfAccountsSeeder;
use Eunoia\LocalizationJo\Database\Seeders\JordanTaxSeeder;
use Illuminate\Console\Command;

/**
 * Re-applies the Jordanian data (idempotent) after an upgrade of eunoia/localization-jo.
 */
class SyncJordanDataCommand extends Command
{
    protected $signature = 'eunoia:jo:sync';

    protected $description = 'Re-apply Jordanian chart of accounts and taxes (idempotent)';

    public function handle(): int
    {
        app(JordanChartOfAccountsSeeder::class)->run();
        $this->info(__('eunoia-localization-jo::localization.install.accounts'));

        app(JordanTaxSeeder::class)->run();
        $this->info(__('eunoia-localization-jo::localization.install.taxes'));

        return self::SUCCESS;
    }
}
