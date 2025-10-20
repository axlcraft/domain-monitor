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
        // Get current user and isolation mode
        $userId = \Core\Auth::id();
        $settingModel = new \App\Models\Setting();
        $isolationMode = $settingModel->getValue('user_isolation_mode', 'shared');
        
        // Get filter parameters
        $search = \App\Helpers\InputValidator::sanitizeSearch($_GET['search'] ?? '', 100);
        $status = $_GET['status'] ?? '';
        $groupId = $_GET['group'] ?? '';
        $tag = $_GET['tag'] ?? '';
        $sortBy = $_GET['sort'] ?? 'domain_name';
        $sortOrder = $_GET['order'] ?? 'asc';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(100, (int)($_GET['per_page'] ?? 25))); // Between 10 and 100

        // Get expiring threshold from settings
        $notificationDays = $settingModel->getNotificationDays();
        $expiringThreshold = !empty($notificationDays) ? max($notificationDays) : 30;

        // Prepare filters array
        $filters = [
            'search' => $search,
            'status' => $status,
            'group' => $groupId,
            'tag' => $tag
        ];

        // Get filtered and paginated domains using model
        $result = $this->domainModel->getFilteredPaginated($filters, $sortBy, $sortOrder, $page, $perPage, $expiringThreshold, $userId);

        // Get groups and tags (always user-specific)
        $groups = $this->groupModel->getAllWithChannelCount($userId);
        $allTags = $this->domainModel->getAllTags($userId);
        
        // Format domains for display
        $formattedDomains = \App\Helpers\DomainHelper::formatMultiple($result['domains']);

        // Get users for transfer functionality (admin only)
        $users = [];
        if (\Core\Auth::isAdmin()) {
            $userModel = new \App\Models\User();
            $users = $userModel->all();
        }

        $this->view('domains/index', [
            'domains' => $formattedDomains,
            'groups' => $groups,
            'allTags' => $allTags,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'group' => $groupId,
                'tag' => $tag,
                'sort' => $sortBy,
                'order' => $sortOrder
            ],
            'pagination' => $result['pagination'],
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

        // CSRF Protection
        $this->verifyCsrf('/domains/create');

        $domainName = trim($_POST['domain_name'] ?? '');
        $groupId = !empty($_POST['notification_group_id']) ? (int)$_POST['notification_group_id'] : null;
        $tagsInput = trim($_POST['tags'] ?? '');

        // Validate
        if (empty($domainName)) {
            $_SESSION['error'] = 'Domain name is required';
            $this->redirect('/domains/create');
            return;
        }

        // Validate domain format
        if (!\App\Helpers\InputValidator::validateDomain($domainName)) {
            $_SESSION['error'] = 'Invalid domain name format (e.g., example.com)';
            $this->redirect('/domains/create');
            return;
        }

        // Validate tags
        $tagValidation = \App\Helpers\InputValidator::validateTags($tagsInput);
        if (!$tagValidation['valid']) {
            $_SESSION['error'] = $tagValidation['error'];
            $this->redirect('/domains/create');
            return;
        }
        $tags = $tagValidation['tags'];

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
            'tags' => $tags,
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

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $id = (int)($params['id'] ?? 0);
        $domain = $this->domainModel->find($id);
        
        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        $groupId = !empty($_POST['notification_group_id']) ? (int)$_POST['notification_group_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $tagsInput = trim($_POST['tags'] ?? '');
        
        // Validate tags
        $tagValidation = \App\Helpers\InputValidator::validateTags($tagsInput);
        if (!$tagValidation['valid']) {
            $_SESSION['error'] = $tagValidation['error'];
            $this->redirect('/domains/' . $id . '/edit');
            return;
        }
        $tags = $tagValidation['tags'];
        
        // Check if monitoring status changed
        $statusChanged = ($domain['is_active'] != $isActive);
        $oldGroupId = $domain['notification_group_id'];

        $this->domainModel->update($id, [
            'notification_group_id' => $groupId,
            'tags' => $tags,
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
        
        // Format domain for display
        $formattedDomain = \App\Helpers\DomainHelper::formatForDisplay($domain);
        
        // Parse WHOIS data for display
        $whoisData = json_decode($domain['whois_data'] ?? '{}', true);
        if (!empty($whoisData['status']) && is_array($whoisData['status'])) {
            $formattedDomain['parsedStatuses'] = \App\Helpers\DomainHelper::parseWhoisStatuses($whoisData['status']);
        } else {
            $formattedDomain['parsedStatuses'] = [];
        }
        
        // Calculate active channel count
        if (!empty($domain['channels'])) {
            $formattedDomain['activeChannelCount'] = \App\Helpers\DomainHelper::getActiveChannelCount($domain['channels']);
        }

        $this->view('domains/view', [
            'domain' => $formattedDomain,
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

        // CSRF Protection
        $this->verifyCsrf('/domains/bulk-add');

        // POST - Process bulk add
        $domainsText = trim($_POST['domains'] ?? '');
        $groupId = !empty($_POST['notification_group_id']) ? (int)$_POST['notification_group_id'] : null;
        $tagsInput = trim($_POST['tags'] ?? '');

        if (empty($domainsText)) {
            $_SESSION['error'] = 'Please enter at least one domain';
            $this->redirect('/domains/bulk-add');
            return;
        }

        // Validate tags
        $tagValidation = \App\Helpers\InputValidator::validateTags($tagsInput);
        if (!$tagValidation['valid']) {
            $_SESSION['error'] = $tagValidation['error'];
            $this->redirect('/domains/bulk-add');
            return;
        }
        $tags = $tagValidation['tags'];

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
                'tags' => $tags,
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

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        // Validate bulk operation size
        $sizeError = \App\Helpers\InputValidator::validateArraySize($domainIds, 1000, 'Domain selection');
        if ($sizeError) {
            $_SESSION['error'] = $sizeError;
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

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        // Validate bulk operation size
        $sizeError = \App\Helpers\InputValidator::validateArraySize($domainIds, 1000, 'Domain selection');
        if ($sizeError) {
            $_SESSION['error'] = $sizeError;
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

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];
        $groupId = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        // Validate bulk operation size
        $sizeError = \App\Helpers\InputValidator::validateArraySize($domainIds, 1000, 'Domain selection');
        if ($sizeError) {
            $_SESSION['error'] = $sizeError;
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

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        
        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        // Validate bulk operation size
        $sizeError = \App\Helpers\InputValidator::validateArraySize($domainIds, 1000, 'Domain selection');
        if ($sizeError) {
            $_SESSION['error'] = $sizeError;
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

    public function updateNotes($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $id = (int)($params['id'] ?? 0);
        $domain = $this->domainModel->find($id);
        
        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        $notes = $_POST['notes'] ?? '';

        // Validate notes length
        $lengthError = \App\Helpers\InputValidator::validateLength($notes, 5000, 'Notes');
        if ($lengthError) {
            $_SESSION['error'] = $lengthError;
            $this->redirect('/domains/' . $id);
            return;
        }

        $this->domainModel->update($id, [
            'notes' => $notes
        ]);

        $_SESSION['success'] = 'Notes updated successfully';
        $this->redirect('/domains/' . $id);
    }

    public function bulkAddTags()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];
        $tagToAdd = trim($_POST['tag'] ?? '');

        if (empty($domainIds) || empty($tagToAdd)) {
            $_SESSION['error'] = 'Invalid request';
            $this->redirect('/domains');
            return;
        }

        // Validate tag format
        if (!preg_match('/^[a-z0-9-]+$/', $tagToAdd)) {
            $_SESSION['error'] = 'Invalid tag format (use only letters, numbers, and hyphens)';
            $this->redirect('/domains');
            return;
        }

        $updated = 0;
        foreach ($domainIds as $id) {
            $domain = $this->domainModel->find($id);
            if (!$domain) continue;

            // Get existing tags
            $existingTags = !empty($domain['tags']) ? explode(',', $domain['tags']) : [];
            
            // Add new tag if it doesn't exist
            if (!in_array($tagToAdd, $existingTags)) {
                $existingTags[] = $tagToAdd;
                $newTags = implode(',', $existingTags);
                
                if ($this->domainModel->update($id, ['tags' => $newTags])) {
                    $updated++;
                }
            }
        }

        $_SESSION['success'] = "Tag '$tagToAdd' added to $updated domain(s)";
        $this->redirect('/domains');
    }

    public function bulkRemoveTags()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        // CSRF Protection
        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];

        if (empty($domainIds)) {
            $_SESSION['error'] = 'No domains selected';
            $this->redirect('/domains');
            return;
        }

        $updated = 0;
        foreach ($domainIds as $id) {
            if ($this->domainModel->update($id, ['tags' => ''])) {
                $updated++;
            }
        }

        $_SESSION['success'] = "Tags removed from $updated domain(s)";
        $this->redirect('/domains');
    }

    /**
     * Transfer domain to another user (Admin only)
     */
    public function transfer()
    {
        \Core\Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $this->verifyCsrf('/domains');

        $domainId = (int)($_POST['domain_id'] ?? 0);
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);

        if (!$domainId || !$targetUserId) {
            $_SESSION['error'] = 'Invalid domain or user selected';
            $this->redirect('/domains');
            return;
        }

        // Validate domain exists
        $domain = $this->domainModel->find($domainId);
        if (!$domain) {
            $_SESSION['error'] = 'Domain not found';
            $this->redirect('/domains');
            return;
        }

        // Validate target user exists
        $userModel = new \App\Models\User();
        $targetUser = $userModel->find($targetUserId);
        if (!$targetUser) {
            $_SESSION['error'] = 'Target user not found';
            $this->redirect('/domains');
            return;
        }

        try {
            // Transfer domain
            $this->domainModel->update($domainId, ['user_id' => $targetUserId]);
            
            $_SESSION['success'] = "Domain '{$domain['domain_name']}' transferred to {$targetUser['username']}";
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to transfer domain. Please try again.';
        }

        $this->redirect('/domains');
    }

    /**
     * Bulk transfer domains to another user (Admin only)
     */
    public function bulkTransfer()
    {
        \Core\Auth::requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/domains');
            return;
        }

        $this->verifyCsrf('/domains');

        $domainIds = $_POST['domain_ids'] ?? [];
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);

        if (empty($domainIds) || !$targetUserId) {
            $_SESSION['error'] = 'No domains selected or invalid user';
            $this->redirect('/domains');
            return;
        }

        // Validate target user exists
        $userModel = new \App\Models\User();
        $targetUser = $userModel->find($targetUserId);
        if (!$targetUser) {
            $_SESSION['error'] = 'Target user not found';
            $this->redirect('/domains');
            return;
        }

        $transferred = 0;
        foreach ($domainIds as $domainId) {
            $domainId = (int)$domainId;
            if ($domainId > 0) {
                try {
                    $this->domainModel->update($domainId, ['user_id' => $targetUserId]);
                    $transferred++;
                } catch (\Exception $e) {
                    // Continue with other domains
                }
            }
        }

        $_SESSION['success'] = "$transferred domain(s) transferred to {$targetUser['username']}";
        $this->redirect('/domains');
    }
}

