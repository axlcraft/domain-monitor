<?php

namespace Core;

class Application
{
    public static Router $router;
    public static Database $db;

    public function __construct()
    {
        self::$router = new Router();
        self::$db = new Database();
    }

    public function run()
    {
        try {
            self::$router->resolve();
        } catch (\Exception $e) {
            http_response_code(500);
            if ($_ENV['APP_ENV'] === 'development') {
                echo '<h1>Error</h1>';
                echo '<pre>' . $e->getMessage() . '</pre>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            } else {
                echo '<h1>500 - Internal Server Error</h1>';
            }
        }
    }
}

