<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;

class WebhookChannel implements NotificationChannelInterface
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
    }

    public function send(array $config, string $message, array $data = []): bool
    {
        $url = trim($config['webhook_url'] ?? '');
        if (empty($url)) {
            return false;
        }

        // Build a sane, generic JSON payload for automation tools (n8n, Zapier, etc.)
        $payload = [
            'event' => 'domain_expiration_alert',
            'message' => $message,
            'data' => $data,
            'sent_at' => date('c')
        ];

        try {
            $response = $this->httpClient->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            $status = $response->getStatusCode();
            return $status >= 200 && $status < 300;
        } catch (\Exception $e) {
            error_log('Webhook send failed: ' . $e->getMessage());
            return false;
        }
    }
}


