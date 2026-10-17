<?php

namespace Eunoia\Core;

use Illuminate\Support\ServiceProvider;

/**
 * Registers every plugins/eunoia/* package provider from its composer.json
 * (extra.laravel.providers), so new Eunoia plugins never need another edit of
 * bootstrap/providers.php (patch P8 adds only this class there).
 */
class EunoiaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (static::discoverProviders(base_path('plugins/eunoia')) as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * @return array<int, class-string>
     */
    public static function discoverProviders(string $root): array
    {
        $files = glob($root.'/*/composer.json') ?: [];

        usort($files, fn (string $a, string $b): int => static::sortKey($a) <=> static::sortKey($b));

        $providers = [];

        foreach ($files as $file) {
            $composer = json_decode((string) file_get_contents($file), true) ?: [];

            foreach ($composer['extra']['laravel']['providers'] ?? [] as $provider) {
                if (class_exists($provider)) {
                    $providers[] = $provider;
                }
            }
        }

        return $providers;
    }

    /**
     * Core first, then alphabetical.
     */
    protected static function sortKey(string $composerFile): string
    {
        $package = basename(dirname($composerFile));

        return ($package === 'core' ? '0' : '1').$package;
    }
}
