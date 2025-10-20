<?php

namespace App\Models;

use Core\Model;

class Domain extends Model
{
    protected static string $table = 'domains';

    /**
     * Get User model instance
     */
    private function getUserModel(): \App\Models\User
    {
        return new \App\Models\User();
    }

    /**
     * Get all domains with their notification group
     */
    public function getAllWithGroups(?int $userId = null): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id";
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $sql .= " WHERE d.user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql .= " ORDER BY d.status DESC, d.expiration_date ASC";
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Get domains expiring within days
     */
    public function getExpiringDomains(int $days, ?int $userId = null): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.is_active = 1 
                AND d.expiration_date IS NOT NULL 
                AND d.expiration_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND d.expiration_date >= CURDATE()";
        
        $params = [$days];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $sql .= " AND d.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY d.expiration_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get domains by status
     */
    public function getByStatus(string $status, ?int $userId = null): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.status = ?";
        
        $params = [$status];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $sql .= " AND d.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY d.expiration_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
    public function getRecent(int $limit = 5, ?int $userId = null): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.is_active = 1";
        
        $params = [];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $sql .= " AND d.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY d.created_at DESC, d.id DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(?int $userId = null): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'expiring_soon' => 0,
            'expired' => 0,
            'inactive' => 0,
        ];

        // Build WHERE clause for user filtering
        $whereClause = "WHERE is_active = 1";
        $params = [];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $whereClause .= " AND user_id = ?";
            $params[] = $userId;
        }

        // Get status counts for active domains only
        $sql = "SELECT status, COUNT(*) as count FROM domains $whereClause GROUP BY status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        $stats['total'] = array_sum(array_column($results, 'count'));

        foreach ($results as $row) {
            $stats[strtolower($row['status'])] = $row['count'];
        }

        // Get count of inactive domains (is_active = 0)
        $inactiveWhereClause = "WHERE is_active = 0";
        $inactiveParams = [];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $inactiveWhereClause .= " AND user_id = ?";
            $inactiveParams[] = $userId;
        }
        
        $inactiveStmt = $this->db->prepare("SELECT COUNT(*) as count FROM domains $inactiveWhereClause");
        $inactiveStmt->execute($inactiveParams);
        $inactiveResult = $inactiveStmt->fetch();
        $stats['inactive'] = $inactiveResult['count'] ?? 0;

        // Add inactive count to total
        $stats['total'] += $stats['inactive'];

        return $stats;
    }

    /**
     * Get filtered, sorted, and paginated domains
     */
    public function getFilteredPaginated(array $filters, string $sortBy, string $sortOrder, int $page, int $perPage, int $expiringThreshold = 30, ?int $userId = null): array
    {
        // Get all domains with groups
        $domains = $this->getAllWithGroups($userId);

        // Apply search filter
        if (!empty($filters['search'])) {
            $domains = array_filter($domains, function($domain) use ($filters) {
                return stripos($domain['domain_name'], $filters['search']) !== false ||
                       stripos($domain['registrar'] ?? '', $filters['search']) !== false;
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $domains = array_filter($domains, function($domain) use ($filters, $expiringThreshold) {
                if ($filters['status'] === 'expiring_soon') {
                    // Check if domain expires within configured threshold
                    if (!empty($domain['expiration_date'])) {
                        $daysLeft = floor((strtotime($domain['expiration_date']) - time()) / 86400);
                        return $daysLeft <= $expiringThreshold && $daysLeft >= 0;
                    }
                    return false;
                }
                // Handle inactive filter (based on is_active field)
                if ($filters['status'] === 'inactive') {
                    return $domain['is_active'] == 0;
                }
                // Handle available and error status filters
                if ($filters['status'] === 'available' || $filters['status'] === 'error') {
                    return $domain['status'] === $filters['status'];
                }
                return $domain['status'] === $filters['status'];
            });
        }

        // Apply group filter
        if (!empty($filters['group'])) {
            $domains = array_filter($domains, function($domain) use ($filters) {
                return $domain['notification_group_id'] == $filters['group'];
            });
        }

        // Apply tag filter
        if (!empty($filters['tag'])) {
            $domains = array_filter($domains, function($domain) use ($filters) {
                if (empty($domain['tags'])) {
                    return false;
                }
                $domainTags = array_map('trim', explode(',', $domain['tags']));
                return in_array($filters['tag'], $domainTags);
            });
        }

        // Get total count after filtering
        $totalDomains = count($domains);

        // Apply sorting
        usort($domains, function($a, $b) use ($sortBy, $sortOrder) {
            $aVal = $a[$sortBy] ?? '';
            $bVal = $b[$sortBy] ?? '';
            
            $comparison = strcasecmp($aVal, $bVal);
            return $sortOrder === 'desc' ? -$comparison : $comparison;
        });

        // Calculate pagination
        $totalPages = ceil($totalDomains / $perPage);
        $page = min($page, max(1, $totalPages)); // Ensure page is within valid range
        $offset = ($page - 1) * $perPage;

        // Slice array for current page
        $paginatedDomains = array_slice($domains, $offset, $perPage);

        return [
            'domains' => $paginatedDomains,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalDomains,
                'total_pages' => $totalPages,
                'showing_from' => $totalDomains > 0 ? $offset + 1 : 0,
                'showing_to' => min($offset + $perPage, $totalDomains)
            ]
        ];
    }

    /**
     * Get all unique tags from all domains
     */
    public function getAllTags(?int $userId = null): array
    {
        $sql = "SELECT DISTINCT tags FROM domains WHERE tags IS NOT NULL AND tags != ''";
        $params = [];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        $allTags = [];
        foreach ($results as $row) {
            if (!empty($row['tags'])) {
                $tags = array_map('trim', explode(',', $row['tags']));
                $allTags = array_merge($allTags, $tags);
            }
        }
        
        // Return unique, sorted tags
        $allTags = array_unique($allTags);
        sort($allTags);
        return $allTags;
    }


    /**
     * Assign all domains without user_id to a specific user
     */
    public function assignUnassignedDomainsToUser(int $userId): int
    {
        $stmt = $this->db->prepare("UPDATE domains SET user_id = ? WHERE user_id IS NULL");
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    /**
     * Search domains for suggestions (quick search)
     */
    public function searchSuggestions(string $query, int $limit = 5): array
    {
        $sql = "SELECT d.id, d.domain_name, d.registrar, d.expiration_date, d.status, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE d.domain_name LIKE ? 
                   OR d.registrar LIKE ?
                ORDER BY d.domain_name ASC
                LIMIT ?";

        $searchTerm = '%' . $query . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Search domains with user isolation support
     */
    public function searchDomains(string $query, ?int $userId = null, int $limit = 50): array
    {
        $sql = "SELECT d.*, ng.name as group_name 
                FROM domains d 
                LEFT JOIN notification_groups ng ON d.notification_group_id = ng.id 
                WHERE (d.domain_name LIKE ? 
                   OR d.registrar LIKE ?
                   OR ng.name LIKE ?)";
        
        $params = ['%' . $query . '%', '%' . $query . '%', '%' . $query . '%'];
        
        if ($userId && !$this->getUserModel()->isAdmin($userId)) {
            $sql .= " AND d.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY d.domain_name ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Update multiple domains based on WHERE conditions
     */
    public function updateWhere(array $conditions, array $data): int
    {
        if (empty($conditions) || empty($data)) {
            return 0;
        }

        // Build WHERE clause
        $whereClause = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "{$field} = ?";
            $params[] = $value;
        }

        // Build SET clause
        $setClause = [];
        foreach ($data as $field => $value) {
            $setClause[] = "{$field} = ?";
            $params[] = $value;
        }

        $sql = "UPDATE domains SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
}

