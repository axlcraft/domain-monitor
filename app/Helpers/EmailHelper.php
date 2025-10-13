<?php

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Models\Setting;
use App\Services\Logger;

class EmailHelper
{
    private static ?Setting $settingModel = null;
    private static ?Logger $logger = null;
    
    /**
     * Get the Setting model instance
     */
    private static function getSettingModel(): Setting
    {
        if (self::$settingModel === null) {
            self::$settingModel = new Setting();
        }
        return self::$settingModel;
    }
    
    /**
     * Get the Logger instance
     */
    private static function getLogger(): Logger
    {
        if (self::$logger === null) {
            self::$logger = new Logger('email');
        }
        return self::$logger;
    }
    
    /**
     * Get email settings from database
     */
    public static function getEmailSettings(): array
    {
        return self::getSettingModel()->getEmailSettings();
    }
    
    /**
     * Get app settings from database
     */
    public static function getAppSettings(): array
    {
        return self::getSettingModel()->getAppSettings();
    }
    
    /**
     * Create and configure a PHPMailer instance with proper settings
     */
    public static function createMailer(): PHPMailer
    {
        $emailSettings = self::getEmailSettings();
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $emailSettings['mail_host'];
        $mail->SMTPAuth = !empty($emailSettings['mail_username']);
        $mail->Username = $emailSettings['mail_username'];
        $mail->Password = $emailSettings['mail_password'];
        $mail->Port = (int)$emailSettings['mail_port'];
        
        // Configure encryption based on port
        $port = (int)$emailSettings['mail_port'];
        $encryption = $emailSettings['mail_encryption'];
        
        // Auto-detect encryption for common ports
        if ($port === 465) {
            // Port 465 typically uses SSL
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587) {
            // Port 587 typically uses TLS
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($port === 25 || $port === 2525) {
            // Port 25/2525 might use TLS or no encryption
            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
            }
        } else {
            // Use configured encryption for other ports
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
            }
        }
        
        // Configure timeouts and SSL options
        $mail->Timeout = 30; // 30 seconds total timeout
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Set character encoding
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    }
    
    /**
     * Send a test email
     */
    public static function sendTestEmail(string $toEmail): array
    {
        try {
            $emailSettings = self::getEmailSettings();
            $appSettings = self::getAppSettings();
            $mail = self::createMailer();
            
            // Set sender
            $mail->setFrom($emailSettings['mail_from_address'], $emailSettings['mail_from_name']);
            $mail->addAddress($toEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Test Email from ' . $appSettings['app_name'];
            
            $appName = htmlspecialchars($appSettings['app_name']);
            $appUrl = htmlspecialchars($appSettings['app_url']);
            $currentTime = date('F j, Y g:i A');
            
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4A90E2; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
                    .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
                    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 15px 0; }
                    .info-table { width: 100%; margin-top: 15px; }
                    .info-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                    .info-table td:first-child { font-weight: bold; width: 150px; }
                    .footer { background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>✅ Email Test Successful!</h2>
                    </div>
                    <div class='content'>
                        <div class='success'>
                            <strong>Success!</strong> Your email configuration is working correctly.
                        </div>
                        <p>This is a test email from <strong>{$appName}</strong>.</p>
                        <p>If you're seeing this message, it means your SMTP settings are configured properly and emails are being delivered successfully.</p>
                        
                        <table class='info-table'>
                            <tr>
                                <td>SMTP Host:</td>
                                <td>" . htmlspecialchars($emailSettings['mail_host']) . "</td>
                            </tr>
                            <tr>
                                <td>SMTP Port:</td>
                                <td>" . htmlspecialchars($emailSettings['mail_port']) . "</td>
                            </tr>
                            <tr>
                                <td>Encryption:</td>
                                <td>" . htmlspecialchars($emailSettings['mail_encryption'] ?: 'None') . "</td>
                            </tr>
                            <tr>
                                <td>From Address:</td>
                                <td>" . htmlspecialchars($emailSettings['mail_from_address']) . "</td>
                            </tr>
                            <tr>
                                <td>Test Time:</td>
                                <td>{$currentTime}</td>
                            </tr>
                        </table>
                    </div>
                    <div class='footer'>
                        <p>This is an automated test message from {$appName}</p>
                        <p style='margin-top: 5px;'><a href='{$appUrl}' style='color: #4A90E2;'>Visit Dashboard</a></p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Email Test Successful!\n\n" .
                           "This is a test email from {$appName}.\n" .
                           "Your SMTP configuration is working correctly.\n\n" .
                           "SMTP Host: {$emailSettings['mail_host']}\n" .
                           "SMTP Port: {$emailSettings['mail_port']}\n" .
                           "From: {$emailSettings['mail_from_address']}\n" .
                           "Test Time: {$currentTime}";
            
            $mail->send();
            
            return [
                'success' => true,
                'message' => "Test email sent successfully to {$toEmail}. Please check your inbox."
            ];
            
        } catch (Exception $e) {
            $errorMessage = "Failed to send test email: " . $e->getMessage();
            $debugInfo = $mail->ErrorInfo ?? 'No debug info available';
            
            // Log the error using the application's logger
            self::getLogger()->error($errorMessage, [
                'email' => $toEmail,
                'smtp_error' => $debugInfo,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage(),
                'debug_info' => $debugInfo
            ];
        }
    }
    
    /**
     * Send a notification email
     */
    public static function sendNotificationEmail(string $toEmail, string $subject, string $message, array $data = []): array
    {
        try {
            $emailSettings = self::getEmailSettings();
            $appSettings = self::getAppSettings();
            $mail = self::createMailer();
            
            // Set sender
            $mail->setFrom($emailSettings['mail_from_address'], $emailSettings['mail_from_name']);
            $mail->addAddress($toEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::formatHtmlBody($message, $data, $appSettings);
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            
            return [
                'success' => true,
                'message' => "Email sent successfully to {$toEmail}"
            ];
            
        } catch (Exception $e) {
            $errorMessage = "Failed to send email: " . $e->getMessage();
            $debugInfo = $mail->ErrorInfo ?? 'No debug info available';
            
            // Log the error using the application's logger
            self::getLogger()->error($errorMessage, [
                'email' => $toEmail,
                'subject' => $subject,
                'smtp_error' => $debugInfo,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage(),
                'debug_info' => $debugInfo
            ];
        }
    }
    
    /**
     * Format HTML email body
     */
    private static function formatHtmlBody(string $message, array $data, array $appSettings): string
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
    
    /**
     * Send verification email
     */
    public static function sendVerificationEmail(string $email, string $fullName, string $token): array
    {
        try {
            $appSettings = self::getAppSettings();
            $verifyUrl = $appSettings['app_url'] . '/verify-email?token=' . $token;
            
            $subject = 'Verify Your Email Address';
            $message = "
                <h2>Welcome to Domain Monitor!</h2>
                <p>Hello {$fullName},</p>
                <p>Thank you for registering. Please click the link below to verify your email address:</p>
                <p><a href='{$verifyUrl}' style='background: #4A90E2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verify Email Address</a></p>
                <p>Or copy and paste this URL into your browser:</p>
                <p>{$verifyUrl}</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not create an account, please ignore this email.</p>
            ";
            
            return self::sendNotificationEmail($email, $subject, $message);
            
        } catch (\Exception $e) {
            $errorMessage = "Failed to send verification email: " . $e->getMessage();
            
            // Log the error using the application's logger
            self::getLogger()->error($errorMessage, [
                'email' => $email,
                'full_name' => $fullName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail(string $email, string $fullName, string $token): array
    {
        try {
            $appSettings = self::getAppSettings();
            $resetUrl = $appSettings['app_url'] . '/reset-password?token=' . $token;
            
            $subject = 'Reset Your Password';
            $message = "
                <h2>Password Reset Request</h2>
                <p>Hello {$fullName},</p>
                <p>We received a request to reset your password. Click the link below to create a new password:</p>
                <p><a href='{$resetUrl}' style='background: #4A90E2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
                <p>Or copy and paste this URL into your browser:</p>
                <p>{$resetUrl}</p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email and your password will remain unchanged.</p>
            ";
            
            return self::sendNotificationEmail($email, $subject, $message);
            
        } catch (\Exception $e) {
            $errorMessage = "Failed to send password reset email: " . $e->getMessage();
            
            // Log the error using the application's logger
            self::getLogger()->error($errorMessage, [
                'email' => $email,
                'full_name' => $fullName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $errorMessage,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get email subject based on data
     */
    public static function getEmailSubject(array $data): string
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
}
