<?php

namespace App\Models;

use Core\Model;

class NotificationChannel extends Model
{
    protected static string $table = 'notification_channels';

    /**
     * Get channels by notification group ID
     */
    public function getByGroupId(int $groupId): array
    {
        return $this->where('notification_group_id', $groupId);
    }

    /**
     * Get active channels by notification group ID
     */
    public function getActiveByGroupId(int $groupId): array
    {
        $sql = "SELECT * FROM notification_channels 
                WHERE notification_group_id = ? AND is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Create channel with JSON config
     */
    public function createChannel(int $groupId, string $type, array $config): int
    {
        return $this->create([
            'notification_group_id' => $groupId,
            'channel_type' => $type,
            'channel_config' => json_encode($config),
            'is_active' => 1
        ]);
    }

    /**
     * Update channel config
     */
    public function updateConfig(int $id, array $config): bool
    {
        $sql = "UPDATE notification_channels SET channel_config = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([json_encode($config), $id]);
    }

    /**
     * Toggle channel active status
     */
    public function toggleActive(int $id): bool
    {
        $sql = "UPDATE notification_channels 
                SET is_active = NOT is_active, updated_at = NOW() 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}

