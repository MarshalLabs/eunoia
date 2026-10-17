<?php

return [
    'group'    => 'Jordan',
    'title'    => 'JoFotara e-invoicing',
    'sections' => [
        'seller'     => 'Seller',
        'connection' => 'Connection',
    ],
    'fields' => [
        'tin'                    => 'Tax number (TIN)',
        'income_source_sequence' => 'Income source sequence',
        'seller_name'            => 'Registered name',
        'seller_registration'    => 'Registration',
        'client_id'              => 'Client-Id',
        'secret_key'             => 'Secret-Key',
        'mode'                   => 'Environment',
        'auto_submit'            => 'Send automatically when an invoice is confirmed',
    ],
    'modes' => [
        'mock' => 'Test (local mock)',
        'live' => 'Live (ISTD)',
    ],
    'help' => [
        'income_source_sequence' => 'From your JoFotara portal (activity sequence).',
        'seller_name'            => 'Leave empty to use the company name.',
        'stored'                 => 'Saved — type a new value to replace it',
        'mode'                   => 'Live can only be enabled on a production server.',
        'auto_submit'            => 'Invoices are queued on confirmation and sent in the background.',
    ],
];
