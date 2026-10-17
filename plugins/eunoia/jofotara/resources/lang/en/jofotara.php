<?php

return [
    'status' => [
        'queued'   => 'Waiting to send',
        'sending'  => 'Sending',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'failed'   => 'Failed',
    ],
    'registration' => [
        'income'        => 'Income tax only',
        'general_sales' => 'General Sales Tax',
        'special_sales' => 'Special Sales Tax',
    ],
    'errors' => [
        'live_not_allowed' => 'Live JoFotara is not allowed on this server. Use the mock (development) or enable it on a production server with JOFOTARA_ALLOW_LIVE=true.',
    ],
];
