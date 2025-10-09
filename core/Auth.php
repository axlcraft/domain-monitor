<?php

namespace Core;

class Auth
{
    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current username
     */
    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Get current user's full name
     */
    public static function fullName(): ?string
    {
        return $_SESSION['full_name'] ?? null;
    }

    /**
     * Require authentication (redirect to login if not authenticated)
     */
    public static function require(): void
    {
        // Get current path
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Public paths that don't require authentication
        $publicPaths = [
            '/login',
            '/logout',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/verify-email',
            '/resend-verification',
            '/install'
        ];
        
        // Don't redirect if on a public path
        foreach ($publicPaths as $path) {
            if (strpos($currentPath, $path) === 0) {
                return;
            }
        }
        
        if (!self::check()) {
            $_SESSION['error'] = 'Please login to continue';
            header('Location: /login');
            exit;
        }
    }
}

