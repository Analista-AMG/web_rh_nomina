<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// --- MANUAL AUTOLOADER FIX (EMERGENCY PATCH) ---
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/../app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});
// -----------------------------------------------

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
