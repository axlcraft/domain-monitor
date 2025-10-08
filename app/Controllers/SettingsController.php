<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Setting;

class SettingsController extends Controller
{
    private Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    public function index()
    {
        $settings = $this->settingModel->getAllAsKeyValue();
        $appSettings = $this->settingModel->getAppSettings();
        $emailSettings = $this->settingModel->getEmailSettings();
        
        // Predefined notification day options
        $notificationPresets = [
            'minimal' => [
                'label' => 'Minimal (30, 7, 1 days)',
                'value' => '30,7,1'
            ],
            'standard' => [
                'label' => 'Standard (60, 30, 21, 14, 7, 5, 3, 2, 1 days)',
                'value' => '60,30,21,14,7,5,3,2,1'
            ],
            'frequent' => [
                'label' => 'Frequent (90, 60, 45, 30, 21, 14, 10, 7, 5, 3, 2, 1 days)',
                'value' => '90,60,45,30,21,14,10,7,5,3,2,1'
            ],
            'business' => [
                'label' => 'Business Focused (60, 30, 14, 7, 3, 1 days)',
                'value' => '60,30,14,7,3,1'
            ],
            'conservative' => [
                'label' => 'Conservative (30, 15, 7, 3, 1 days)',
                'value' => '30,15,7,3,1'
            ],
            'custom' => [
                'label' => 'Custom',
                'value' => 'custom'
            ]
        ];

        // Check interval presets
        $checkIntervalPresets = [
            ['label' => 'Every 6 hours', 'value' => 6],
            ['label' => 'Every 12 hours', 'value' => 12],
            ['label' => 'Daily (24 hours)', 'value' => 24],
            ['label' => 'Every 2 days (48 hours)', 'value' => 48],
            ['label' => 'Weekly (168 hours)', 'value' => 168]
        ];

        $this->view('settings/index', [
            'settings' => $settings,
            'appSettings' => $appSettings,
            'emailSettings' => $emailSettings,
            'notificationPresets' => $notificationPresets,
            'checkIntervalPresets' => $checkIntervalPresets,
            'title' => 'Settings'
        ]);
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }

        try {
            // Update notification days
            $notificationPreset = $_POST['notification_preset'] ?? 'standard';
            
            if ($notificationPreset === 'custom') {
                // Custom days entered by user
                $customDays = trim($_POST['custom_notification_days'] ?? '');
                
                if (empty($customDays)) {
                    $_SESSION['error'] = 'Please enter notification days for custom preset';
                    $this->redirect('/settings#monitoring');
                    return;
                }
                
                // Validate custom days (comma-separated integers)
                $daysArray = array_map('trim', explode(',', $customDays));
                $daysArray = array_filter($daysArray, function($day) {
                    return is_numeric($day) && $day > 0;
                });
                
                if (empty($daysArray)) {
                    $_SESSION['error'] = 'Invalid notification days format. Use comma-separated numbers (e.g., 30,15,7,1)';
                    $this->redirect('/settings#monitoring');
                    return;
                }
                
                // Sort in descending order
                rsort($daysArray, SORT_NUMERIC);
                $notificationDays = implode(',', $daysArray);
            } else {
                // Use preset value
                $notificationDays = $_POST['notification_days_before'] ?? '30,15,7,3,1';
            }

            // Update check interval
            $checkInterval = (int)($_POST['check_interval_hours'] ?? 24);
            
            if ($checkInterval < 1 || $checkInterval > 720) { // Max 30 days
                $_SESSION['error'] = 'Check interval must be between 1 and 720 hours';
                $this->redirect('/settings#monitoring');
                return;
            }

            // Save settings
            $this->settingModel->setValue('notification_days_before', $notificationDays);
            $this->settingModel->setValue('check_interval_hours', $checkInterval);

            $_SESSION['success'] = 'Settings updated successfully';
            $this->redirect('/settings#monitoring');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update settings: ' . $e->getMessage();
            $this->redirect('/settings#monitoring');
        }
    }

    public function testCron()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }

        // Update last check run time to show the test worked
        $this->settingModel->updateLastCheckRun();
        
        $_SESSION['info'] = 'Test notification sent (feature coming soon). Last check time updated.';
        $this->redirect('/settings');
    }

    public function clearLogs()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }

        try {
            // Clear notification logs older than 30 days
            $stmt = $this->settingModel->db->prepare(
                "DELETE FROM notification_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $stmt->execute();
            $deleted = $stmt->rowCount();

            $_SESSION['success'] = "Cleared $deleted old notification log(s)";
            $this->redirect('/settings#maintenance');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to clear logs: ' . $e->getMessage();
            $this->redirect('/settings#maintenance');
        }
    }

    public function updateApp()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }

        try {
            $appSettings = [
                'app_name' => trim($_POST['app_name'] ?? 'Domain Monitor'),
                'app_url' => trim($_POST['app_url'] ?? 'http://localhost:8000'),
                'app_timezone' => trim($_POST['app_timezone'] ?? 'UTC')
            ];

            // Validate app_name
            if (empty($appSettings['app_name'])) {
                $_SESSION['error'] = 'Application name is required';
                $this->redirect('/settings#app');
                return;
            }

            // Validate app_url
            if (empty($appSettings['app_url']) || !filter_var($appSettings['app_url'], FILTER_VALIDATE_URL)) {
                $_SESSION['error'] = 'Please enter a valid application URL';
                $this->redirect('/settings#app');
                return;
            }

            // Validate timezone
            $validTimezones = timezone_identifiers_list();
            if (!in_array($appSettings['app_timezone'], $validTimezones)) {
                $_SESSION['error'] = 'Invalid timezone selected';
                $this->redirect('/settings#app');
                return;
            }

            $this->settingModel->updateAppSettings($appSettings);
            $_SESSION['success'] = 'Application settings updated successfully';
            $this->redirect('/settings#app');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update application settings: ' . $e->getMessage();
            $this->redirect('/settings#app');
        }
    }

    public function updateEmail()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }

        try {
            $emailSettings = [
                'mail_host' => trim($_POST['mail_host'] ?? ''),
                'mail_port' => trim($_POST['mail_port'] ?? '2525'),
                'mail_username' => trim($_POST['mail_username'] ?? ''),
                'mail_password' => trim($_POST['mail_password'] ?? ''),
                'mail_encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
                'mail_from_address' => trim($_POST['mail_from_address'] ?? ''),
                'mail_from_name' => trim($_POST['mail_from_name'] ?? 'Domain Monitor')
            ];

            // Validate required fields
            if (empty($emailSettings['mail_host'])) {
                $_SESSION['error'] = 'Mail host is required';
                $this->redirect('/settings#email');
                return;
            }

            if (empty($emailSettings['mail_from_address']) || !filter_var($emailSettings['mail_from_address'], FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = 'Please enter a valid from email address';
                $this->redirect('/settings#email');
                return;
            }

            // Validate port
            if (!is_numeric($emailSettings['mail_port']) || $emailSettings['mail_port'] < 1 || $emailSettings['mail_port'] > 65535) {
                $_SESSION['error'] = 'Please enter a valid port number (1-65535)';
                $this->redirect('/settings#email');
                return;
            }

            $this->settingModel->updateEmailSettings($emailSettings);
            $_SESSION['success'] = 'Email settings updated successfully';
            $this->redirect('/settings#email');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to update email settings: ' . $e->getMessage();
            $this->redirect('/settings#email');
        }
    }

    public function testEmail()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/settings');
            return;
        }

        $testEmail = trim($_POST['test_email'] ?? '');

        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address';
            $this->redirect('/settings#email');
            return;
        }

        try {
            // Get current email settings
            $emailSettings = $this->settingModel->getEmailSettings();
            $appSettings = $this->settingModel->getAppSettings();

            // Create PHPMailer instance
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

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
            $mail->addAddress($testEmail);

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
            
            $_SESSION['success'] = "Test email sent successfully to {$testEmail}. Please check your inbox.";
            $this->redirect('/settings#email');

        } catch (\Exception $e) {
            $_SESSION['error'] = "Failed to send test email: " . $e->getMessage();
            $this->redirect('/settings#email');
        }
    }
}

