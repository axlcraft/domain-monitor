<?php

namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . "/../app/Views/$view.php";

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: $view");
        }

        require_once $viewPath;
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void
    {
        header("Location: $path");
        exit;
    }
}

