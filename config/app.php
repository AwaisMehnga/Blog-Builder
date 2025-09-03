<?php

return [
    'name' => env('APP_NAME', 'BilloCraft'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'debug' => filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'database' => include __DIR__ . '/database.php',
    'middleware' => [
        // Global middleware can be added here
    ],
];
