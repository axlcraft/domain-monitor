<?php

namespace App\Controllers;

use Core\Controller;

class InstallerController extends Controller
{
    private $db = null;
    
    /**
     * Check if system is already installed
     */
    private function isInstalled(): bool
    {
        try {
            $pdo = \Core\Database::getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check pending migrations
     */
    private function getPendingMigrations(): array
    {
        // For fresh installs - use consolidated schema
        $freshInstallMigration = ['000_initial_schema_v1.1.0.sql'];
        
        // For incremental updates from v1.0.0
        $incrementalMigrations = [
            '001_create_tables.sql',
            '002_create_users_table.sql',
            '003_add_whois_fields.sql',
            '004_create_tld_registry_table.sql',
            '005_update_tld_import_logs.sql',
            '006_add_complete_workflow_import_type.sql',
            '007_add_app_and_email_settings.sql',
            '008_add_notes_to_domains.sql',
            '009_add_authentication_features.sql',
            '010_add_app_version_setting.sql',
            '011_create_sessions_table.sql',
            '012_link_remember_tokens_to_sessions.sql',
            '013_create_user_notifications_table.sql',
            '014_add_captcha_settings.sql',
            '015_create_error_logs_table.sql',
            '016_add_tags_to_domains.sql',
            '017_add_two_factor_authentication.sql',
            '018_add_user_isolation.sql',
        ];
        
        try {
            $pdo = \Core\Database::getConnection();
            
            // First, check if this is a fresh install by looking for core application tables
            // Core tables that indicate a real installation: users, domains, settings, notification_groups
            $hasUsers = false;
            $hasDomains = false;
            $hasSettings = false;
            $hasNotificationGroups = false;
            $hasMigrations = false;
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                $hasUsers = $stmt->fetchColumn() > 0;
            } catch (\Exception $e) {
                // Users table doesn't exist
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM domains");
                $hasDomains = true; // Table exists
            } catch (\Exception $e) {
                // Domains table doesn't exist
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM settings");
                $hasSettings = true; // Table exists
            } catch (\Exception $e) {
                // Settings table doesn't exist
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM notification_groups");
                $hasNotificationGroups = true; // Table exists
            } catch (\Exception $e) {
                // Notification groups table doesn't exist
            }
            
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM migrations");
                $hasMigrations = true; // Table exists
            } catch (\Exception $e) {
                // Migrations table doesn't exist
            }
            
            // If no core application tables exist - this is a fresh install
            // Core tables are: users, domains, settings, notification_groups
            // Note: sessions, password_reset_tokens, etc. might exist from app startup but don't indicate real installation
            if (!$hasUsers && !$hasDomains && !$hasSettings && !$hasNotificationGroups) {
                return $freshInstallMigration;
            }
            
            // Additional check: if we have some tables but no actual data in core tables, treat as fresh install
            // This handles cases where tables might be created by app startup but no real data exists
            if ($hasUsers && !$hasDomains && !$hasSettings && !$hasNotificationGroups) {
                // Only users table exists, check if it has any real data
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                    $adminCount = $stmt->fetchColumn();
                    if ($adminCount == 0) {
                        // No admin users, treat as fresh install
                        return $freshInstallMigration;
                    }
                } catch (\Exception $e) {
                    // Error checking users, treat as fresh install
                    return $freshInstallMigration;
                }
            }
            
            // Create migrations table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_migration (migration)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            
            // Get executed migrations
            $stmt = $pdo->query("SELECT migration FROM migrations");
            $executed = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            // If no migrations executed but has data - check if it's a complete v1.0.0 install or broken fresh install
            if (empty($executed) && ($hasUsers || $hasDomains)) {
                // If critical tables are missing, treat as broken fresh install and use consolidated schema
                if (!$hasSettings || !$hasNotificationGroups) {
                    // Clear the migrations table and use fresh install
                    $pdo->exec("DELETE FROM migrations");
                    return $freshInstallMigration;
                }
                // Mark 001-008 as executed (v1.0.0 migrations)
                $v1Migrations = [
                    '001_create_tables.sql',
                    '002_create_users_table.sql',
                    '003_add_whois_fields.sql',
                    '004_create_tld_registry_table.sql',
                    '005_update_tld_import_logs.sql',
                    '006_add_complete_workflow_import_type.sql',
                    '007_add_app_and_email_settings.sql',
                    '008_add_notes_to_domains.sql'
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO migrations (migration) VALUES (?)");
                foreach ($v1Migrations as $migration) {
                    $stmt->execute([$migration]);
                }
                
                // Return only new migrations for v1.1.0
                return [
                    '009_add_authentication_features.sql', 
                    '010_add_app_version_setting.sql', 
                    '011_create_sessions_table.sql',
                    '012_link_remember_tokens_to_sessions.sql',
                    '013_create_user_notifications_table.sql',
                    '014_add_captcha_settings.sql',
                    '015_create_error_logs_table.sql',
                    '016_add_tags_to_domains.sql',
                    '017_add_two_factor_authentication.sql',
                    '018_add_user_isolation.sql'
                ];
            }
            
            // If no migrations executed and no data - fresh install (use consolidated)
            if (empty($executed)) {
                return $freshInstallMigration;
            }
            
            // If has executed migrations - check for pending incremental ones
            $pending = array_diff($incrementalMigrations, $executed);
            
            // If we have executed migrations but critical tables are missing, something went wrong
            // Clear migrations and use fresh install
            if (!empty($executed) && (!$hasSettings || !$hasNotificationGroups)) {
                $pdo->exec("DELETE FROM migrations");
                return $freshInstallMigration;
            }
            
            return $pending;
            
        } catch (\Exception $e) {
            // If critical error - assume fresh install
            return $freshInstallMigration;
        }
    }
    
    /**
     * Show installer welcome page
     */
    public function index()
    {
        if ($this->isInstalled()) {
            $pending = $this->getPendingMigrations();
            if (empty($pending)) {
                $_SESSION['info'] = 'System is already installed and up to date';
                $this->redirect('/');
                return;
            }
            // Has pending migrations - show updater
            $this->redirect('/install/update');
            return;
        }
        
        $this->view('installer/welcome', [
            'title' => 'Install Domain Monitor'
        ]);
    }
    
    /**
     * Check database connection
     */
    public function checkDatabase()
    {
        try {
            $pdo = \Core\Database::getConnection();
            $pdo->query("SELECT 1");
            
            $this->view('installer/database-check', [
                'title' => 'Database Connection',
                'success' => true
            ]);
        } catch (\Exception $e) {
            $this->view('installer/database-check', [
                'title' => 'Database Connection',
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Run installation
     */
    public function install()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/install');
            return;
        }
        
        $adminUsername = trim($_POST['admin_username'] ?? '');
        $adminPassword = trim($_POST['admin_password'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        
        // Validate username format and length
        $usernameError = \App\Helpers\InputValidator::validateUsername($adminUsername, 3, 50);
        if ($usernameError) {
            $_SESSION['error'] = $usernameError;
            $this->redirect('/install');
            return;
        }
        
        if (empty($adminPassword) || strlen($adminPassword) < 8) {
            $_SESSION['error'] = 'Admin password must be at least 8 characters';
            $this->redirect('/install');
            return;
        }
        
        if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid admin email';
            $this->redirect('/install');
            return;
        }
        
        try {
            $pdo = \Core\Database::getConnection();
            
            // Run all migrations
            $migrations = $this->getPendingMigrations();
            $results = [];
            
            foreach ($migrations as $migration) {
                $file = __DIR__ . '/../../database/migrations/' . $migration;
                if (!file_exists($file)) continue;
                
                $sql = file_get_contents($file);
                
                // Replace placeholders for user migration or consolidated schema
                if ($migration === '002_create_users_table.sql' || $migration === '000_initial_schema_v1.1.0.sql') {
                    $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT);
                    $sql = str_replace('{{ADMIN_PASSWORD_HASH}}', $passwordHash, $sql);
                    $sql = str_replace('{{ADMIN_USERNAME}}', $adminUsername, $sql);
                    $sql = str_replace('{{ADMIN_EMAIL}}', $adminEmail, $sql);
                }
                
                // Execute SQL - use a more robust method for complex SQL files
                try {
                    // For complex migration files, execute the entire SQL at once
                    // This handles multi-line statements, comments, and complex syntax properly
                    $pdo->exec($sql);
                } catch (\PDOException $e) {
                    // If that fails, try the statement-by-statement approach as fallback
                    $statements = $this->parseSqlStatements($sql);
                    foreach ($statements as $statement) {
                        if (!empty(trim($statement))) {
                            try {
                                $pdo->exec($statement);
                            } catch (\PDOException $e2) {
                                // Ignore duplicate/already exists errors
                                if (strpos($e2->getMessage(), 'Duplicate') === false && 
                                    strpos($e2->getMessage(), 'already exists') === false &&
                                    strpos($e2->getMessage(), 'Table') === false) {
                                    throw $e2;
                                }
                            }
                        }
                    }
                }
                
                // Mark as executed
                $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?) ON DUPLICATE KEY UPDATE migration=migration");
                $stmt->execute([$migration]);
                
                $results[] = $migration;
            }
            
            // If using consolidated schema, mark all individual migrations as executed too
            if (in_array('000_initial_schema_v1.1.0.sql', $migrations)) {
                $allMigrations = [
                    '001_create_tables.sql',
                    '002_create_users_table.sql',
                    '003_add_whois_fields.sql',
                    '004_create_tld_registry_table.sql',
                    '005_update_tld_import_logs.sql',
                    '006_add_complete_workflow_import_type.sql',
                    '007_add_app_and_email_settings.sql',
                    '008_add_notes_to_domains.sql',
                    '009_add_authentication_features.sql',
                    '010_add_app_version_setting.sql',
                    '011_create_sessions_table.sql',
                    '012_link_remember_tokens_to_sessions.sql',
                    '013_create_user_notifications_table.sql',
                    '014_add_captcha_settings.sql',
                    '015_create_error_logs_table.sql',
                    '016_add_tags_to_domains.sql',
                    '017_add_two_factor_authentication.sql',
                    '018_add_user_isolation.sql',
                ];
                
                $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?) ON DUPLICATE KEY UPDATE migration=migration");
                foreach ($allMigrations as $individualMigration) {
                    $stmt->execute([$individualMigration]);
                }
            }
            
            // Update admin user to ensure role and verified status (in case migration already had defaults)
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin', email_verified = 1 WHERE username = ?");
            $stmt->execute([$adminUsername]);
            
            // Generate encryption key if not exists
            if (empty($_ENV['APP_ENCRYPTION_KEY'])) {
                $this->generateEncryptionKey();
            }
            
            // Create .installed flag file
            $installedFile = __DIR__ . '/../../.installed';
            file_put_contents($installedFile, date('Y-m-d H:i:s'));
            
            // Create welcome notification for admin
            try {
                // Get the admin user ID
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                $stmt->execute([$adminUsername]);
                $adminUser = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($adminUser) {
                    $notificationService = new \App\Services\NotificationService();
                    $notificationService->notifyWelcome($adminUser['id'], $adminUsername);
                }
            } catch (\Exception $e) {
                // Don't fail install if notification fails
                error_log("Failed to create welcome notification: " . $e->getMessage());
            }
            
            // Redirect to complete page
            $_SESSION['install_complete'] = true;
            $_SESSION['admin_username'] = $adminUsername;
            $_SESSION['admin_password'] = $adminPassword;
            $this->redirect('/install/complete');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Installation failed: ' . $e->getMessage();
            $this->redirect('/install');
        }
    }
    
    /**
     * Show update page
     */
    public function showUpdate()
    {
        $pending = $this->getPendingMigrations();
        
        if (empty($pending)) {
            $_SESSION['info'] = 'No updates available';
            $this->redirect('/');
            return;
        }
        
        $this->view('installer/update', [
            'title' => 'System Update',
            'migrations' => $pending
        ]);
    }
    
    /**
     * Run update
     */
    public function runUpdate()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/install/update');
            return;
        }
        
        try {
            $pdo = \Core\Database::getConnection();
            $migrations = $this->getPendingMigrations();
            $executed = [];
            
            foreach ($migrations as $migration) {
                $file = __DIR__ . '/../../database/migrations/' . $migration;
                if (!file_exists($file)) continue;
                
                $sql = file_get_contents($file);
                
                // Execute SQL
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                        } catch (\PDOException $e) {
                            // Ignore duplicate/already exists errors
                            if (strpos($e->getMessage(), 'Duplicate') === false && 
                                strpos($e->getMessage(), 'already exists') === false) {
                                throw $e;
                            }
                        }
                    }
                }
                
                // Mark as executed
                $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?) ON DUPLICATE KEY UPDATE migration=migration");
                $stmt->execute([$migration]);
                
                $executed[] = $migration;
            }
            
            // Create .installed flag file if doesn't exist (for v1.0.0 upgrades)
            $installedFile = __DIR__ . '/../../.installed';
            if (!file_exists($installedFile)) {
                file_put_contents($installedFile, date('Y-m-d H:i:s'));
            }
            
            // Notify admins about upgrade (if migrations were executed)
            if (!empty($executed)) {
                try {
                    $settingModel = new \App\Models\Setting();
                    $currentVersion = $settingModel->getAppVersion();
                    
                    // Determine from/to versions based on migrations
                    $fromVersion = '1.0.0';
                    $toVersion = '1.1.0';
                    
                    // Detect version based on which migrations were run
                    if (in_array('011_create_sessions_table.sql', $executed) || 
                        in_array('012_link_remember_tokens_to_sessions.sql', $executed) ||
                        in_array('013_create_user_notifications_table.sql', $executed)) {
                        $toVersion = '1.1.0';
                    }
                    
                    $notificationService = new \App\Services\NotificationService();
                    $notificationService->notifyAdminsUpgrade($fromVersion, $toVersion, count($executed));
                } catch (\Exception $e) {
                    // Don't fail upgrade if notification fails
                    error_log("Failed to create upgrade notification: " . $e->getMessage());
                }
            }
            
            $_SESSION['success'] = count($executed) . ' migration(s) executed successfully';
            $this->redirect('/');
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Update failed: ' . $e->getMessage();
            $this->redirect('/install/update');
        }
    }
    
    /**
     * Show installation complete page
     */
    public function complete()
    {
        if (!isset($_SESSION['install_complete'])) {
            $this->redirect('/');
            return;
        }
        
        $adminUsername = $_SESSION['admin_username'] ?? 'admin';
        $adminPassword = $_SESSION['admin_password'] ?? null;
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_password']);
        unset($_SESSION['install_complete']);
        
        $this->view('installer/complete', [
            'title' => 'Installation Complete',
            'adminUsername' => $adminUsername,
            'adminPassword' => $adminPassword
        ]);
    }
    
    /**
     * Parse SQL statements from a SQL file (fallback method)
     */
    private function parseSqlStatements(string $sql): array
    {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Split by semicolon, but be more careful about it
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar) {
                // Check for escaped quotes
                if ($i > 0 && $sql[$i-1] !== '\\') {
                    $inString = false;
                }
            } elseif (!$inString && $char === ';') {
                $statements[] = trim($current);
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Add the last statement if it doesn't end with semicolon
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return array_filter($statements, function($stmt) {
            return !empty(trim($stmt));
        });
    }

    /**
     * Generate encryption key
     */
    private function generateEncryptionKey()
    {
        $encryptionKey = base64_encode(random_bytes(32));
        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            
            if (strpos($envContent, 'APP_ENCRYPTION_KEY=') !== false) {
                $envContent = preg_replace(
                    '/APP_ENCRYPTION_KEY=.*$/m',
                    "APP_ENCRYPTION_KEY=$encryptionKey",
                    $envContent
                );
            } else {
                $envContent .= "\nAPP_ENCRYPTION_KEY=$encryptionKey\n";
            }
            
            file_put_contents($envFile, $envContent);
        }
    }
}

