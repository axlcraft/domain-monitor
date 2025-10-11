<?php

namespace App\Models;

use Core\Model;

/**
 * ErrorLog Model
 * 
 * Manages error log database operations for tracking and debugging
 */
class ErrorLog extends Model
{
    protected static string $table = 'error_logs';

    /**
     * Log an error to database
     * If the same error exists (same file + line + type), increment occurrence count
     */
    public function logError(array $errorData): ?int
    {
        // Generate unique error signature for deduplication
        $signature = md5($errorData['error_type'] . $errorData['error_file'] . $errorData['error_line']);
        
        // Check if this error already exists
        $existing = $this->findBySimilar(
            $errorData['error_type'],
            $errorData['error_file'],
            $errorData['error_line']
        );
        
        if ($existing) {
            // Update existing error
            $this->incrementOccurrence($existing['id']);
            return $existing['id'];
        }
        
        // Create new error log
        return $this->create($errorData);
    }

    /**
     * Find similar error (same type, file, line)
     */
    private function findBySimilar(string $type, string $file, int $line): ?array
    {
        $sql = "SELECT * FROM error_logs 
                WHERE error_type = ? 
                AND error_file = ? 
                AND error_line = ?
                AND is_resolved = FALSE
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type, $file, $line]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Increment occurrence counter
     */
    private function incrementOccurrence(int $id): void
    {
        $sql = "UPDATE error_logs 
                SET occurrences = occurrences + 1,
                    last_occurred_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Find error by error_id (unique reference)
     */
    public function findByErrorId(string $errorId): ?array
    {
        $sql = "SELECT el.*, u.username, u.full_name, r.username as resolved_by_name
                FROM error_logs el
                LEFT JOIN users u ON el.user_id = u.id
                LEFT JOIN users r ON el.resolved_by = r.id
                WHERE el.error_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$errorId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Get recent errors with pagination
     */
    public function getRecent(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        
        // Filter by resolution status
        if (isset($filters['resolved'])) {
            $where[] = 'el.is_resolved = ?';
            $params[] = $filters['resolved'] ? 1 : 0;
        }
        
        // Filter by error type
        if (!empty($filters['type'])) {
            $where[] = 'el.error_type LIKE ?';
            $params[] = '%' . $filters['type'] . '%';
        }
        
        // Filter by user
        if (!empty($filters['user_id'])) {
            $where[] = 'el.user_id = ?';
            $params[] = $filters['user_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT el.*, u.username, u.full_name
                FROM error_logs el
                LEFT JOIN users u ON el.user_id = u.id
                WHERE {$whereClause}
                ORDER BY el.last_occurred_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count total errors
     */
    public function count(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        
        if (isset($filters['resolved'])) {
            $where[] = 'is_resolved = ?';
            $params[] = $filters['resolved'] ? 1 : 0;
        }
        
        if (!empty($filters['type'])) {
            $where[] = 'error_type LIKE ?';
            $params[] = '%' . $filters['type'] . '%';
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = ?';
            $params[] = $filters['user_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) as count FROM error_logs WHERE {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Get error statistics
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_errors,
                    SUM(occurrences) as total_occurrences,
                    COUNT(CASE WHEN is_resolved = FALSE THEN 1 END) as unresolved,
                    COUNT(CASE WHEN is_resolved = TRUE THEN 1 END) as resolved,
                    COUNT(CASE WHEN occurred_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h,
                    COUNT(CASE WHEN occurred_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as last_7d
                FROM error_logs";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }

    /**
     * Mark error as resolved
     */
    public function resolve(int $id, int $resolvedBy, ?string $notes = null): bool
    {
        return $this->update($id, [
            'is_resolved' => true,
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolved_by' => $resolvedBy,
            'notes' => $notes
        ]);
    }

    /**
     * Delete old resolved errors
     */
    public function deleteOldResolved(int $daysOld = 30): int
    {
        $sql = "DELETE FROM error_logs 
                WHERE is_resolved = TRUE 
                AND resolved_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysOld]);
        return $stmt->rowCount();
    }

    /**
     * Get most frequent errors
     */
    public function getMostFrequent(int $limit = 10): array
    {
        $sql = "SELECT el.*, u.username, u.full_name
                FROM error_logs el
                LEFT JOIN users u ON el.user_id = u.id
                WHERE el.is_resolved = FALSE
                ORDER BY el.occurrences DESC, el.last_occurred_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get paginated errors with filters for admin panel
     */
    public function getPaginatedErrors(array $filters, int $perPage, int $offset): array
    {
        $where = [];
        $params = [];

        if ($filters['resolved'] !== '') {
            $where[] = 'is_resolved = ?';
            $params[] = (int)$filters['resolved'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'error_type LIKE ?';
            $params[] = '%' . $filters['type'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sortColumn = $filters['sort'];
        $sortOrder = strtoupper($filters['order']) === 'DESC' ? 'DESC' : 'ASC';

        $query = "
            SELECT 
                error_id,
                error_type,
                error_message,
                error_file,
                error_line,
                is_resolved,
                MIN(occurred_at) as occurred_at,
                MAX(occurred_at) as last_occurred_at,
                COUNT(*) as occurrences
            FROM error_logs
            $whereClause
            GROUP BY error_id
            ORDER BY $sortColumn $sortOrder
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([...$params, $perPage, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Count total unique errors with filters
     */
    public function countUniqueErrors(array $filters): int
    {
        $where = [];
        $params = [];

        if ($filters['resolved'] !== '') {
            $where[] = 'is_resolved = ?';
            $params[] = (int)$filters['resolved'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'error_type LIKE ?';
            $params[] = '%' . $filters['type'] . '%';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT COUNT(DISTINCT error_id) as total FROM error_logs $whereClause";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'];
    }

    /**
     * Get all occurrences of a specific error
     */
    public function getOccurrencesByErrorId(string $errorId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM error_logs 
            WHERE error_id = ? 
            ORDER BY occurred_at DESC
        ");
        $stmt->execute([$errorId]);
        return $stmt->fetchAll();
    }

    /**
     * Get admin statistics
     */
    public function getAdminStats(): array
    {
        // Total unique errors
        $stmt = $this->db->query("SELECT COUNT(DISTINCT error_id) as total FROM error_logs");
        $totalErrors = $stmt->fetch()['total'];

        // Unresolved errors
        $stmt = $this->db->query("SELECT COUNT(DISTINCT error_id) as total FROM error_logs WHERE is_resolved = 0");
        $unresolved = $stmt->fetch()['total'];

        // Errors in last 24h
        $stmt = $this->db->query("SELECT COUNT(DISTINCT error_id) as total FROM error_logs WHERE occurred_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $last24h = $stmt->fetch()['total'];

        // Total occurrences
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM error_logs");
        $totalOccurrences = $stmt->fetch()['total'];

        return [
            'total_errors' => $totalErrors,
            'unresolved' => $unresolved,
            'last_24h' => $last24h,
            'total_occurrences' => $totalOccurrences
        ];
    }

    /**
     * Mark all occurrences of an error as resolved
     */
    public function markErrorResolved(string $errorId, int $userId, ?string $notes): bool
    {
        $stmt = $this->db->prepare("
            UPDATE error_logs 
            SET is_resolved = 1, 
                resolved_at = NOW(), 
                resolved_by = ?,
                notes = ?
            WHERE error_id = ?
        ");
        
        return $stmt->execute([$userId, $notes, $errorId]);
    }

    /**
     * Mark all occurrences of an error as unresolved
     */
    public function markErrorUnresolved(string $errorId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE error_logs 
            SET is_resolved = 0, 
                resolved_at = NULL, 
                resolved_by = NULL,
                notes = NULL
            WHERE error_id = ?
        ");
        
        return $stmt->execute([$errorId]);
    }

    /**
     * Delete all occurrences of an error
     */
    public function deleteByErrorId(string $errorId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM error_logs WHERE error_id = ?");
        return $stmt->execute([$errorId]);
    }

    /**
     * Clear old resolved errors
     */
    public function clearOldResolved(int $daysOld): int
    {
        $stmt = $this->db->prepare("
            DELETE FROM error_logs 
            WHERE is_resolved = 1 
            AND resolved_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$daysOld]);
        return $stmt->rowCount();
    }
}

