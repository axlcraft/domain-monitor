<?php

namespace App\Services\Channels;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Setting;

class EmailChannel implements NotificationChannelInterface
{
    public function send(array $config, string $message, array $data = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Get email settings from database
            $settingModel = new Setting();
            $emailSettings = $settingModel->getEmailSettings();
            $appSettings = $settingModel->getAppSettings();

            // Server settings
            $mail->isSMTP();
            $mail->Host = $emailSettings['mail_host'];
            $mail->SMTPAuth = !empty($emailSettings['mail_username']);
            $mail->Username = $emailSettings['mail_username'];
            $mail->Password = $emailSettings['mail_password'];
            $mail->SMTPSecure = $emailSettings['mail_encryption'];
            $mail->Port = $emailSettings['mail_port'];

            // Recipients
            $mail->setFrom($emailSettings['mail_from_address'], $emailSettings['mail_from_name']);
            $mail->addAddress($config['email']);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $this->getSubject($data);
            $mail->Body = $this->formatHtmlBody($message, $data, $appSettings);
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

    private function formatHtmlBody(string $message, array $data, array $appSettings): string
    {
        $messageHtml = nl2br(htmlspecialchars($message));
        $appName = htmlspecialchars($appSettings['app_name']);
        $appUrl = htmlspecialchars($appSettings['app_url']);
        
        // Build domain link if domain ID is available
        $domainLink = '';
        if (isset($data['domain_id'])) {
            $domainUrl = rtrim($appUrl, '/') . '/domains/' . $data['domain_id'];
            $domainLink = "<p style='margin-top: 15px;'><a href='$domainUrl' class='button'>View Domain Details</a></p>";
        }

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
                    <h2>🔔 {$appName} Alert</h2>
                </div>
                <div class='content'>
                    <p>$messageHtml</p>
                    $domainLink
                </div>
                <div class='footer'>
                    <p>This is an automated message from {$appName}</p>
                    <p style='margin-top: 5px;'><a href='$appUrl' style='color: #4A90E2;'>Visit Dashboard</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

