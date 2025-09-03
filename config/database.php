<?php

return [
    'dsn' => env('DB_DSN', 'mysql:host=localhost;dbname=blog-builder;charset=utf8mb4'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'persistent' => env('DB_PERSISTENT', false),
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
