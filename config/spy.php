<?php

return [
    'table_name' => 'http_logs',
    
    'enabled' => env('SPY_ENABLED', true),
    'db_connection' => env('SPY_DB_CONNECTION'),

    'exclude_urls' => explode(',', env('SPY_EXCLUDE_URLS', '')),
    'obfuscates' => explode(',', env('SPY_OBFUSCATES', 'password')),
];
