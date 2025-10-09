<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Application;
use Core\Router;
use Dotenv\Dotenv;

define('PATH_ROOT', __DIR__ . '/../');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configure and start session (with database sessions if available)
Core\SessionConfig::configure();
Core\SessionConfig::start();

// Load CSRF helper functions
require_once __DIR__ . '/../app/Helpers/CsrfHelper.php';

// Check if system is installed (using flag file - no DB queries!)
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isInstallerPath = strpos($currentPath, '/install') === 0;
$installedFlagFile = __DIR__ . '/../.installed';

if (!$isInstallerPath) {
    // Check if .installed flag file exists
    if (!file_exists($installedFlagFile)) {
        header('Location: /install');
        exit;
    }
}

// Check remember me token if user is not logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token']) && !$isInstallerPath) {
    $authController = new \App\Controllers\AuthController();
    $authController->checkRememberToken();
}

// Initialize application
$app = new Application();

// Load routes
require_once __DIR__ . '/../routes/web.php';

// Run application
$app->run();

