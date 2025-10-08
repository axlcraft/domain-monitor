<?php

namespace App\Services\Channels;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailChannel implements NotificationChannelInterface
{
    public function send(array $config, string $message, array $data = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = $_ENV['MAIL_PORT'];

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($config['email']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $this->getSubject($data);
            $mail->Body = $this->formatHtmlBody($message, $data);
            $mail->AltBody = strip_tags($message);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email send failed: {$mail->ErrorInfo}");
            return false;
        }
    }

    private function getSubject(array $data): string
    {
        if (isset($data['domain'])) {
            $daysLeft = $data['days_left'];
            if ($daysLeft <= 0) {
                return "🚨 URGENT: Domain {$data['domain']} has EXPIRED";
            }
            if ($daysLeft == 1) {
                return "⚠️ CRITICAL: Domain {$data['domain']} expires TOMORROW";
            }
            return "⚠️ Domain Expiration Alert: {$data['domain']} ({$daysLeft} days)";
        }

        return "Domain Monitor Alert";
    }

    private function formatHtmlBody(string $message, array $data): string
    {
        $messageHtml = nl2br(htmlspecialchars($message));

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4A90E2; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                .footer { background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
                .button { display: inline-block; padding: 10px 20px; background: #4A90E2; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🔔 Domain Monitor Alert</h2>
                </div>
                <div class='content'>
                    <p>$messageHtml</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from Domain Monitor</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

