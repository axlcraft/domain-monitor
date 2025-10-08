<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;

class SlackChannel implements NotificationChannelInterface
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
            $payload = [
                'text' => $message,
                'blocks' => $this->createBlocks($message, $data)
            ];

            $response = $this->client->post($config['webhook_url'], [
                'json' => $payload
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            error_log("Slack send failed: " . $e->getMessage());
            return false;
        }
    }

    private function createBlocks(string $message, array $data): array
    {
        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '🔔 Domain Expiration Alert'
                ]
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message
                ]
            ]
        ];

        if (isset($data['domain'])) {
            $blocks[] = [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Domain:*\n{$data['domain']}"
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Days Left:*\n{$data['days_left']}"
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Expiration:*\n{$data['expiration_date']}"
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Registrar:*\n{$data['registrar']}"
                    ]
                ]
            ];
        }

        return $blocks;
    }
}

