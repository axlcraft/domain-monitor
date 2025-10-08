<?php

namespace App\Services;

use App\Services\Channels\EmailChannel;
use App\Services\Channels\TelegramChannel;
use App\Services\Channels\DiscordChannel;
use App\Services\Channels\SlackChannel;

class NotificationService
{
    private array $channels = [];

    public function __construct()
    {
        $this->channels = [
            'email' => new EmailChannel(),
            'telegram' => new TelegramChannel(),
            'discord' => new DiscordChannel(),
            'slack' => new SlackChannel(),
        ];
    }

    /**
     * Send notification to specified channel
     */
    public function send(string $channelType, array $config, string $message, array $data = []): bool
    {
        if (!isset($this->channels[$channelType])) {
            return false;
        }

        try {
            return $this->channels[$channelType]->send($config, $message, $data);
        } catch (\Exception $e) {
            error_log("Notification send failed [$channelType]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification to all active channels in a group
     */
    public function sendToGroup(int $groupId, string $subject, string $message, array $data = []): array
    {
        // Get active channels for the group
        $channelModel = new \App\Models\NotificationChannel();
        $channels = $channelModel->getByGroupId($groupId);
        
        $results = [];
        
        foreach ($channels as $channel) {
            if (!$channel['is_active']) {
                continue; // Skip inactive channels
            }
            
            $config = json_decode($channel['channel_config'], true);
            
            // Add subject to data for channels that support it (like email)
            $channelData = array_merge(['subject' => $subject], $data);
            
            $success = $this->send(
                $channel['channel_type'],
                $config,
                $message,
                $channelData
            );

            $results[] = [
                'channel' => $channel['channel_type'],
                'success' => $success
            ];
        }

        return $results;
    }

    /**
     * Send domain expiration notification
     */
    public function sendDomainExpirationAlert(array $domain, array $notificationChannels): array
    {
        $daysLeft = $this->calculateDaysLeft($domain['expiration_date']);
        $message = $this->formatExpirationMessage($domain, $daysLeft);

        $results = [];

        foreach ($notificationChannels as $channel) {
            $config = json_decode($channel['channel_config'], true);
            $success = $this->send(
                $channel['channel_type'],
                $config,
                $message,
                [
                    'domain' => $domain['domain_name'],
                    'days_left' => $daysLeft,
                    'expiration_date' => $domain['expiration_date'],
                    'registrar' => $domain['registrar']
                ]
            );

            $results[] = [
                'channel' => $channel['channel_type'],
                'success' => $success
            ];
        }

        return $results;
    }

    /**
     * Format expiration message
     */
    private function formatExpirationMessage(array $domain, int $daysLeft): string
    {
        $domainName = $domain['domain_name'];
        $expirationDate = date('F j, Y', strtotime($domain['expiration_date']));
        $registrar = $domain['registrar'] ?? 'Unknown';

        if ($daysLeft <= 0) {
            return "🚨 URGENT: Domain '$domainName' has EXPIRED on $expirationDate!\n\n" .
                   "Registrar: $registrar\n" .
                   "Please renew immediately to avoid losing your domain.";
        }

        if ($daysLeft == 1) {
            return "⚠️ CRITICAL: Domain '$domainName' expires TOMORROW ($expirationDate)!\n\n" .
                   "Registrar: $registrar\n" .
                   "Please renew as soon as possible.";
        }

        if ($daysLeft <= 7) {
            return "⚠️ WARNING: Domain '$domainName' expires in $daysLeft days ($expirationDate)!\n\n" .
                   "Registrar: $registrar\n" .
                   "Please renew soon.";
        }

        return "ℹ️ REMINDER: Domain '$domainName' expires in $daysLeft days ($expirationDate).\n\n" .
               "Registrar: $registrar\n" .
               "Please plan for renewal.";
    }

    /**
     * Calculate days left until expiration
     */
    private function calculateDaysLeft(string $expirationDate): int
    {
        $expiration = strtotime($expirationDate);
        $now = time();
        return (int)floor(($expiration - $now) / 86400);
    }
}

