<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Application;
use Core\Router;
use Dotenv\Dotenv;

define('PATH_ROOT', __DIR__ . '/../');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configure database session handler
try {
    // Only use database sessions if sessions table exists
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    
    // Check if sessions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'sessions'");
    if ($stmt->rowCount() > 0) {
        // Use database session handler
        $sessionLifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 1440);
        $handler = new Core\DatabaseSessionHandler($sessionLifetime);
        session_set_save_handler($handler, true);
    }
} catch (\Exception $e) {
    // Fall back to default file-based sessions
    error_log("Database session handler not available, using file sessions: " . $e->getMessage());
}

// Start session
session_start();

// Validate session exists in database (for database-backed sessions)
// This ensures deleted sessions are immediately invalidated
Core\SessionValidator::validate();

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

