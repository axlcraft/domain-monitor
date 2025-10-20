<?php

namespace App\Models;

use Core\Model;

class NotificationGroup extends Model
{
    protected static string $table = 'notification_groups';

    /**
     * Get all groups with channel count
     */
    public function getAllWithChannelCount(?int $userId = null): array
    {
        $sql = "SELECT ng.*, 
                COUNT(DISTINCT nc.id) as channel_count,
                COUNT(DISTINCT d.id) as domain_count
                FROM notification_groups ng
                LEFT JOIN notification_channels nc ON ng.id = nc.notification_group_id
                LEFT JOIN domains d ON ng.id = d.notification_group_id";
        
        if ($userId && !$this->isAdmin($userId)) {
            $sql .= " WHERE ng.user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql .= " GROUP BY ng.id ORDER BY ng.name ASC";
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Get group with channels and domains
     */
    public function getWithDetails(int $id, ?int $userId = null): ?array
    {
        $group = $this->find($id);

        if (!$group) {
            return null;
        }

        // Check if user has access to this group
        if ($userId && !$this->isAdmin($userId) && $group['user_id'] != $userId) {
            return null;
        }

        // Get channels
        $channelModel = new NotificationChannel();
        $group['channels'] = $channelModel->getByGroupId($id);

        // Get domains (filtered by user if needed)
        $domainModel = new Domain();
        if ($userId && !$this->isAdmin($userId)) {
            $group['domains'] = $domainModel->where('notification_group_id', $id, $userId);
        } else {
            $group['domains'] = $domainModel->where('notification_group_id', $id);
        }

        return $group;
    }

    /**
     * Delete group and handle relationships
     */
    public function deleteWithRelations(int $id): bool
    {
        // The database CASCADE will handle channels
        // But we need to set domains to NULL
        $sql = "UPDATE domains SET notification_group_id = NULL WHERE notification_group_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        return $this->delete($id);
    }

    /**
     * Check if user is admin
     */
    private function isAdmin(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user && $user['role'] === 'admin';
    }

    /**
     * Get first admin user
     */
    public function getFirstAdminUser(): ?array
    {
        $stmt = $this->db->query("SELECT * FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        return $stmt->fetch() ?: null;
    }
}

