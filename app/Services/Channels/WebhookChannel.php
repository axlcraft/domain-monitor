<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;
use App\Services\Logger;

class WebhookChannel implements NotificationChannelInterface
{
    private Client $httpClient;
    private Logger $logger;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
        $this->logger = new Logger('webhook_channel');
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
            $ok = $status >= 200 && $status < 300;
            if ($ok) {
                $this->logger->info('Webhook sent successfully', [
                    'url' => $url,
                    'status' => $status
                ]);
            } else {
                $this->logger->error('Webhook responded with non-2xx', [
                    'url' => $url,
                    'status' => $status
                ]);
            }
            return $ok;
        } catch (\Exception $e) {
            $this->logger->error('Webhook send failed', [
                'url' => $url,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }
}


