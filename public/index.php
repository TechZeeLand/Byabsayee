<?php
// =============================================================================
// public/index.php — Front Controller (Entry Point)
// =============================================================================
// THIS IS THE ONLY PHP FILE NGINX EVER CALLS DIRECTLY.
// Every single request — whether someone visits /login, /dashboard, /api/users —
// comes through this file first.
//
// nginx is configured with:  try_files $uri $uri/ /index.php?$query_string
// That means: if a real file doesn't exist, send the request here.
//
// This file does 4 things:
//   1. Sets up the environment (paths, error handling, autoloading)
//   2. Starts the session
//   3. Loads all route definitions
//   4. Tells the router to handle the current request
// =============================================================================

// ---- 1. DEFINE THE BASE PATH ------------------------------------------------
// BASE_PATH is the root of your project (one level above /public)
// Other files use this to find config/, app/, views/, etc.
define('BASE_PATH', dirname(__DIR__));

// ---- 2. ERROR HANDLING ------------------------------------------------------
// In development: show all errors
// In production: log them, never display to users
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ---- 3. AUTOLOADER ----------------------------------------------------------
// PHP "autoloading" means: when you write  new App\Controllers\AuthController()
// PHP automatically finds and loads the right file without you needing require()
//
// Our simple autoloader: converts  App\Controllers\AuthController
// to file path:  /app/Controllers/AuthController.php

spl_autoload_register(function (string $class): void {
    // Remove the leading "App\" namespace prefix
    $relative = str_replace('App\\', '', $class);

    // Convert namespace separators to directory separators
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ---- 4. LOAD HELPER FUNCTIONS -----------------------------------------------
// These are global functions like e(), redirect(), flash(), auth(), etc.
require_once BASE_PATH . '/app/Helpers/helpers.php';

// ---- 5. LOAD COMPOSER AUTOLOADER (mPDF, PHPMailer) --------------------------
// Only if vendor/ directory exists (after running composer install)
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// ---- 6. START SESSION -------------------------------------------------------
$sessionCfg = config('session');
session_name($sessionCfg['name']);
session_set_cookie_params([
    'lifetime' => $sessionCfg['lifetime'],
    'path'     => '/',
    'secure'   => $sessionCfg['secure'],
    'httponly' => $sessionCfg['httponly'],
    'samesite' => $sessionCfg['samesite'],
]);
session_start();

// ---- 7. SET UP ROUTER -------------------------------------------------------
use App\Helpers\Router;

$router = new Router();

// Load all route definitions (keeps this file clean)
require_once BASE_PATH . '/routes.php';

// ---- 8. DISPATCH THE REQUEST ------------------------------------------------
$router->dispatch();
