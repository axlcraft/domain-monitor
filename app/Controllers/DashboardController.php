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
        $expiringThisMonth = $this->domainModel->getExpiringDomains(30); // Domains expiring within 30 days
        $recentLogs = $this->logModel->getRecent(10);

        $this->view('dashboard/index', [
            'stats' => $stats,
            'recentDomains' => $recentDomains,
            'expiringThisMonth' => $expiringThisMonth,
            'recentLogs' => $recentLogs,
            'title' => 'Dashboard'
        ]);
    }
}

