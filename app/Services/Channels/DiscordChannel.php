<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;

class DiscordChannel implements NotificationChannelInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]);
    }

    public function send(array $config, string $message, array $data = []): bool
    {
        if (!isset($config['webhook_url'])) {
            return false;
        }

        try {
            $embed = $this->createEmbed($message, $data);

            $response = $this->client->post($config['webhook_url'], [
                'json' => [
                    'embeds' => [$embed]
                ]
            ]);

            return $response->getStatusCode() === 204;
        } catch (\Exception $e) {
            error_log("Discord send failed: " . $e->getMessage());
            return false;
        }
    }

    private function createEmbed(string $message, array $data): array
    {
        $color = $this->getColorByDaysLeft($data['days_left'] ?? null);

        $embed = [
            'title' => '🔔 Domain Expiration Alert',
            'description' => $message,
            'color' => $color,
            'timestamp' => date('c'),
            'footer' => [
                'text' => 'Domain Monitor'
            ]
        ];

        if (isset($data['domain'])) {
            $embed['fields'] = [
                [
                    'name' => 'Domain',
                    'value' => $data['domain'],
                    'inline' => true
                ],
                [
                    'name' => 'Days Left',
                    'value' => $data['days_left'],
                    'inline' => true
                ],
                [
                    'name' => 'Expiration Date',
                    'value' => $data['expiration_date'],
                    'inline' => true
                ]
            ];
        }

        return $embed;
    }

    private function getColorByDaysLeft(?int $daysLeft): int
    {
        if ($daysLeft === null) {
            return 0x808080; // Gray
        }

        if ($daysLeft <= 0) {
            return 0xFF0000; // Red
        }

        if ($daysLeft <= 3) {
            return 0xFF4500; // Orange Red
        }

        if ($daysLeft <= 7) {
            return 0xFFA500; // Orange
        }

        if ($daysLeft <= 30) {
            return 0xFFFF00; // Yellow
        }

        return 0x00FF00; // Green
    }
}

