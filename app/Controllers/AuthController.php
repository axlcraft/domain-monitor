<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
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

        $this->view('auth/login', [
            'title' => 'Login'
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
        $password = $_POST['password'] ?? '';

        // Validate input
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username and password are required';
            $this->redirect('/login');
            return;
        }

        // Find user
        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            $_SESSION['error'] = 'Invalid username or password';
            $this->redirect('/login');
            return;
        }

        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            $_SESSION['error'] = 'Invalid username or password';
            $this->redirect('/login');
            return;
        }

        // Login successful - create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Redirect to dashboard
        $this->redirect('/');
    }

    /**
     * Logout
     */
    public function logout()
    {
        // Destroy session
        session_destroy();
        session_start();

        $_SESSION['success'] = 'You have been logged out successfully';
        $this->redirect('/login');
    }
}

