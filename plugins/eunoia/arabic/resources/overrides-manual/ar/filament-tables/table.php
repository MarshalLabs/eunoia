<?php

/**
 * Hand-written Arabic overrides (design system §6.1 EmptyState: a full grammatical sentence).
 * :model is the plural label without "ال" (see resources/glossary/plurals.php), e.g. "لا توجد فواتير بعد".
 */
return [
    'empty' => [
        'heading'     => 'لا توجد :model بعد',
        'description' => 'أضف :model للبدء.',
    ],
];
