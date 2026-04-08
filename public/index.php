<?php
// public/index.php - Front Controller

// Set strict error reporting in dev
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('ROOT', dirname(__DIR__));
define('APP_PATH', ROOT . '/app');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', ROOT . '/storage');
define('LOG_PATH', STORAGE_PATH . '/logs/app.log');

// Basic autoloader (PSR-0-ish for our folders)
spl_autoload_register(function($class){
    $class = ltrim($class, '\\');
    $paths = [
        APP_PATH . '/core/' . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/services/' . $class . '.php',
        APP_PATH . '/models/' . $class . '.php',
        APP_PATH . '/libraries/' . $class . '.php',
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) { require_once $file; return; }
    }
});

// Load helpers early (log helper first to log boot errors)
require_once APP_PATH . '/helpers/log_helper.php';
require_once APP_PATH . '/helpers/url_helper.php';

// Load config and env
require_once APP_PATH . '/core/Env.php';
Env::load(ROOT . '/.env');
$config = require APP_PATH . '/config/config.php';
$autoload = require APP_PATH . '/config/autoload.php';

// Configure error display based on env
if (Env::get('APP_ENV') !== 'dev') {
    ini_set('display_errors', 0);
}

// Ensure log directory exists
if (!is_dir(dirname(LOG_PATH))) {
    mkdir(dirname(LOG_PATH), 0777, true);
}

// Handle CORS (simple, configurable)
$cors = Env::get('CORS', '*');
header("Access-Control-Allow-Origin: $cors");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Bootstrap Router
try {
    $router = new Router($config);
    // Autoload configured stuff
    Controller::setAutoload($autoload);
    $router->dispatch();
} catch (Throwable $e) {
    log_message('ERROR', 'Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
}