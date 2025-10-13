<?php

namespace App\Services\Channels;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Setting;
use App\Helpers\EmailHelper;
use App\Services\Logger;

class EmailChannel implements NotificationChannelInterface
{
    private Logger $logger;
    
    public function __construct()
    {
        $this->logger = new Logger('email_channel');
    }
    
    public function send(array $config, string $message, array $data = []): bool
    {
        try {
            $result = EmailHelper::sendNotificationEmail(
                $config['email'],
                EmailHelper::getEmailSubject($data),
                $message,
                $data
            );
            
            if (!$result['success']) {
                $this->logger->error("Email send failed via EmailChannel", [
                    'email' => $config['email'],
                    'subject' => EmailHelper::getEmailSubject($data),
                    'debug_info' => $result['debug_info'] ?? null,
                    'error' => $result['error'] ?? null
                ]);
                return false;
            }
            
            $this->logger->info("Email sent successfully via EmailChannel", [
                'email' => $config['email'],
                'subject' => EmailHelper::getEmailSubject($data)
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Email send exception in EmailChannel", [
                'email' => $config['email'],
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

}

