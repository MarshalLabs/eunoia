<?php

return [
    'columns' => [
        'check'   => 'Check',
        'status'  => 'Status',
        'details' => 'Details',
    ],
    'status' => [
        'ok'   => 'OK',
        'warn' => 'Warning',
        'fail' => 'Failed',
        'skip' => 'Skipped',
    ],
    'checks' => [
        'database' => 'Database connection',
        'currency' => 'Currency precision (JOD)',
        'queue'    => 'Queue worker',
        'locale'   => 'Language and timezone',
        'fonts'    => 'Arabic fonts',
    ],
    'messages' => [
        'database_ok'            => 'Connected (:driver, database :database).',
        'currency_missing'       => 'Currency :currency not found.',
        'currency_wrong'         => ':currency has :decimals decimals and rounding :rounding; expected 3 and 0.001 (apply patch P1).',
        'currency_zero_rounding' => 'These currencies have rounding ≤ 0: :currencies (apply patch P1).',
        'currency_ok'            => 'JOD uses 3 decimals with rounding 0.001; no currency has rounding ≤ 0.',
        'queue_ok'               => 'A worker processed the heartbeat job (:connection).',
        'queue_timeout'          => 'No worker processed the heartbeat job within :seconds s (:connection). Start one: :command',
        'locale_ok'              => 'Arabic (ar) with timezone Asia/Amman.',
        'locale_unexpected'      => 'APP_LOCALE=:locale, APP_TIMEZONE=:timezone; Eunoia expects ar and Asia/Amman.',
        'fonts_later'            => 'Checked once the theme plugin ships self-hosted fonts (E1-01).',
    ],
];
