<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\TldRegistry;
use App\Models\TldImportLog;
use App\Services\TldRegistryService;

class TldRegistryController extends Controller
{
    private TldRegistry $tldModel;
    private TldImportLog $importLogModel;
    private TldRegistryService $tldService;

    public function __construct()
    {
        $this->tldModel = new TldRegistry();
        $this->importLogModel = new TldImportLog();
        $this->tldService = new TldRegistryService();
    }
    
    /**
     * Check if current user is admin
     */
    private function requireAdmin()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = 'Access denied. Admin privileges required.';
            $this->redirect('/tld-registry');
            exit;
        }
    }

    /**
     * Display TLD registry dashboard
     */
    public function index()
    {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $dataType = $_GET['data_type'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 50)));
        $sort = $_GET['sort'] ?? 'tld';
        $order = $_GET['order'] ?? 'asc';

        $result = $this->tldModel->getPaginated($page, $perPage, $search, $sort, $order, $status, $dataType);
        $stats = $this->tldModel->getStatistics();

        $this->view('tld-registry/index', [
            'tlds' => $result['tlds'],
            'pagination' => $result['pagination'],
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'data_type' => $dataType,
                'sort' => $sort,
                'order' => $order
            ],
            'title' => 'TLD Registry'
        ]);
    }

    /**
     * Show TLD details
     */
    public function show($params = [])
    {
        $id = $params['id'] ?? 0;
        $tld = $this->tldModel->find($id);

        if (!$tld) {
            $_SESSION['error'] = 'TLD not found';
            $this->redirect('/tld-registry');
            return;
        }

        $this->view('tld-registry/view', [
            'tld' => $tld,
            'title' => 'TLD: ' . $tld['tld']
        ]);
    }

    /**
     * Import TLD list from IANA
     */
    public function importTldList()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tld-registry');
            return;
        }

        try {
            $stats = $this->tldService->importTldList();
            
            $message = "TLD list import completed: ";
            $message .= "{$stats['total_tlds']} total, ";
            $message .= "{$stats['new_tlds']} new, ";
            $message .= "{$stats['updated_tlds']} updated";
            
            if ($stats['failed_tlds'] > 0) {
                $message .= ", {$stats['failed_tlds']} failed";
            }
            
            $message .= ". Next: Import RDAP servers for these TLDs.";
            
            $_SESSION['success'] = $message;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'TLD list import failed: ' . $e->getMessage();
        }

        $this->redirect('/tld-registry');
    }

    /**
     * Import RDAP data from IANA
     */
    public function importRdap()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tld-registry');
            return;
        }

        try {
            $stats = $this->tldService->importRdapData();
            
            $message = "RDAP import completed: ";
            $message .= "{$stats['total_tlds']} total, ";
            $message .= "{$stats['new_tlds']} new, ";
            $message .= "{$stats['updated_tlds']} updated";
            
            if ($stats['failed_tlds'] > 0) {
                $message .= ", {$stats['failed_tlds']} failed";
            }
            
            $message .= ". Next: Import WHOIS servers for TLDs missing RDAP.";
            
            $_SESSION['success'] = $message;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'RDAP import failed: ' . $e->getMessage();
        }

        $this->redirect('/tld-registry');
    }

    /**
     * Import WHOIS data for missing TLDs
     */
    public function importWhois()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tld-registry');
            return;
        }

        try {
            $stats = $this->tldService->importWhoisDataForMissingTlds();
            $remainingCount = $this->tldService->getTldsNeedingWhoisCount();
            
            $message = "WHOIS import completed: ";
            $message .= "{$stats['total_tlds']} total, ";
            $message .= "{$stats['updated_tlds']} updated";
            
            if ($stats['failed_tlds'] > 0) {
                $message .= ", {$stats['failed_tlds']} failed";
            }
            
            if ($remainingCount > 0) {
                $message .= ". {$remainingCount} TLDs still need WHOIS data. Run import again to continue.";
            } else {
                $message .= ". TLD registry setup complete! Use 'Check Updates' to monitor for changes.";
            }
            
            $_SESSION['success'] = $message;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'WHOIS import failed: ' . $e->getMessage();
        }

        $this->redirect('/tld-registry');
    }

    /**
     * Check for IANA updates
     */
    public function checkUpdates()
    {
        $this->requireAdmin();
        
        try {
            $updateInfo = $this->tldService->checkForUpdates();
            
            if ($updateInfo['overall_needs_update']) {
                $messages = [];
                
                if ($updateInfo['tld_list']['needs_update']) {
                    $messages[] = "TLD list updated: Version " . 
                        ($updateInfo['tld_list']['current_version'] ?? 'Unknown') . 
                        " (was " . ($updateInfo['tld_list']['last_version'] ?? 'None') . ")";
                }
                
                if ($updateInfo['rdap']['needs_update']) {
                    $messages[] = "RDAP data updated: " . 
                        ($updateInfo['rdap']['current_publication'] ?? 'Unknown') . 
                        " (was " . ($updateInfo['rdap']['last_publication'] ?? 'None') . ")";
                }
                
                $_SESSION['info'] = "IANA data has been updated. " . implode(' | ', $messages);
            } else {
                $_SESSION['success'] = "TLD registry is up to date";
            }
            
            // Show any errors
            if (!empty($updateInfo['errors'])) {
                $_SESSION['warning'] = "Some checks failed: " . implode(', ', $updateInfo['errors']);
            }
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to check for updates: ' . $e->getMessage();
        }

        $this->redirect('/tld-registry');
    }

    /**
     * Start progressive import (universal)
     */
    public function startProgressiveImport()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tld-registry');
            return;
        }

        $importType = $_POST['import_type'] ?? '';
        
        if (!in_array($importType, ['tld_list', 'rdap', 'whois', 'check_updates', 'complete_workflow'])) {
            $_SESSION['error'] = 'Invalid import type';
            $this->redirect('/tld-registry');
            return;
        }

        try {
            $result = $this->tldService->startProgressiveImport($importType);
            
            if ($result['status'] === 'complete') {
                $_SESSION['success'] = $result['message'];
                $this->redirect('/tld-registry');
            } else {
                // Redirect to progress page
                $this->redirect('/tld-registry/import-progress/' . $result['log_id']);
            }
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to start import: ' . $e->getMessage();
            $this->redirect('/tld-registry');
        }
    }

    /**
     * Show import progress page (universal)
     */
    public function importProgress($params = [])
    {
        $logId = $params['log_id'] ?? 0;
        
        if (!$logId) {
            $_SESSION['error'] = 'Invalid import session';
            $this->redirect('/tld-registry');
            return;
        }

        // Get import type from log
        $log = $this->importLogModel->find($logId);
        if (!$log) {
            $_SESSION['error'] = 'Import log not found';
            $this->redirect('/tld-registry');
            return;
        }

        $importType = $log['import_type'];
        $titles = [
            'tld_list' => 'TLD List Import Progress',
            'rdap' => 'RDAP Import Progress',
            'whois' => 'WHOIS Import Progress',
            'check_updates' => 'Update Check Progress'
        ];

        $this->view('tld-registry/import-progress', [
            'log_id' => $logId,
            'import_type' => $importType,
            'title' => $titles[$importType] ?? 'Import Progress'
        ]);
    }

    /**
     * API endpoint to get import progress
     */
    public function apiGetImportProgress()
    {
        $logId = $_GET['log_id'] ?? 0;
        
        if (!$logId) {
            http_response_code(400);
            echo json_encode(['error' => 'Log ID required']);
            return;
        }

        try {
            $result = $this->tldService->processNextBatch($logId);
            echo json_encode($result);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk delete TLDs
     */
    public function bulkDelete()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tld-registry');
            return;
        }

        $tldIds = $_POST['tld_ids'] ?? [];
        
        if (empty($tldIds)) {
            $_SESSION['error'] = 'No TLDs selected for deletion';
            $this->redirect('/tld-registry');
            return;
        }

        try {
            $deletedCount = 0;
            foreach ($tldIds as $id) {
                if ($this->tldModel->delete($id)) {
                    $deletedCount++;
                }
            }
            
            $_SESSION['success'] = "Successfully deleted {$deletedCount} TLD(s)";
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to delete TLDs: ' . $e->getMessage();
        }

        $this->redirect('/tld-registry');
    }

    /**
     * Toggle TLD active status
     */
    public function toggleActive($params = [])
    {
        $this->requireAdmin();
        
        $id = $params['id'] ?? 0;
        $tld = $this->tldModel->find($id);

        if (!$tld) {
            $_SESSION['error'] = 'TLD not found';
            $this->redirect('/tld-registry');
            return;
        }

        $this->tldModel->toggleActive($id);
        
        $status = $tld['is_active'] ? 'disabled' : 'enabled';
        $_SESSION['success'] = "TLD {$tld['tld']} has been {$status}";
        
        $this->redirect('/tld-registry');
    }

    /**
     * Refresh TLD data from IANA
     */
    public function refresh($params = [])
    {
        $this->requireAdmin();
        
        $id = $params['id'] ?? 0;
        $tld = $this->tldModel->find($id);

        if (!$tld) {
            $_SESSION['error'] = 'TLD not found';
            $this->redirect('/tld-registry');
            return;
        }

        try {
            // Remove dot from TLD for URL
            $tldForUrl = ltrim($tld['tld'], '.');
            $url = "https://www.iana.org/domains/root/db/{$tldForUrl}.html";

            $client = new \GuzzleHttp\Client();
            $response = $client->get($url);
            $html = $response->getBody()->getContents();

            // Extract data from HTML
            $whoisServer = $this->extractWhoisServer($html);
            $lastUpdated = $this->extractLastUpdated($html);
            $registryUrl = $this->extractRegistryUrl($html);
            $registrationDate = $this->extractRegistrationDate($html);

            $updateData = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($whoisServer) $updateData['whois_server'] = $whoisServer;
            if ($lastUpdated) $updateData['record_last_updated'] = $lastUpdated;
            if ($registryUrl) $updateData['registry_url'] = $registryUrl;
            if ($registrationDate) $updateData['registration_date'] = $registrationDate;

            $this->tldModel->update($id, $updateData);
            
            $_SESSION['success'] = "TLD {$tld['tld']} data refreshed successfully";
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to refresh TLD data: ' . $e->getMessage();
        }

        $this->redirect('/tld-registry');
    }

    /**
     * Show import logs
     */
    public function importLogs()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 20)));

        $result = $this->importLogModel->getPaginated($page, $perPage);
        $importStats = $this->importLogModel->getImportStatistics();

        $this->view('tld-registry/import-logs', [
            'imports' => $result['logs'],
            'pagination' => $result['pagination'],
            'stats' => $importStats,
            'title' => 'TLD Import Logs'
        ]);
    }

    /**
     * API endpoint to get TLD info for a domain
     */
    public function apiGetTldInfo()
    {
        $domain = $_GET['domain'] ?? '';
        
        if (empty($domain)) {
            http_response_code(400);
            echo json_encode(['error' => 'Domain parameter is required']);
            return;
        }

        try {
            $tldInfo = $this->tldService->getTldInfo($domain);
            
            if ($tldInfo) {
                echo json_encode([
                    'success' => true,
                    'data' => $tldInfo
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'TLD information not found'
                ]);
            }
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract WHOIS server from HTML
     */
    private function extractWhoisServer(string $html): ?string
    {
        if (preg_match('/WHOIS Server:\s*([^\s<]+)/i', $html, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Extract last updated date from HTML
     */
    private function extractLastUpdated(string $html): ?string
    {
        if (preg_match('/Record last updated\s+(\d{4}-\d{2}-\d{2})/i', $html, $matches)) {
            return $matches[1] . ' 00:00:00';
        }
        return null;
    }

    /**
     * Extract registry URL from HTML
     */
    private function extractRegistryUrl(string $html): ?string
    {
        if (preg_match('/URL for registration services:\s*([^\s<]+)/i', $html, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Extract registration date from HTML
     */
    private function extractRegistrationDate(string $html): ?string
    {
        if (preg_match('/Registration date\s+(\d{4}-\d{2}-\d{2})/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
