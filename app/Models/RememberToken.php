<?php

namespace App\Models;

use Core\Model;

class RememberToken extends Model
{
    protected static string $table = 'remember_tokens';

    /**
     * Delete remember tokens by session ID
     * Called when a session is terminated
     */
    public function deleteBySessionId(string $sessionId): int
    {
        $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        return $stmt->rowCount();
    }

    /**
     * Get remember token by session ID
     */
    public function getBySessionId(string $sessionId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM remember_tokens WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Clean old expired tokens
     */
    public function cleanExpired(): int
    {
        $stmt = $this->db->query("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        return $stmt->rowCount();
    }
}

