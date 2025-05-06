<?php

return [
    'enabled' => env('SPY_ENABLED', true),
    'db_connection' => env('SPY_DB_CONNECTION'),
    'table_name' => 'http_logs',

    'exclude_urls' => explode(',', env('SPY_EXCLUDE_URLS', null)),
    'obfuscates' => explode(',', env('SPY_OBFUSCATES', 'password')),
];
