<?php

/**
 * Eunoia i18n check (E0-04, architecture §9).
 *
 * For every plugins/eunoia/<package>:
 *  1. ar and en lang files have exactly the same keys.
 *  2. every __('eunoia-<package>::file.key') / trans() / @lang() used in src/ or resources/views exists in ar and en.
 *  3. ar values contain no Latin letters, except placeholders (:name) and whitelisted terms.
 *
 * JSON lang files are not used by Eunoia packages (PHP arrays only) and are not checked.
 *
 * Usage: php .github/scripts/eunoia-i18n-check.php [plugins-root]   (default: plugins/eunoia)
 */
$root = rtrim($argv[1] ?? 'plugins/eunoia', '/');

/** Latin words allowed inside Arabic strings (brands, codes, units). */
$whitelist = ['JoFotara', 'QR', 'WhatsApp', 'SMS', 'PDF', 'UBL', 'XML', 'UUID', 'ICV', 'TIN', 'ID', 'JOD', 'ILS', 'USD', 'ml', 'g', 'ISTD', 'CliQ', 'POS', 'API', 'Client-Id', 'Secret-Key', 'E.164', 'Eunoia', 'mock', 'live', 'Email', 'URL', 'A4', 'mm'];

if (! is_dir($root)) {
    echo "i18n check: {$root} not found, nothing to check\n";
    exit(0);
}

$errors = [];
$catalog = [];
$packageDirs = [];

$flatten = function (array $array, string $prefix = '') use (&$flatten): array {
    $out = [];

    foreach ($array as $key => $value) {
        $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $out += $flatten($value, $full);
        } else {
            $out[$full] = (string) $value;
        }
    }

    return $out;
};

$loadLocale = function (string $dir) use ($flatten): array {
    $keys = [];

    foreach (glob("{$dir}/*.php") ?: [] as $file) {
        $data = include $file;

        if (! is_array($data)) {
            continue;
        }

        foreach ($flatten($data) as $key => $value) {
            $keys[basename($file, '.php').'.'.$key] = $value;
        }
    }

    return $keys;
};

$latinWhitelistPattern = '/(?<![A-Za-z])('.implode('|', array_map(fn ($w) => preg_quote($w, '/'), $whitelist)).')(?![A-Za-z])/u';

foreach (glob("{$root}/*", GLOB_ONLYDIR) ?: [] as $packageDir) {
    $package = basename($packageDir);
    $langDir = "{$packageDir}/resources/lang";

    $ar = is_dir("{$langDir}/ar") ? $loadLocale("{$langDir}/ar") : [];
    $en = is_dir("{$langDir}/en") ? $loadLocale("{$langDir}/en") : [];

    foreach (array_diff_key($en, $ar) as $key => $_) {
        $errors[] = "{$package}: key '{$key}' exists in en but not in ar";
    }

    foreach (array_diff_key($ar, $en) as $key => $_) {
        $errors[] = "{$package}: key '{$key}' exists in ar but not in en";
    }

    foreach ($ar as $key => $value) {
        $stripped = preg_replace('/:[A-Za-z_]+/', '', $value);
        $stripped = preg_replace('/<[^>]+>/', '', $stripped);
        $stripped = preg_replace($latinWhitelistPattern, '', $stripped);

        if (preg_match('/\p{Arabic}[A-Za-z]/u', $value)) {
            $errors[] = "{$package}: ar value for '{$key}' has a Latin suffix on an Arabic word (e.g. \"فاتورةs\"): \"{$value}\"";
        } elseif (preg_match('/[A-Za-z]{2,}/', $stripped)) {
            $errors[] = "{$package}: ar value for '{$key}' contains Latin text: \"{$value}\"";
        }
    }

    $catalog["eunoia-{$package}"] = ['ar' => $ar, 'en' => $en];
    $packageDirs[] = $packageDir;
}

/* Second pass: every eunoia-*:: reference in any package must exist in that namespace, in ar and en. */
foreach ($packageDirs as $packageDir) {
    $package = basename($packageDir);
    $sources = [];

    foreach (['src', 'resources/views', 'database'] as $sub) {
        if (! is_dir("{$packageDir}/{$sub}")) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$packageDir}/{$sub}", FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (str_ends_with($file->getFilename(), '.php')) {
                $sources[] = $file->getPathname();
            }
        }
    }

    foreach ($sources as $file) {
        $content = file_get_contents($file);

        if (! preg_match_all("/(?:__|trans|trans_choice|@lang)\\(\\s*['\"](eunoia-[a-z0-9\\-]+)::([A-Za-z0-9_\\-.]+)['\"]/", $content, $matches, PREG_SET_ORDER)) {
            continue;
        }

        foreach ($matches as [, $namespace, $key]) {
            if (str_ends_with($key, '.')) {
                continue; // dynamic key built at runtime, e.g. '...status.'.$value
            }

            if (! isset($catalog[$namespace])) {
                $errors[] = "{$package}: {$file} uses unknown namespace '{$namespace}::{$key}'";

                continue;
            }

            foreach ($catalog[$namespace] as $locale => $keys) {
                $hasExact = isset($keys[$key]);
                $hasChildren = (bool) array_filter(array_keys($keys), fn ($k) => str_starts_with($k, $key.'.'));

                if (! $hasExact && ! $hasChildren) {
                    $errors[] = "{$package}: {$file} uses '{$namespace}::{$key}' missing in {$locale}";
                }
            }
        }
    }
}

if ($errors) {
    echo "i18n check failed:\n - ".implode("\n - ", $errors)."\n";
    exit(1);
}

echo "i18n check passed ({$root})\n";
