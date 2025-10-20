<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\Setting;

class LayoutHelper
{
    /**
     * Get notifications for the top nav dropdown
     */
    public static function getNotifications(int $userId): array
    {
        try {
            $notificationModel = new Notification();
            $notifications = $notificationModel->getRecentUnread($userId, 4);
            $unreadCount = $notificationModel->getUnreadCount($userId);
            
            // Format each notification
            foreach ($notifications as &$notif) {
                $notif['time_ago'] = self::timeAgo($notif['created_at']);
                $notif['icon'] = self::getNotificationIcon($notif['type']);
                $notif['color'] = self::getNotificationColor($notif['type']);
            }
            
            return [
                'items' => $notifications,
                'unread_count' => $unreadCount
            ];
        } catch (\Exception $e) {
            // If table doesn't exist yet
            return ['items' => [], 'unread_count' => 0];
        }
    }
    
    /**
     * Get stats for sidebar (respects user isolation)
     */
    public static function getGlobalStats(?int $userId = null): array
    {
        try {
            $pdo = \Core\Database::getConnection();
            
            // Check isolation mode
            $settingModel = new Setting();
            $isolationMode = $settingModel->getValue('user_isolation_mode', 'shared');
            
            // Build WHERE clause based on isolation mode
            $whereClause = '';
            $params = [];
            
            if ($isolationMode === 'isolated' && $userId) {
                $whereClause = ' WHERE user_id = ?';
                $params[] = $userId;
            }
            
            // Get total domains
            $totalSql = "SELECT COUNT(*) as count FROM domains" . $whereClause;
            $totalStmt = $pdo->prepare($totalSql);
            $totalStmt->execute($params);
            $total = $totalStmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Get active domains
            $activeSql = "SELECT COUNT(*) as count FROM domains WHERE is_active = 1" . $whereClause;
            $activeStmt = $pdo->prepare($activeSql);
            $activeStmt->execute($params);
            $active = $activeStmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Get expiring soon
            $notificationDays = $settingModel->getNotificationDays();
            $threshold = !empty($notificationDays) ? max($notificationDays) : 30;
            
            $expiringSql = "SELECT COUNT(*) as count FROM domains 
                           WHERE is_active = 1 
                           AND expiration_date IS NOT NULL 
                           AND expiration_date <= DATE_ADD(NOW(), INTERVAL ? DAY) 
                           AND expiration_date >= NOW()" . $whereClause;
            
            $expiringParams = [$threshold];
            if ($isolationMode === 'isolated' && $userId) {
                $expiringParams[] = $userId;
            }
            
            $expiringSoonStmt = $pdo->prepare($expiringSql);
            $expiringSoonStmt->execute($expiringParams);
            $expiringSoon = $expiringSoonStmt->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0;
            
            return [
                'total' => $total,
                'active' => $active,
                'expiring_soon' => $expiringSoon,
                'expiring_threshold' => $threshold
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'expiring_soon' => 0,
                'expiring_threshold' => 30
            ];
        }
    }
    
    /**
     * Convert timestamp to "time ago" format
     */
    private static function timeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) return 'just now';
        
        if ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        }
        
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    
    /**
     * Get notification icon based on type
     */
    private static function getNotificationIcon(string $type): string
    {
        return match($type) {
            'domain_expiring' => 'exclamation-triangle',
            'domain_expired' => 'times-circle',
            'domain_updated' => 'sync-alt',
            'session_new' => 'sign-in-alt',
            'whois_failed' => 'exclamation-circle',
            'system_welcome' => 'hand-sparkles',
            'system_upgrade' => 'arrow-up',
            default => 'bell'
        };
    }
    
    /**
     * Get notification color based on type
     */
    private static function getNotificationColor(string $type): string
    {
        return match($type) {
            'domain_expiring' => 'orange',
            'domain_expired' => 'red',
            'domain_updated' => 'green',
            'session_new' => 'blue',
            'whois_failed' => 'gray',
            'system_welcome' => 'purple',
            'system_upgrade' => 'indigo',
            default => 'gray'
        };
    }
    
    /**
     * Get application settings
     */
    public static function getAppSettings(): array
    {
        try {
            $settingModel = new Setting();
            $appSettings = $settingModel->getAppSettings();
            
            return [
                'app_name' => htmlspecialchars($appSettings['app_name']),
                'app_timezone' => $appSettings['app_timezone'],
                'app_version' => $appSettings['app_version']
            ];
        } catch (\Exception $e) {
            // Fallback defaults
            $settingModel = new Setting();
            return [
                'app_name' => 'Domain Monitor',
                'app_timezone' => 'UTC',
                'app_version' => $settingModel->getAppVersion()
            ];
        }
    }
}

