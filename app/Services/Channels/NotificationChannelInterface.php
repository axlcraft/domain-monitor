<?php

namespace App\Services\Channels;

interface NotificationChannelInterface
{
    /**
     * Send notification through the channel
     *
     * @param array $config Channel-specific configuration
     * @param string $message Message to send
     * @param array $data Additional data for formatting
     * @return bool Success status
     */
    public function send(array $config, string $message, array $data = []): bool;
}

