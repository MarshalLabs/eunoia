<?php

namespace Eunoia\Core\Support;

use Illuminate\Support\Facades\Schema;
use Throwable;
use Webkul\PluginManager\Models\Plugin;

/**
 * Which plugins are installed, cached per application instance (upstream's
 * Package::isPluginInstalled() caches in a function-level static for the whole process).
 */
class InstalledPlugins
{
    /**
     * @var array<string, bool>|null
     */
    protected ?array $installed = null;

    public function has(string $name): bool
    {
        return $this->all()[$name] ?? false;
    }

    public function forget(): void
    {
        $this->installed = null;
    }

    /**
     * @return array<string, bool>
     */
    protected function all(): array
    {
        if ($this->installed !== null) {
            return $this->installed;
        }

        try {
            if (! Schema::hasTable('plugins')) {
                return $this->installed = [];
            }

            return $this->installed = Plugin::query()->pluck('is_installed', 'name')->map(fn ($value): bool => (bool) $value)->all();
        } catch (Throwable) {
            return $this->installed = [];
        }
    }
}
