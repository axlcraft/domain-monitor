<?php

namespace Core;

/**
 * Session Validator Middleware
 * 
 * Validates that the current session exists in database.
 * If session was deleted (logged out remotely), forces re-login.
 */
class SessionValidator
{
    /**
     * Validate current session against database
     * If session doesn't exist in DB, destroy it and force login
     */
    public static function validate(): void
    {
        // Skip if not logged in
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        try {
            $sessionId = session_id();
            $pdo = Database::getConnection();
            
            // Check if this session exists in database
            $stmt = $pdo->prepare("SELECT user_id FROM sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            // If session not found in DB, it was deleted remotely
            if (!$result) {
                // Session was deleted - logout this user
                session_destroy();
                session_start();
                $_SESSION['error'] = 'Your session was terminated remotely. Please login again.';
                header('Location: /login');
                exit;
            }
            
            // If session exists but user_id doesn't match, something is wrong
            if ($result['user_id'] != $_SESSION['user_id']) {
                session_destroy();
                session_start();
                $_SESSION['error'] = 'Session validation failed. Please login again.';
                header('Location: /login');
                exit;
            }
            
        } catch (\Exception $e) {
            // If sessions table doesn't exist, allow normal operation (graceful fallback)
            error_log("Session validation failed: " . $e->getMessage());
        }
    }
}

