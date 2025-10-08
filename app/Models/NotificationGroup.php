<?php

namespace App\Models;

use Core\Model;

class NotificationGroup extends Model
{
    protected static string $table = 'notification_groups';

    /**
     * Get all groups with channel count
     */
    public function getAllWithChannelCount(): array
    {
        $sql = "SELECT ng.*, 
                COUNT(DISTINCT nc.id) as channel_count,
                COUNT(DISTINCT d.id) as domain_count
                FROM notification_groups ng
                LEFT JOIN notification_channels nc ON ng.id = nc.notification_group_id
                LEFT JOIN domains d ON ng.id = d.notification_group_id
                GROUP BY ng.id
                ORDER BY ng.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get group with channels and domains
     */
    public function getWithDetails(int $id): ?array
    {
        $group = $this->find($id);

        if (!$group) {
            return null;
        }

        // Get channels
        $channelModel = new NotificationChannel();
        $group['channels'] = $channelModel->getByGroupId($id);

        // Get domains
        $domainModel = new Domain();
        $group['domains'] = $domainModel->where('notification_group_id', $id);

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
}

