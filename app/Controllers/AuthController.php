<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use App\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthController extends Controller
{
    private User $userModel;
    private Setting $settingModel;
    private $db;

    public function __construct()
    {
        $this->userModel = new User();
        $this->settingModel = new Setting();
        $this->db = \Core\Database::getConnection();
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
        }
        
        // Check if registration is enabled
        $registrationEnabled = $this->settingModel->getValue('registration_enabled');

        $this->view('auth/login', [
            'title' => 'Login',
            'registrationEnabled' => $registrationEnabled
        ]);
    }

    /**
     * Process login
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // Don't trim - passwords may have intentional spaces
        $remember = isset($_POST['remember']);

        // Validate input
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username and password are required';
            $this->redirect('/login');
            return;
        }

        // Find user by username or email
        $user = $this->userModel->findByUsername($username);
        
        // If not found by username, try email
        if (!$user && filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $users = $this->userModel->where('email', $username);
            if (!empty($users) && $users[0]['is_active']) {
                $user = $users[0];
            }
        }

        if (!$user) {
            error_log("Login failed: User '$username' not found or not active");
            $_SESSION['error'] = 'Invalid username or password';
            $this->redirect('/login');
            return;
        }

        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            error_log("Login failed: Password verification failed for user '$username'");
            error_log("Stored hash: {$user['password']}");
            $_SESSION['error'] = 'Invalid username or password';
            $this->redirect('/login');
            return;
        }
        
        error_log("Login successful for user '$username'");

        // Check if email verification is required
        $requireVerification = $this->settingModel->getValue('require_email_verification');
        if ($requireVerification && !$user['email_verified'] && $user['role'] !== 'admin') {
            $_SESSION['error'] = 'Please verify your email address before logging in';
            $_SESSION['pending_verification_email'] = $user['email'];
            $this->redirect('/verify-email');
            return;
        }

        // Login successful - create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Session is automatically tracked by DatabaseSessionHandler
        // No need to manually create session record

        // Handle remember me
        if ($remember) {
            $this->createRememberToken($user['id']);
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Redirect to dashboard
        $this->redirect('/');
    }

    /**
     * Show registration form
     */
    public function showRegister()
    {
        // Check if already logged in
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
            return;
        }

        // Check if registration is enabled
        $registrationEnabled = $this->settingModel->getValue('registration_enabled');
        if (!$registrationEnabled) {
            $_SESSION['error'] = 'Registration is currently disabled';
            $this->redirect('/login');
            return;
        }

        $this->view('auth/register', [
            'title' => 'Register'
        ]);
    }

    /**
     * Process registration
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }

        // Check if registration is enabled
        $registrationEnabled = $this->settingModel->getValue('registration_enabled');
        if (!$registrationEnabled) {
            $_SESSION['error'] = 'Registration is currently disabled';
            $this->redirect('/login');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validate inputs
        if (empty($username) || empty($email) || empty($fullName) || empty($password)) {
            $_SESSION['error'] = 'All fields are required';
            $this->redirect('/register');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address';
            $this->redirect('/register');
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $_SESSION['error'] = 'Username can only contain letters, numbers, and underscores';
            $this->redirect('/register');
            return;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long';
            $this->redirect('/register');
            return;
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'Passwords do not match';
            $this->redirect('/register');
            return;
        }

        // Check if username already exists
        $existingUser = $this->userModel->findByUsername($username);
        if ($existingUser) {
            $_SESSION['error'] = 'Username is already taken';
            $this->redirect('/register');
            return;
        }

        // Check if email already exists
        $existingEmail = $this->userModel->where('email', $email);
        if (!empty($existingEmail)) {
            $_SESSION['error'] = 'Email address is already registered';
            $this->redirect('/register');
            return;
        }

        try {
            // Create user account
            $userId = $this->userModel->createUser($username, $password, $email, $fullName);
            
            // Create welcome notification
            try {
                $notificationService = new \App\Services\NotificationService();
                $notificationService->notifyWelcome($userId, $username);
            } catch (\Exception $e) {
                // Don't fail registration if notification fails
                error_log("Failed to create welcome notification: " . $e->getMessage());
            }

            // Check if email verification is required
            $requireVerification = $this->settingModel->getValue('require_email_verification');
            
            if ($requireVerification) {
                // Generate verification token
                $token = bin2hex(random_bytes(32));
                
                // Save token to database
                $stmt = $this->db->prepare(
                    "UPDATE users SET email_verification_token = ?, email_verification_sent_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$token, $userId]);

                // Send verification email
                $this->sendVerificationEmail($email, $fullName, $token);

                $_SESSION['success'] = 'Account created successfully! Please check your email to verify your account.';
                $_SESSION['pending_verification_email'] = $email;
                $this->redirect('/verify-email');
            } else {
                // Mark as verified and log them in
                $stmt = $this->db->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
                $stmt->execute([$userId]);

                $_SESSION['success'] = 'Account created successfully! You can now log in.';
                $this->redirect('/login');
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create account: ' . $e->getMessage();
            $this->redirect('/register');
        }
    }

    /**
     * Show email verification page
     */
    public function showVerifyEmail()
    {
        $token = $_GET['token'] ?? null;
        
        if ($token) {
            // Verify the token
            $this->verifyEmail($token);
            return;
        }

        // Show pending verification page
        $email = $_SESSION['pending_verification_email'] ?? 'your email';
        $this->view('auth/verify-email', [
            'title' => 'Verify Email',
            'email' => $email
        ]);
    }

    /**
     * Verify email with token
     */
    private function verifyEmail($token)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM users WHERE email_verification_token = ? AND email_verified = 0"
            );
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->view('auth/verify-email', [
                    'title' => 'Verification Failed',
                    'error' => true,
                    'errorMessage' => 'Invalid or expired verification link.'
                ]);
                return;
            }

            // Check if token is expired (24 hours)
            $sentAt = strtotime($user['email_verification_sent_at']);
            if (time() - $sentAt > 86400) {
                $this->view('auth/verify-email', [
                    'title' => 'Verification Failed',
                    'error' => true,
                    'errorMessage' => 'Verification link has expired. Please request a new one.'
                ]);
                return;
            }

            // Mark email as verified
            $stmt = $this->db->prepare(
                "UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = ?"
            );
            $stmt->execute([$user['id']]);

            $this->view('auth/verify-email', [
                'title' => 'Email Verified',
                'verified' => true
            ]);

        } catch (\Exception $e) {
            $this->view('auth/verify-email', [
                'title' => 'Verification Failed',
                'error' => true,
                'errorMessage' => 'An error occurred during verification.'
            ]);
        }
    }

    /**
     * Resend verification email
     */
    public function resendVerification()
    {
        // Only allow resend if email is in session (from registration or login attempt)
        $email = $_SESSION['pending_verification_email'] ?? '';

        if (empty($email)) {
            $_SESSION['error'] = 'Please try logging in first to resend verification email';
            $this->redirect('/login');
            return;
        }

        try {
            $users = $this->userModel->where('email', $email);
            
            if (empty($users)) {
                $_SESSION['error'] = 'Email address not found';
                $this->redirect('/verify-email');
                return;
            }

            $user = $users[0];

            if ($user['email_verified']) {
                $_SESSION['info'] = 'Email is already verified. You can log in now.';
                $this->redirect('/login');
                return;
            }

            // Generate new verification token
            $token = bin2hex(random_bytes(32));
            
            $stmt = $this->db->prepare(
                "UPDATE users SET email_verification_token = ?, email_verification_sent_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$token, $user['id']]);

            // Send verification email
            $this->sendVerificationEmail($user['email'], $user['full_name'], $token);

            $_SESSION['success'] = 'Verification email sent! Please check your inbox.';
            $_SESSION['pending_verification_email'] = $email;
            $this->redirect('/verify-email');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to resend verification email. Please try again.';
            $this->redirect('/verify-email');
        }
    }

    /**
     * Show forgot password form
     */
    public function showForgotPassword()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/');
            return;
        }

        $this->view('auth/forgot-password', [
            'title' => 'Forgot Password'
        ]);
    }

    /**
     * Process forgot password request
     */
    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/forgot-password');
            return;
        }

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address';
            $this->redirect('/forgot-password');
            return;
        }

        try {
            $users = $this->userModel->where('email', $email);
            
            // Always show success message to prevent email enumeration
            $_SESSION['success'] = 'If an account exists with that email, you will receive password reset instructions.';

            if (!empty($users)) {
                $user = $users[0];

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token to database
                $stmt = $this->db->prepare(
                    "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)"
                );
                $stmt->execute([$user['id'], $token, $expiresAt]);

                // Send reset email
                $this->sendPasswordResetEmail($user['email'], $user['full_name'], $token);
            }

            $this->redirect('/forgot-password');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'An error occurred. Please try again.';
            $this->redirect('/forgot-password');
        }
    }

    /**
     * Show reset password form
     */
    public function showResetPassword()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['error'] = 'Invalid reset link';
            $this->redirect('/login');
            return;
        }

        // Verify token exists and is not expired
        $stmt = $this->db->prepare(
            "SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()"
        );
        $stmt->execute([$token]);
        $resetToken = $stmt->fetch();

        if (!$resetToken) {
            $_SESSION['error'] = 'Invalid or expired reset link';
            $this->redirect('/forgot-password');
            return;
        }

        $this->view('auth/reset-password', [
            'title' => 'Reset Password',
            'token' => $token
        ]);
    }

    /**
     * Process password reset
     */
    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validate inputs
        if (empty($token) || empty($password) || empty($passwordConfirm)) {
            $_SESSION['error'] = 'All fields are required';
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long';
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'Passwords do not match';
            $this->redirect('/reset-password?token=' . urlencode($token));
            return;
        }

        try {
            // Verify token
            $stmt = $this->db->prepare(
                "SELECT * FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()"
            );
            $stmt->execute([$token]);
            $resetToken = $stmt->fetch();

            if (!$resetToken) {
                $_SESSION['error'] = 'Invalid or expired reset link';
                $this->redirect('/forgot-password');
                return;
            }

            // Update password
            $this->userModel->changePassword($resetToken['user_id'], $password);

            // Mark token as used
            $stmt = $this->db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
            $stmt->execute([$resetToken['id']]);

            $_SESSION['success'] = 'Password reset successfully! You can now log in.';
            $this->redirect('/login');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to reset password. Please try again.';
            $this->redirect('/reset-password?token=' . urlencode($token));
        }
    }

    /**
     * Create remember me token linked to current session
     */
    private function createRememberToken($userId)
    {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            $sessionId = session_id();

            $stmt = $this->db->prepare(
                "INSERT INTO remember_tokens (user_id, session_id, token, expires_at) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$userId, $sessionId, $token, $expiresAt]);

            // Set cookie
            setcookie('remember_token', $token, [
                'expires' => strtotime('+30 days'),
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

        } catch (\Exception $e) {
            // Silently fail - remember me is not critical
            error_log("Failed to create remember token: " . $e->getMessage());
        }
    }

    /**
     * Check and process remember me token
     */
    public function checkRememberToken()
    {
        $token = $_COOKIE['remember_token'] ?? null;

        if (!$token) {
            return false;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()"
            );
            $stmt->execute([$token]);
            $rememberToken = $stmt->fetch();

            if ($rememberToken) {
                $user = $this->userModel->find($rememberToken['user_id']);
                
                if ($user && $user['is_active']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // Session is automatically tracked by DatabaseSessionHandler
                    // No need to manually create session record

                    return true;
                }
            }

            // Invalid token - clear cookie
            setcookie('remember_token', '', time() - 3600, '/');

        } catch (\Exception $e) {
            // Silently fail
        }

        return false;
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $fullName, $token)
    {
        try {
            $emailSettings = $this->settingModel->getEmailSettings();
            $appSettings = $this->settingModel->getAppSettings();
            
            $verifyUrl = $appSettings['app_url'] . '/verify-email?token=' . $token;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $emailSettings['mail_host'];
            $mail->SMTPAuth = !empty($emailSettings['mail_username']);
            $mail->Username = $emailSettings['mail_username'];
            $mail->Password = $emailSettings['mail_password'];
            $mail->SMTPSecure = $emailSettings['mail_encryption'];
            $mail->Port = (int)$emailSettings['mail_port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($emailSettings['mail_from_address'], $emailSettings['mail_from_name']);
            $mail->addAddress($email, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email Address';
            $mail->Body = "
                <h2>Welcome to Domain Monitor!</h2>
                <p>Hello {$fullName},</p>
                <p>Thank you for registering. Please click the link below to verify your email address:</p>
                <p><a href='{$verifyUrl}' style='background: #4A90E2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a></p>
                <p>Or copy and paste this URL into your browser:</p>
                <p>{$verifyUrl}</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not create an account, please ignore this email.</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            // Log error but don't fail the registration
            error_log('Failed to send verification email: ' . $e->getMessage());
        }
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($email, $fullName, $token)
    {
        try {
            $emailSettings = $this->settingModel->getEmailSettings();
            $appSettings = $this->settingModel->getAppSettings();
            
            $resetUrl = $appSettings['app_url'] . '/reset-password?token=' . $token;

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $emailSettings['mail_host'];
            $mail->SMTPAuth = !empty($emailSettings['mail_username']);
            $mail->Username = $emailSettings['mail_username'];
            $mail->Password = $emailSettings['mail_password'];
            $mail->SMTPSecure = $emailSettings['mail_encryption'];
            $mail->Port = (int)$emailSettings['mail_port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($emailSettings['mail_from_address'], $emailSettings['mail_from_name']);
            $mail->addAddress($email, $fullName);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $mail->Body = "
                <h2>Password Reset Request</h2>
                <p>Hello {$fullName},</p>
                <p>We received a request to reset your password. Click the link below to create a new password:</p>
                <p><a href='{$resetUrl}' style='background: #4A90E2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                <p>Or copy and paste this URL into your browser:</p>
                <p>{$resetUrl}</p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            // Log error
            error_log('Failed to send password reset email: ' . $e->getMessage());
        }
    }


    /**
     * Logout
     */
    public function logout()
    {
        // Clear remember me token if exists
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            try {
                $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE token = ?");
                $stmt->execute([$token]);
            } catch (\Exception $e) {
                // Silently fail
            }

            setcookie('remember_token', '', time() - 3600, '/');
        }

        // Destroy session (DatabaseSessionHandler automatically deletes from DB)
        session_destroy();
        session_start();

        $_SESSION['success'] = 'You have been logged out successfully';
        $this->redirect('/login');
    }
}
