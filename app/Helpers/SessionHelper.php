<?php

namespace App\Helpers;

class SessionHelper
{
    /**
     * Format sessions for display
     * Adds: deviceIcon, browserInfo, timeAgo, sessionAge
     */
    public static function formatForDisplay(array $sessions): array
    {
        return array_map(function($session) {
            // Determine device icon
            $userAgent = strtolower($session['user_agent'] ?? '');
            if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
                $session['deviceIcon'] = 'fa-mobile-alt';
            } elseif (strpos($userAgent, 'tablet') !== false || strpos($userAgent, 'ipad') !== false) {
                $session['deviceIcon'] = 'fa-tablet-alt';
            } else {
                $session['deviceIcon'] = 'fa-desktop';
            }
            
            // Parse browser info
            if (strpos($userAgent, 'chrome') !== false) {
                $session['browserInfo'] = 'Chrome';
            } elseif (strpos($userAgent, 'safari') !== false) {
                $session['browserInfo'] = 'Safari';
            } elseif (strpos($userAgent, 'firefox') !== false) {
                $session['browserInfo'] = 'Firefox';
            } elseif (strpos($userAgent, 'edge') !== false) {
                $session['browserInfo'] = 'Edge';
            } elseif (strpos($userAgent, 'opera') !== false) {
                $session['browserInfo'] = 'Opera';
            } else {
                $session['browserInfo'] = 'Unknown Browser';
            }
            
            // Time ago
            $lastActivity = strtotime($session['last_activity']);
            $diff = time() - $lastActivity;
            if ($diff < 60) {
                $session['timeAgo'] = 'Just now';
            } elseif ($diff < 3600) {
                $session['timeAgo'] = floor($diff / 60) . ' min ago';
            } elseif ($diff < 86400) {
                $session['timeAgo'] = floor($diff / 3600) . 'h ago';
            } else {
                $session['timeAgo'] = date('M j, Y', $lastActivity);
            }
            
            // Session age
            $createdTime = strtotime($session['created_at']);
            $sessionAge = time() - $createdTime;
            if ($sessionAge < 3600) {
                $session['sessionAge'] = floor($sessionAge / 60) . ' min old';
            } elseif ($sessionAge < 86400) {
                $session['sessionAge'] = floor($sessionAge / 3600) . 'h old';
            } else {
                $session['sessionAge'] = floor($sessionAge / 86400) . 'd old';
            }
            
            return $session;
        }, $sessions);
    }
}

