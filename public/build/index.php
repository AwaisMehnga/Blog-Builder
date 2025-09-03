<?php   
declare(strict_types=1);

use Core\App;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env if file exists
if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
}

// get all route files from the routes directory
$routeFiles = glob(__DIR__ . '/../routes/*.php');

$globalMiddleware = [];

$app = new App($routeFiles, $globalMiddleware);

// Run the application
$app->run();
