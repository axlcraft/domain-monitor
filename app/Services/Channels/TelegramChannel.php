<?php

namespace App\Services\Channels;

use GuzzleHttp\Client;

class TelegramChannel implements NotificationChannelInterface
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.telegram.org',
            'timeout' => 10,
        ]);
    }

    public function send(array $config, string $message, array $data = []): bool
    {
        if (!isset($config['bot_token']) || !isset($config['chat_id'])) {
            return false;
        }

        try {
            $response = $this->client->post("/bot{$config['bot_token']}/sendMessage", [
                'json' => [
                    'chat_id' => $config['chat_id'],
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            error_log("Telegram send failed: " . $e->getMessage());
            return false;
        }
    }
}

