<?php

/**
 * Reference: db_design.md §5.1, testing_spec.md §7.2
 * ("Database | MySQL", "Testing SHALL use a dedicated testing
 * database isolated from development data.")
 */
return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'secure_messaging_app'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],

        // Dedicated, isolated testing connection (testing_spec.md §7.2).
        'mysql_testing' => [
            'driver' => 'mysql',
            'host' => env('DB_TEST_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('DB_TEST_PORT', env('DB_PORT', '3306')),
            'database' => env('DB_TEST_DATABASE', 'secure_messaging_app_testing'),
            'username' => env('DB_TEST_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('DB_TEST_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    'migrations' => 'migrations',
];
