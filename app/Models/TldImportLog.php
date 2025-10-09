<?php

namespace App\Models;

use Core\Model;

class TldImportLog extends Model
{
    protected static string $table = 'tld_import_logs';

    /**
     * Create a new import log entry
     */
    public function startImport(string $importType, ?string $ianaPublicationDate = null): int
    {
        return $this->create([
            'import_type' => $importType,
            'iana_publication_date' => $ianaPublicationDate,
            'status' => 'running',
            'started_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Complete an import log entry
     */
    public function completeImport(int $logId, array $stats, ?string $status = null, ?string $errorMessage = null, ?array $details = null): bool
    {
        $data = [
            'total_tlds' => $stats['total_tlds'] ?? 0,
            'new_tlds' => $stats['new_tlds'] ?? 0,
            'updated_tlds' => $stats['updated_tlds'] ?? 0,
            'failed_tlds' => $stats['failed_tlds'] ?? 0,
            'completed_at' => date('Y-m-d H:i:s'),
            'status' => $status ?? ($errorMessage ? 'failed' : 'completed'),
            'error_message' => $errorMessage
        ];

        if ($details !== null) {
            $data['details'] = json_encode($details);
        }

        return $this->update($logId, $data);
    }

    /**
     * Update an import log entry (for progress tracking)
     */
    public function update(int $logId, array $data, ?string $status = null, ?string $errorMessage = null, ?array $details = null): bool
    {
        if ($status !== null) {
            $data['status'] = $status;
        }
        
        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }
        
        if ($details !== null) {
            $data['details'] = json_encode($details);
        }

        return parent::update($logId, $data);
    }

    /**
     * Get recent import logs
     */
    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT *, 
                       COALESCE(new_tlds, 0) as new_tlds
                FROM tld_import_logs 
                ORDER BY started_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get import statistics
     */
    public function getImportStatistics(): array
    {
        $stats = [
            'total_imports' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'last_import' => null,
            'total_tlds_imported' => 0
        ];

        // Total imports
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM tld_import_logs");
        $stats['total_imports'] = $stmt->fetch()['count'];

        // Successful imports
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM tld_import_logs WHERE status = 'completed'");
        $stats['successful_imports'] = $stmt->fetch()['count'];

        // Failed imports
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM tld_import_logs WHERE status = 'failed'");
        $stats['failed_imports'] = $stmt->fetch()['count'];

        // Last import
        $stmt = $this->db->query("SELECT * FROM tld_import_logs ORDER BY started_at DESC LIMIT 1");
        $lastImport = $stmt->fetch();
        if ($lastImport) {
            $stats['last_import'] = $lastImport['started_at'];
        }

        // Total TLDs imported
        $stmt = $this->db->query("SELECT SUM(total_tlds) as total FROM tld_import_logs WHERE status = 'completed'");
        $result = $stmt->fetch();
        $stats['total_tlds_imported'] = $result['total'] ?? 0;

        return $stats;
    }

    /**
     * Get import logs with pagination
     */
    public function getPaginated(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT *, 
                       COALESCE(new_tlds, 0) as new_tlds
                FROM tld_import_logs 
                ORDER BY started_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$perPage, $offset]);
        $logs = $stmt->fetchAll();
        
        // Get total count
        $countStmt = $this->db->query("SELECT COUNT(*) as count FROM tld_import_logs");
        $total = $countStmt->fetch()['count'];

        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'showing_from' => $total > 0 ? $offset + 1 : 0,
                'showing_to' => min($offset + $perPage, $total)
            ]
        ];
    }

    /**
     * Execute a custom SQL query
     */
    public function query(string $sql): array
    {
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
