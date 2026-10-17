<?php

return [
    /** The jofotara-kit mock (docker service "jofotara-mock" or `npx jofotara-kit serve --port 8080`). */
    'mock_base_url' => env('JOFOTARA_BASE_URL', 'http://127.0.0.1:8080'),

    /** Live ISTD endpoint. Only used when mode = live AND live is allowed on this server. */
    'live_base_url' => 'https://backend.jofotara.gov.jo',

    /** Safety switch: never true in dev/CI. Set JOFOTARA_ALLOW_LIVE=true only on a production tenant. */
    'allow_live' => (bool) env('JOFOTARA_ALLOW_LIVE', false),

    'timeout' => (int) env('JOFOTARA_TIMEOUT', 20),

    'queue' => 'jofotara',
];
