<?php

namespace Eunoia\Arabic\Translation;

use Closure;
use Illuminate\Contracts\Translation\Loader;

/**
 * Decorates Laravel's translation loader: upstream lines load first, then our glossary overrides
 * from each overrides path ({path}/{locale}/{namespace}/{group}.php) are merged on top, in order
 * (no upstream file edits).
 */
class OverlayLoader implements Loader
{
    /**
     * @param  array<int, string>  $overridesPaths
     * @param  Closure(): bool  $enabled
     */
    public function __construct(
        protected Loader $loader,
        protected array $overridesPaths,
        protected Closure $enabled,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function load($locale, $group, $namespace = null): array
    {
        $lines = $this->loader->load($locale, $group, $namespace);

        if ($group === '*' || ! ($this->enabled)()) {
            return $lines;
        }

        foreach ($this->overridesPaths as $path) {
            $file = $path.'/'.$locale.'/'.($namespace ?? '*').'/'.$group.'.php';

            if (is_file($file)) {
                $lines = array_replace_recursive($lines, require $file);
            }
        }

        return $lines;
    }

    public function addNamespace($namespace, $hint): void
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    public function addJsonPath($path): void
    {
        $this->loader->addJsonPath($path);
    }

    /**
     * @return array<string, string>
     */
    public function namespaces(): array
    {
        return $this->loader->namespaces();
    }
}
