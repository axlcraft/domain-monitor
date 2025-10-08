<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Application;
use Core\Router;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Start session
session_start();

// Initialize application
$app = new Application();

// Load routes
require_once __DIR__ . '/../routes/web.php';

// Run application
$app->run();

