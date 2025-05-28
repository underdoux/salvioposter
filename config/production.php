<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Environment Settings
    |--------------------------------------------------------------------------
    */

    'database' => [
        'max_connections' => env('DB_MAX_CONNECTIONS', 100),
        'timeout' => env('DB_TIMEOUT', 60),
        'strict' => true,
        'engine' => null,
    ],

    'cache' => [
        'driver' => env('CACHE_DRIVER', 'redis'),
        'prefix' => env('CACHE_PREFIX', 'blogposter_cache'),
        'ttl' => env('CACHE_TTL', 3600),
    ],

    'queue' => [
        'driver' => env('QUEUE_CONNECTION', 'redis'),
        'retry_after' => 90,
        'block_for' => null,
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
    ],

    'logging' => [
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'error'),
        'days' => env('LOG_RETENTION_DAYS', 30),
    ],

    'security' => [
        'ssl' => true,
        'force_https' => true,
        'secure_cookies' => true,
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'response_time',
            'memory_usage',
            'cpu_usage',
            'database_queries',
            'cache_hits',
            'queue_size',
        ],
        'alert_thresholds' => [
            'response_time' => 1000, // ms
            'memory_usage' => 128, // MB
            'cpu_usage' => 80, // percent
        ],
    ],

    'backup' => [
        'enabled' => true,
        'schedule' => '0 0 * * *', // Daily at midnight
        'retention' => [
            'days' => 7,
            'weekly' => 4,
            'monthly' => 3,
        ],
        'destination' => [
            'disks' => ['s3'],
            'notify' => true,
        ],
    ],

    'optimization' => [
        'opcache' => true,
        'view_cache' => true,
        'route_cache' => true,
        'config_cache' => true,
        'gzip_compression' => true,
    ],
];
