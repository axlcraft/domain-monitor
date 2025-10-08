<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Create user with hashed password
     */
    public function createUser(string $username, string $password, ?string $email = null, ?string $fullName = null): int
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        return $this->create([
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'full_name' => $fullName,
            'is_active' => 1
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }
}

