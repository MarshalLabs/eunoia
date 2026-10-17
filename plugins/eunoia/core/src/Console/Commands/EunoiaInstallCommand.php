<?php

namespace Eunoia\Core\Console\Commands;

use Eunoia\Core\Support\CountryProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\PluginManager\Models\Plugin;

/**
 * Meta installer: installs the upstream prerequisites and every available Eunoia plugin for a country, in order.
 */
class EunoiaInstallCommand extends Command
{
    protected $signature = 'eunoia:install
        {--country=JO : Country profile (JO)}
        {--dry-run : Only list what would be installed}';

    protected $description = 'Install Eunoia plugins for a country (e.g. --country=JO)';

    public function handle(): int
    {
        $country = strtoupper((string) $this->option('country'));

        if (! CountryProfile::supports($country)) {
            $this->error(__('eunoia-core::install.unsupported_country', [
                'country'   => $country,
                'supported' => implode(', ', CountryProfile::countries()),
            ]));

            return self::FAILURE;
        }

        if (! Schema::hasTable('plugins')) {
            $this->error(__('eunoia-core::install.erp_missing', ['command' => 'php artisan erp:install --country='.$country]));

            return self::FAILURE;
        }

        $upstream = CountryProfile::upstreamPlugins($country);
        $eunoia = array_values(array_filter(CountryProfile::eunoiaPlugins($country), fn (string $name): bool => $this->hasInstallCommand($name)));
        $missing = array_values(array_diff(CountryProfile::eunoiaPlugins($country), $eunoia));

        $this->info(__('eunoia-core::install.plan', ['country' => $country]));
        $this->line('  '.__('eunoia-core::install.upstream_label').': '.implode(', ', $upstream));
        $this->line('  '.__('eunoia-core::install.eunoia_label').': '.implode(', ', $eunoia));

        if ($missing) {
            $this->line('  '.__('eunoia-core::install.not_available').': '.implode(', ', $missing));
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        /** Upstream install commands prompt ("Reseed/Skip") unless the shared input is non-interactive. */
        $this->input->setInteractive(false);

        foreach ([...$upstream, ...$eunoia] as $name) {
            if ($this->isInstalled($name)) {
                $this->line(__('eunoia-core::install.already_installed', ['plugin' => $name]));

                continue;
            }

            $this->info(__('eunoia-core::install.installing', ['plugin' => $name]));

            try {
                $exitCode = Artisan::call("{$name}:install", ['--no-interaction' => true], $this->output);
            } catch (Throwable $e) {
                $this->error(__('eunoia-core::install.failed', ['plugin' => $name]).' '.$e->getMessage());

                return self::FAILURE;
            }

            /** Upstream install commands return null on success and on some failures, so also check the result. */
            if (($exitCode !== self::SUCCESS) || ! $this->isInstalled($name)) {
                $this->error(__('eunoia-core::install.failed', ['plugin' => $name]));

                return self::FAILURE;
            }
        }

        $this->info(__('eunoia-core::install.done', ['command' => 'php artisan eunoia:doctor']));

        return self::SUCCESS;
    }

    protected function hasInstallCommand(string $plugin): bool
    {
        return array_key_exists("{$plugin}:install", Artisan::all());
    }

    protected function isInstalled(string $plugin): bool
    {
        return (bool) Plugin::query()->where('name', $plugin)->value('is_installed');
    }
}
