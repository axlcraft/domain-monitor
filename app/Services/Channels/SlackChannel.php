<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;
use App\Services\Logger;

class SlackChannel implements NotificationChannelInterface
{
    private Client $client;
    private Logger $logger;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]);
        $this->logger = new Logger('slack_channel');
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

            $ok = $response->getStatusCode() === 200;
            if ($ok) {
                $this->logger->info('Slack message sent', [
                    'status' => $response->getStatusCode()
                ]);
            } else {
                $this->logger->error('Slack non-200 status', [
                    'status' => $response->getStatusCode()
                ]);
            }
            return $ok;
        } catch (\Exception $e) {
            $this->logger->error('Slack send failed', [
                'exception' => $e->getMessage()
            ]);
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

