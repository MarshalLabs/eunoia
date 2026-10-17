<?php

/**
 * Generates resources/overrides/ar/{namespace}/{group}.php from upstream Arabic files by applying
 * the Eunoia glossary (design system §7.2). Re-run after every upstream sync and review the diff:
 *
 *   php plugins/eunoia/arabic/tools/generate-overrides.php && vendor/bin/pint plugins/eunoia/arabic
 *
 * Only changed keys are written, so upstream improvements to other keys still flow through.
 */
$root = dirname(__DIR__, 4);
$out = dirname(__DIR__).'/resources/overrides/ar';

/** Context: which plugins speak about vendors, and where "session/register" means the POS shift/till. */
$vendorContext = fn (string $plugin, string $group): bool => $plugin === 'purchases' || preg_match('/(bill|vendor|purchase|refund)/', $group) === 1;
$posContext = fn (string $plugin): bool => $plugin === 'point-of-sale';

$A = '\p{Arabic}\x{064B}-\x{0652}';
$word = fn (string $w): string => '/(?<!['.$A.'])'.$w.'(?!['.$A.'])/u';

/**
 * @return array<string, string> regex => replacement
 */
$rules = function (string $plugin, string $group, string $key) use ($vendorContext, $posContext, $word): array {
    $rules = [];

    // JO-03: Jordan's tax is General Sales Tax, never VAT. Other countries' VAT numbers keep their name.
    if (! preg_match('/vat[-_]?(number|id)|[-_]vat$/i', $key)) {
        $rules[$word('ضريبة القيمة المضافة')] = 'ضريبة المبيعات العامة';
        $rules[$word('القيمة المضافة')] = 'المبيعات العامة';
    }

    $rules += [
        // Post an invoice = اعتماد (glossary). Only forms with shadda mean "posted"; مرحلة (stage) is untouched.
        $word('(و?)(ال)?مُ?رح[\x{064E}\x{0651}]+لة') => '$1$2معتمدة',
        $word('(و?)(ال)?مُ?رح[\x{064E}\x{0651}]+ل')  => '$1$2معتمد',
        $word('يرحّل')                               => 'يعتمد',
        $word('(و|ل)?(ال)?ترحيل')                    => '$1$2اعتماد',
    ];

    if ($plugin === 'partners' || $plugin === 'contacts') {
        $rules += [
            $word('(لل|ل)?الشريك') => '$1جهة الاتصال',
            $word('(لل)?الشركاء')  => '$1جهات الاتصال',
            $word('شريك')          => 'جهة اتصال',
            $word('شركاء')         => 'جهات اتصال',
        ];
    } elseif ($vendorContext($plugin, $group)) {
        $rules += [
            $word('للشريك')  => 'للمورد',
            $word('الشريك')  => 'المورد',
            $word('للشركاء') => 'للموردين',
            $word('الشركاء') => 'الموردون',
            $word('شريك')    => 'مورد',
            $word('شركاء')   => 'موردون',
        ];
    } else {
        $rules += [
            $word('للشريك')  => 'للعميل',
            $word('الشريك')  => 'العميل',
            $word('للشركاء') => 'للعملاء',
            $word('الشركاء') => 'العملاء',
            $word('شريك')    => 'عميل',
            $word('شركاء')   => 'عملاء',
        ];
    }

    if ($posContext($plugin)) {
        $rules += [
            $word('(و|ب)?الجلسات')      => '$1الورديات',
            $word('جلسات')              => 'ورديات',
            $word('(و|ب)?الجلسة')       => '$1الوردية',
            $word('جلسة')               => 'وردية',
            $word('السجلات النقدية')    => 'الصناديق',
            $word('السجل النقدي')       => 'الصندوق',
        ];
    }

    return $rules;
};

$flatten = function (array $array, string $prefix = '') use (&$flatten): array {
    $result = [];

    foreach ($array as $key => $value) {
        $full = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        $result += is_array($value) ? $flatten($value, $full) : [$full => $value];
    }

    return $result;
};

$set = function (array &$array, string $path, mixed $value): void {
    $ref = &$array;

    foreach (explode('.', $path) as $segment) {
        $ref = &$ref[$segment];
    }

    $ref = $value;
};

$export = function (array $array, int $indent = 1) use (&$export): string {
    $pad = str_repeat('    ', $indent);
    $lines = [];

    foreach ($array as $key => $value) {
        $k = var_export((string) $key, true);
        $lines[] = is_array($value)
            ? "{$pad}{$k} => ".$export($value, $indent + 1).','
            : "{$pad}{$k} => ".var_export($value, true).',';
    }

    return "[\n".implode("\n", $lines)."\n".str_repeat('    ', $indent - 1).']';
};

if (is_dir($out)) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
}

$total = 0;

foreach (glob($root.'/plugins/webkul/*/resources/lang/ar') ?: [] as $langDir) {
    $plugin = basename(dirname($langDir, 3));

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($langDir, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $group = substr($file->getPathname(), strlen($langDir) + 1, -4);
        $lines = include $file->getPathname();

        if (! is_array($lines)) {
            continue;
        }

        $changes = [];

        foreach ($flatten($lines) as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $new = $value;

            foreach ($rules($plugin, $group, $key) as $pattern => $replacement) {
                $new = preg_replace($pattern, $replacement, $new);
            }

            if ($new !== $value) {
                $set($changes, $key, $new);
                $total++;
            }
        }

        if ($changes) {
            $target = "{$out}/{$plugin}/{$group}.php";
            @mkdir(dirname($target), 0777, true);
            file_put_contents($target, "<?php\n\n// Generated by plugins/eunoia/arabic/tools/generate-overrides.php from upstream ar/{$plugin}/{$group}.php — do not edit by hand.\n\nreturn ".$export($changes).";\n");
        }
    }
}

echo "Wrote {$total} glossary overrides to {$out}\n";
