<?php

namespace App\Controllers;

use Core\Controller;
use Core\Auth;
use App\Models\User;
use App\Models\SessionManager;
use App\Models\RememberToken;

class ProfileController extends Controller
{
    private User $userModel;
    private SessionManager $sessionModel;
    private RememberToken $rememberTokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->sessionModel = new SessionManager();
        $this->rememberTokenModel = new RememberToken();
    }

    /**
     * Show profile page
     */
    public function index()
    {
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if (!$user) {
            $_SESSION['error'] = 'User not found';
            $this->redirect('/');
            return;
        }

        // Clean old sessions when user views their profile (perfect time!)
        // This happens naturally when users check their sessions
        try {
            $this->sessionModel->cleanOldSessions();
        } catch (\Exception $e) {
            // Silent fail - don't break the page
            error_log("Session cleanup failed: " . $e->getMessage());
        }

        // Get all active sessions
        $sessions = $this->sessionModel->getByUserId($userId);
        
        // Mark current session and check for remember tokens
        $currentSessionId = session_id();
        foreach ($sessions as &$session) {
            $session['is_current'] = ($session['id'] === $currentSessionId);
            // Format timestamps for display
            $session['last_activity'] = date('Y-m-d H:i:s', $session['last_activity']);
            $session['created_at'] = date('Y-m-d H:i:s', $session['created_at']);
            
            // Check if this session has a remember token
            $rememberToken = $this->rememberTokenModel->getBySessionId($session['id']);
            $session['has_remember_token'] = !empty($rememberToken);
        }
        
        // Format sessions for display (adds deviceIcon, browserInfo, timeAgo, sessionAge)
        $formattedSessions = \App\Helpers\SessionHelper::formatForDisplay($sessions);

        $this->view('profile/index', [
            'user' => $user,
            'sessions' => $formattedSessions,
            'title' => 'My Profile'
        ]);
    }

    /**
     * Update profile
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profile');
            return;
        }

        $userId = Auth::id();
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validate
        if (empty($fullName) || empty($email)) {
            $_SESSION['error'] = 'Full name and email are required';
            $this->redirect('/profile');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address';
            $this->redirect('/profile');
            return;
        }

        // Check if email is already taken by another user
        $existingUsers = $this->userModel->where('email', $email);
        foreach ($existingUsers as $existingUser) {
            if ($existingUser['id'] != $userId) {
            $_SESSION['error'] = 'Email address is already in use';
            $this->redirect('/profile');
            return;
        }
        }

        // Update user
        $this->userModel->update($userId, [
            'full_name' => $fullName,
            'email' => $email,
        ]);

        // Update session
            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;

                $_SESSION['success'] = 'Profile updated successfully';
            $this->redirect('/profile');
    }

    /**
     * Change password
     */
    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profile');
            return;
        }

        $userId = Auth::id();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        // Validate
        if (empty($currentPassword) || empty($newPassword) || empty($newPasswordConfirm)) {
            $_SESSION['error'] = 'All fields are required';
            $this->redirect('/profile');
            return;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long';
            $this->redirect('/profile');
            return;
        }

        if ($newPassword !== $newPasswordConfirm) {
            $_SESSION['error'] = 'New passwords do not match';
            $this->redirect('/profile');
            return;
        }

        // Get user
            $user = $this->userModel->find($userId);

            // Verify current password
            if (!$this->userModel->verifyPassword($currentPassword, $user['password'])) {
                $_SESSION['error'] = 'Current password is incorrect';
                $this->redirect('/profile');
                return;
            }

            // Update password
            $this->userModel->changePassword($userId, $newPassword);

            $_SESSION['success'] = 'Password changed successfully';
            $this->redirect('/profile');
    }

    /**
     * Delete account
     */
    public function delete()
    {
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        // Don't allow admins to delete their own account
        if ($user['role'] === 'admin') {
            $_SESSION['error'] = 'Admin accounts cannot be deleted';
            $this->redirect('/profile');
            return;
        }

        // Delete user (cascade will handle related records)
            $this->userModel->delete($userId);

        // Logout
            session_destroy();
            session_start();

        $_SESSION['success'] = 'Your account has been deleted';
            $this->redirect('/login');
    }

    /**
     * Resend email verification
     */
    public function resendVerification()
    {
        $userId = Auth::id();
        $user = $this->userModel->find($userId);

        if ($user['email_verified']) {
            $_SESSION['info'] = 'Your email is already verified';
            $this->redirect('/profile');
            return;
        }

        // Use AuthController logic
        $authController = new AuthController();
        
        $_SESSION['pending_verification_email'] = $user['email'];
        $_SESSION['success'] = 'Verification email sent! Please check your inbox.';
        
        $this->redirect('/profile');
    }

    /**
     * Logout other sessions (actually terminates them!)
     */
    public function logoutOtherSessions()
    {
        $userId = Auth::id();
        $currentSessionId = session_id();

        if (!$currentSessionId) {
            $_SESSION['error'] = 'No active session found';
            $this->redirect('/profile');
            return;
        }

        try {
            // Get all other sessions first to delete their remember tokens
            $allSessions = $this->sessionModel->getByUserId($userId);
            $deletedTokens = 0;
            foreach ($allSessions as $session) {
                if ($session['id'] !== $currentSessionId) {
                    $deletedTokens += $this->rememberTokenModel->deleteBySessionId($session['id']);
                }
            }
            
            // Delete all other sessions (this actually logs them out!)
            $count = $this->sessionModel->deleteOtherSessions($userId, $currentSessionId);
            
            // Perfect time to clean all old sessions (user is security-conscious)
            $this->sessionModel->cleanOldSessions();
            
            $message = "Terminated {$count} other session(s) - those devices are now logged out";
            if ($deletedTokens > 0) {
                $message .= " ({$deletedTokens} remember tokens removed)";
            }
            $_SESSION['success'] = $message;
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to terminate other sessions';
        }

        $this->redirect('/profile#sessions');
    }

    /**
     * Logout specific session (actually terminates it!)
     */
    public function logoutSession($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profile');
            return;
        }

        $sessionId = $params['sessionId'] ?? '';
        $userId = Auth::id();
        $currentSessionId = session_id();

        if (empty($sessionId)) {
            $_SESSION['error'] = 'Invalid session';
            $this->redirect('/profile');
            return;
        }

        try {
            // Get the session to verify ownership
            $session = $this->sessionModel->getById($sessionId);

            if (!$session) {
                $_SESSION['error'] = 'Session not found';
                $this->redirect('/profile');
                return;
            }

            // Verify session belongs to current user
            if ($session['user_id'] != $userId) {
                $_SESSION['error'] = 'Unauthorized action';
                $this->redirect('/profile');
                return;
            }

            // Prevent deleting current session
            if ($session['id'] === $currentSessionId) {
                $_SESSION['error'] = 'Cannot delete your current session. Use logout instead.';
                $this->redirect('/profile');
                return;
            }

            // Delete the session (this actually logs out that device!)
            $this->sessionModel->deleteById($sessionId);
            
            // Also delete any remember token associated with this session
            $deletedTokens = $this->rememberTokenModel->deleteBySessionId($sessionId);
            
            $message = 'Session terminated - that device is now logged out';
            if ($deletedTokens > 0) {
                $message .= ' (remember me disabled)';
            }
            $_SESSION['success'] = $message;

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to terminate session';
        }

        $this->redirect('/profile#sessions');
    }
}
