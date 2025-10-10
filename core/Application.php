<?php

namespace Core;

use App\Services\ErrorHandler;

class Application
{
    public static Router $router;
    public static Database $db;
    private ErrorHandler $errorHandler;

    public function __construct()
    {
        self::$router = new Router();
        self::$db = new Database();
        
        // Initialize error handler
        $this->errorHandler = new ErrorHandler();
    }

    public function run()
    {
        try {
            self::$router->resolve();
        } catch (\Throwable $e) {
            // Use centralized error handler
            $this->errorHandler->handleException($e);
        }
    }
}

