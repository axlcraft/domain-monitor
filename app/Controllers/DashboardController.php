<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Domain;
use App\Models\NotificationGroup;
use App\Models\NotificationLog;

class DashboardController extends Controller
{
    private Domain $domainModel;
    private NotificationGroup $groupModel;
    private NotificationLog $logModel;

    public function __construct()
    {
        $this->domainModel = new Domain();
        $this->groupModel = new NotificationGroup();
        $this->logModel = new NotificationLog();
    }

    public function index()
    {
        $stats = $this->domainModel->getStatistics();
        $recentDomains = $this->domainModel->getRecent(5); // Get 5 most recent domains
        
        // Get expiring threshold from settings
        $settingModel = new \App\Models\Setting();
        $notificationDays = $settingModel->getNotificationDays();
        $expiringThreshold = !empty($notificationDays) ? max($notificationDays) : 30;
        
        // Get expiring domains limited to top 5
        $allExpiringDomains = $this->domainModel->getExpiringDomains($expiringThreshold);
        $expiringThisMonth = array_slice($allExpiringDomains, 0, 5);
        
        $recentLogs = $this->logModel->getRecent(10);
        $groups = $this->groupModel->all();
        
        // Check system status
        $systemStatus = $this->checkSystemStatus();

        $this->view('dashboard/index', [
            'stats' => $stats,
            'recentDomains' => $recentDomains,
            'expiringThisMonth' => $expiringThisMonth,
            'expiringCount' => count($allExpiringDomains),
            'recentLogs' => $recentLogs,
            'groups' => $groups,
            'systemStatus' => $systemStatus,
            'title' => 'Dashboard'
        ]);
    }

    /**
     * Check system status
     */
    private function checkSystemStatus(): array
    {
        $status = [
            'database' => ['status' => 'offline', 'color' => 'red'],
            'whois' => ['status' => 'offline', 'color' => 'red'],
            'notifications' => ['status' => 'disabled', 'color' => 'gray']
        ];

        // Check database connection
        try {
            $pdo = \Core\Database::getConnection();
            $pdo->query("SELECT 1");
            $status['database'] = ['status' => 'online', 'color' => 'green'];
        } catch (\Exception $e) {
            $status['database'] = ['status' => 'offline', 'color' => 'red'];
        }

        // Check WHOIS service (test with a known TLD)
        try {
            $whoisService = new \App\Services\WhoisService();
            // Quick test - just check if we can discover TLD servers
            $tldModel = new \App\Models\TldRegistry();
            $testTld = $tldModel->find(1); // Get first TLD
            if ($testTld) {
                $status['whois'] = ['status' => 'active', 'color' => 'green'];
            } else {
                $status['whois'] = ['status' => 'no data', 'color' => 'yellow'];
            }
        } catch (\Exception $e) {
            $status['whois'] = ['status' => 'error', 'color' => 'red'];
        }

        // Check if any notification groups have active channels
        try {
            $channelModel = new \App\Models\NotificationChannel();
            $activeChannels = $channelModel->where('is_active', 1);
            if (count($activeChannels) > 0) {
                $status['notifications'] = ['status' => 'enabled', 'color' => 'green'];
            } else {
                $status['notifications'] = ['status' => 'no channels', 'color' => 'yellow'];
            }
        } catch (\Exception $e) {
            $status['notifications'] = ['status' => 'error', 'color' => 'red'];
        }

        return $status;
    }
}

