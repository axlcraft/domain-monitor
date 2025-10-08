<?php

namespace App\Models;

use Core\Model;

class Domain extends Model
{
    protected static string $table = 'domains';

    /**
     * Get all domains with their notification group
     */
    public function getAllWithGroups(): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                ORDER BY d.status DESC, d.expiration_date ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get domains expiring within days
     */
    public function getExpiringDomains(int $days): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.is_active = 1 
                AND d.expiration_date IS NOT NULL 
                AND d.expiration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND d.expiration_date >= CURDATE()
                ORDER BY d.expiration_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /**
     * Get domains by status
     */
    public function getByStatus(string $status): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.status = ?
                ORDER BY d.expiration_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    /**
     * Get domain with notification channels
     */
    public function getWithChannels(int $id): ?array
    {
        $sql = "SELECT d.*, ng.name as group_name, ng.id as group_id
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $domain = $stmt->fetch();

        if (!$domain) {
            return null;
        }

        // Get notification channels for this domain's group
        if ($domain['group_id']) {
            $channelModel = new NotificationChannel();
            $domain['channels'] = $channelModel->getByGroupId($domain['group_id']);
        } else {
            $domain['channels'] = [];
        }

        return $domain;
    }

    /**
     * Check if domain exists
     */
    public function existsByDomain(string $domainName): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM domains WHERE domain_name = ?");
        $stmt->execute([$domainName]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Get recent domains
     */
    public function getRecent(int $limit = 5): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.is_active = 1
                ORDER BY d.created_at DESC, d.id DESC 
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'inactive' => 0,
        ];

        $sql = "SELECT status, COUNT(*) as count FROM domains WHERE is_active = 1 GROUP BY status";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();

        $stats['total'] = array_sum(array_column($results, 'count'));

        foreach ($results as $row) {
            $stats[strtolower($row['status'])] = $row['count'];
        }

        return $stats;
    }
}

