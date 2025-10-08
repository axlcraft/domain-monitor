<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Domain;
use App\Models\NotificationGroup;
use App\Services\WhoisService;

class DomainController extends Controller
{
    private Domain $domainModel;
    private NotificationGroup $groupModel;
    private WhoisService $whoisService;

    public function __construct()
    {
        $this->domainModel = new Domain();
        $this->groupModel = new NotificationGroup();
        $this->whoisService = new WhoisService();
    }

    public function index()
    {
        // Get filter parameters
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $groupId = $_GET['group'] ?? '';
        $sortBy = $_GET['sort'] ?? 'domain_name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 25))); // Between 10 and 100

        // Get expiring threshold from settings
        $settingModel = new \App\Models\Setting();
        $notificationDays = $settingModel->getNotificationDays();
        $expiringThreshold = !empty($notificationDays) ? max($notificationDays) : 30;

        // Get all domains with groups
        $domains = $this->domainModel->getAllWithGroups();

        // Apply filters
        if (!empty($search)) {
            $domains = array_filter($domains, function($domain) use ($search) {
                return stripos($domain['domain_name'], $search) !== false ||
                       stripos($domain['registrar'] ?? '', $search) !== false;
            });
        }

        if (!empty($status)) {
            $domains = array_filter($domains, function($domain) use ($status, $expiringThreshold) {
                if ($status === 'expiring_soon') {
                    // Check if domain expires within configured threshold
                    if (!empty($domain['expiration_date'])) {
                        $daysLeft = floor((strtotime($domain['expiration_date']) - time()) / 86400);
                        return $daysLeft <= $expiringThreshold && $daysLeft >= 0;
                    }
                    return false;
                }
                return $domain['status'] === $status;
            });
        }

        if (!empty($groupId)) {
            $domains = array_filter($domains, function($domain) use ($groupId) {
                return $domain['notification_group_id'] == $groupId;
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

        $groups = $this->groupModel->all();

        $this->view('domains/index', [
            'domains' => $paginatedDomains,
            'groups' => $groups,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'group' => $groupId,
                'sort' => $sortBy,
                'order' => $sortOrder
            ],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalDomains,
                'total_pages' => $totalPages,
                'showing_from' => $totalDomains > 0 ? $offset + 1 : 0,
                'showing_to' => min($offset + $perPage, $totalDomains)
            ],
            'title' => 'Domains'
        ]);
    }

    public function create()
    {
        $groups = $this->groupModel->all();

        $this->view('domains/create', [
            'groups' => $groups,
            'title' => 'Add Domain'
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains/create');
            return;
        }

        $domainName = trim($_POST['domain_name'] ?? '');
        $groupId = !empty($_POST['notification_group_id']) ? (int)$_POST['notification_group_id'] : null;

        // Validate
        if (empty($domainName)) {
            $_SESSION['error'] = 'Domain name is required';
            $this->redirect('/domains/create');
            return;
        }

        // Check if domain already exists
        if ($this->domainModel->existsByDomain($domainName)) {
            $_SESSION['error'] = 'Domain already exists';
            $this->redirect('/domains/create');
            return;
        }

        // Get WHOIS information
        $whoisData = $this->whoisService->getDomainInfo($domainName);

        if (!$whoisData) {
            $_SESSION['error'] = 'Could not retrieve WHOIS information for this domain';
            $this->redirect('/domains/create');
            return;
        }

        // Create domain
        $status = $this->whoisService->getDomainStatus($whoisData['expiration_date'], $whoisData['status'] ?? []);

        // Warn if domain is available (not registered)
        if ($status === 'available') {
            $_SESSION['warning'] = "Note: '$domainName' appears to be AVAILABLE (not registered). You're monitoring an unregistered domain.";
        }

        $id = $this->domainModel->create([
            'domain_name' => $domainName,
            'notification_group_id' => $groupId,
            'registrar' => $whoisData['registrar'],
            'registrar_url' => $whoisData['registrar_url'] ?? null,
            'expiration_date' => $whoisData['expiration_date'],
            'updated_date' => $whoisData['updated_date'] ?? null,
            'abuse_email' => $whoisData['abuse_email'] ?? null,
            'last_checked' => date('Y-m-d H:i:s'),
            'status' => $status,
            'whois_data' => json_encode($whoisData),
            'is_active' => 1
        ]);

        if ($status !== 'available') {
            $_SESSION['success'] = "Domain '$domainName' added successfully";
        }
        $this->redirect('/domains');
    }

    public function edit($params = [])
    {
        $id = $params['id'] ?? 0;
        $domain = $this->domainModel->find($id);

        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        $groups = $this->groupModel->all();

        $this->view('domains/edit', [
            'domain' => $domain,
            'groups' => $groups,
            'title' => 'Edit Domain'
        ]);
    }

    public function update($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $id = (int)($params['id'] ?? 0);
        $domain = $this->domainModel->find($id);
        
        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        $groupId = !empty($_POST['notification_group_id']) ? (int)$_POST['notification_group_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Check if monitoring status changed
        $statusChanged = ($domain['is_active'] != $isActive);
        $oldGroupId = $domain['notification_group_id'];

        $this->domainModel->update($id, [
            'notification_group_id' => $groupId,
            'is_active' => $isActive
        ]);

        // Send notification if monitoring status changed and has notification group
        if ($statusChanged && $groupId) {
            $notificationService = new \App\Services\NotificationService();
            
            if ($isActive) {
                // Monitoring activated
                $message = "🟢 Domain monitoring has been ACTIVATED for {$domain['domain_name']}\n\n" .
                          "The domain will now be monitored regularly and you'll receive expiration alerts.";
                $subject = "✅ Monitoring Activated: {$domain['domain_name']}";
            } else {
                // Monitoring deactivated
                $message = "🔴 Domain monitoring has been DEACTIVATED for {$domain['domain_name']}\n\n" .
                          "You will no longer receive alerts for this domain until monitoring is re-enabled.";
                $subject = "⏸️ Monitoring Paused: {$domain['domain_name']}";
            }
            
            $notificationService->sendToGroup($groupId, $subject, $message);
        }
        
        // Also send notification if group changed and monitoring is active
        if (!$statusChanged && $isActive && $oldGroupId != $groupId) {
            $notificationService = new \App\Services\NotificationService();
            
            if ($groupId) {
                // Assigned to new group
                $groupModel = new NotificationGroup();
                $group = $groupModel->find($groupId);
                $groupName = $group ? $group['name'] : 'Unknown Group';
                
                $message = "🔔 Notification group updated for {$domain['domain_name']}\n\n" .
                          "This domain is now assigned to: {$groupName}\n" .
                          "You will receive expiration alerts through this notification group.";
                $subject = "📬 Group Changed: {$domain['domain_name']}";
                
                $notificationService->sendToGroup($groupId, $subject, $message);
            }
        }

        $_SESSION['success'] = 'Domain updated successfully';
        $this->redirect('/domains/' . $id);
    }

    public function refresh($params = [])
    {
        $id = $params['id'] ?? 0;
        $domain = $this->domainModel->find($id);

        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        // Get fresh WHOIS information
        $whoisData = $this->whoisService->getDomainInfo($domain['domain_name']);

        if (!$whoisData) {
            $_SESSION['error'] = 'Could not retrieve WHOIS information';
            // Check if we came from view page
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (strpos($referer, '/domains/' . $id) !== false) {
                $this->redirect('/domains/' . $id);
            } else {
                $this->redirect('/domains');
            }
            return;
        }

        $status = $this->whoisService->getDomainStatus($whoisData['expiration_date'], $whoisData['status'] ?? []);

        $this->domainModel->update($id, [
            'registrar' => $whoisData['registrar'],
            'registrar_url' => $whoisData['registrar_url'] ?? null,
            'expiration_date' => $whoisData['expiration_date'],
            'updated_date' => $whoisData['updated_date'] ?? null,
            'abuse_email' => $whoisData['abuse_email'] ?? null,
            'last_checked' => date('Y-m-d H:i:s'),
            'status' => $status,
            'whois_data' => json_encode($whoisData)
        ]);

        $_SESSION['success'] = 'Domain information refreshed';
        
        // Check if we came from view page or list page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, '/domains/' . $id) !== false) {
            // Came from view page, go back to view page
            $this->redirect('/domains/' . $id);
        } else {
            // Came from list page, stay on list page
            $this->redirect('/domains');
        }
    }

    public function delete($params = [])
    {
        $id = $params['id'] ?? 0;
        $domain = $this->domainModel->find($id);

        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        $this->domainModel->delete($id);
        $_SESSION['success'] = 'Domain deleted successfully';
        $this->redirect('/domains');
    }

    public function show($params = [])
    {
        $id = $params['id'] ?? 0;
        $domain = $this->domainModel->getWithChannels($id);

        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        $logModel = new \App\Models\NotificationLog();
        $logs = $logModel->getByDomain($id, 20);

        $this->view('domains/view', [
            'domain' => $domain,
            'logs' => $logs,
            'title' => $domain['domain_name']
        ]);
    }

    public function bulkAdd()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $groups = $this->groupModel->all();
            $this->view('domains/bulk-add', [
                'groups' => $groups,
                'title' => 'Bulk Add Domains'
            ]);
            return;
        }

        // POST - Process bulk add
        $domainsText = trim($_POST['domains'] ?? '');
        $groupId = !empty($_POST['notification_group_id']) ? (int)$_POST['notification_group_id'] : null;

        if (empty($domainsText)) {
            $_SESSION['error'] = 'Please enter at least one domain';
            $this->redirect('/domains/bulk-add');
            return;
        }

        // Split by new lines and clean
        $domainNames = array_filter(array_map('trim', explode("\n", $domainsText)));
        
        $added = 0;
        $skipped = 0;
        $availableCount = 0;
        $errors = [];

        foreach ($domainNames as $domainName) {
            // Skip if already exists
            if ($this->domainModel->existsByDomain($domainName)) {
                $skipped++;
                continue;
            }

            // Get WHOIS information
            $whoisData = $this->whoisService->getDomainInfo($domainName);

            if (!$whoisData) {
                $errors[] = $domainName;
                continue;
            }

            $status = $this->whoisService->getDomainStatus($whoisData['expiration_date'], $whoisData['status'] ?? []);

            // Track available domains
            if ($status === 'available') {
                $availableCount++;
            }

            $this->domainModel->create([
                'domain_name' => $domainName,
                'notification_group_id' => $groupId,
                'registrar' => $whoisData['registrar'],
                'registrar_url' => $whoisData['registrar_url'] ?? null,
                'expiration_date' => $whoisData['expiration_date'],
                'updated_date' => $whoisData['updated_date'] ?? null,
                'abuse_email' => $whoisData['abuse_email'] ?? null,
                'last_checked' => date('Y-m-d H:i:s'),
                'status' => $status,
                'whois_data' => json_encode($whoisData),
                'is_active' => 1
            ]);

            $added++;
        }

        $message = "Added $added domain(s)";
        if ($skipped > 0) $message .= ", skipped $skipped duplicate(s)";
        if (count($errors) > 0) $message .= ", failed to add " . count($errors) . " domain(s)";

        if ($availableCount > 0) {
            $_SESSION['warning'] = "Note: $availableCount domain(s) appear to be AVAILABLE (not registered).";
        }
        
        $_SESSION['success'] = $message;
        $this->redirect('/domains');
    }

    public function bulkRefresh()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $domainIds = $_POST['domain_ids'] ?? [];
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        $refreshed = 0;
        $failed = 0;

        foreach ($domainIds as $id) {
            $domain = $this->domainModel->find($id);
            if (!$domain) continue;

            $whoisData = $this->whoisService->getDomainInfo($domain['domain_name']);

            if (!$whoisData) {
                $failed++;
                continue;
            }

            $status = $this->whoisService->getDomainStatus($whoisData['expiration_date'], $whoisData['status'] ?? []);

            $this->domainModel->update($id, [
                'registrar' => $whoisData['registrar'],
                'registrar_url' => $whoisData['registrar_url'] ?? null,
                'expiration_date' => $whoisData['expiration_date'],
                'updated_date' => $whoisData['updated_date'] ?? null,
                'abuse_email' => $whoisData['abuse_email'] ?? null,
                'last_checked' => date('Y-m-d H:i:s'),
                'status' => $status,
                'whois_data' => json_encode($whoisData)
            ]);

            $refreshed++;
        }

        $_SESSION['success'] = "Refreshed $refreshed domain(s)" . ($failed > 0 ? ", $failed failed" : '');
        $this->redirect('/domains');
    }

    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $domainIds = $_POST['domain_ids'] ?? [];
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        $deleted = 0;
        foreach ($domainIds as $id) {
            if ($this->domainModel->delete($id)) {
                $deleted++;
            }
        }

        $_SESSION['success'] = "Deleted $deleted domain(s)";
        $this->redirect('/domains');
    }

    public function bulkAssignGroup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $domainIds = $_POST['domain_ids'] ?? [];
        $groupId = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        $updated = 0;
        foreach ($domainIds as $id) {
            if ($this->domainModel->update($id, ['notification_group_id' => $groupId])) {
                $updated++;
            }
        }

        $_SESSION['success'] = "Updated $updated domain(s)";
        $this->redirect('/domains');
    }

    public function bulkToggleStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $domainIds = $_POST['domain_ids'] ?? [];
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        $updated = 0;
        foreach ($domainIds as $id) {
            if ($this->domainModel->update($id, ['is_active' => $isActive])) {
                $updated++;
            }
        }

        $status = $isActive ? 'enabled' : 'disabled';
        $_SESSION['success'] = "Monitoring $status for $updated domain(s)";
        $this->redirect('/domains');
    }
}

