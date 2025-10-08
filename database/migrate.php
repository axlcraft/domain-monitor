#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    // Check if encryption key is set, if not generate and save it
    if (empty($_ENV['APP_ENCRYPTION_KEY'])) {
        echo "🔑 Generating encryption key...\n";
        
        // Generate a secure 32-byte (256-bit) key
        $encryptionKey = base64_encode(random_bytes(32));
        
        // Path to .env file
        $envFile = __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            echo "✗ Error: .env file not found. Please create it first.\n";
            exit(1);
        }
        
        // Read current .env content
        $envContent = file_get_contents($envFile);
        
        // Check if APP_ENCRYPTION_KEY line exists
        if (strpos($envContent, 'APP_ENCRYPTION_KEY=') !== false) {
            // Replace empty value with generated key
            $envContent = preg_replace(
                '/APP_ENCRYPTION_KEY=.*$/m',
                "APP_ENCRYPTION_KEY=$encryptionKey",
                $envContent
            );
        } else {
            // Append the key to the file
            $envContent .= "\nAPP_ENCRYPTION_KEY=$encryptionKey\n";
        }
        
        // Write updated content back to .env
        if (!file_put_contents($envFile, $envContent)) {
            echo "✗ Error: Could not write to .env file.\n";
            exit(1);
        }
        
        // Reload environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        
        echo "✓ Encryption key generated and saved to .env\n";
        echo "   Key: $encryptionKey\n";
        echo "   ⚠️  Keep this key secret and backup securely!\n\n";
    }

    $host = $_ENV['DB_HOST'];
    $port = $_ENV['DB_PORT'];
    $database = $_ENV['DB_DATABASE'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];

    // Connect to database
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Connected to database successfully!\n\n";

    // Generate random admin password
    $adminPassword = bin2hex(random_bytes(8)); // 16 character random password
    $adminPasswordHash = password_hash($adminPassword, PASSWORD_BCRYPT);

    // Get all migration files
    $migrationFiles = [
        __DIR__ . '/migrations/001_create_tables.sql',
        __DIR__ . '/migrations/002_create_users_table.sql',
        __DIR__ . '/migrations/003_add_whois_fields.sql',
        __DIR__ . '/migrations/004_create_tld_registry_table.sql',
        __DIR__ . '/migrations/005_update_tld_import_logs.sql',
        __DIR__ . '/migrations/006_add_complete_workflow_import_type.sql',
        __DIR__ . '/migrations/007_add_app_and_email_settings.sql',
        __DIR__ . '/migrations/008_remove_mail_driver.sql',
    ];

    foreach ($migrationFiles as $migrationFile) {
        if (!file_exists($migrationFile)) {
            echo "⚠ Migration file not found: " . basename($migrationFile) . "\n";
            continue;
        }

        echo "Running migration: " . basename($migrationFile) . "\n";
        
        $sql = file_get_contents($migrationFile);

        // Replace password placeholder in users migration
        if (basename($migrationFile) === '002_create_users_table.sql') {
            $sql = str_replace('{{ADMIN_PASSWORD_HASH}}', $adminPasswordHash, $sql);
        }

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Check if it's a "column already exists" error for migrations 003 and 005
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false && 
                        (basename($migrationFile) === '003_add_whois_fields.sql' || 
                         basename($migrationFile) === '005_update_tld_import_logs.sql')) {
                        echo "  ⚠ Column already exists, skipping: " . $e->getMessage() . "\n";
                        continue;
                    }
                    // Check if it's an enum modification error for migrations 005 and 006
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false && 
                        (basename($migrationFile) === '005_update_tld_import_logs.sql' ||
                         basename($migrationFile) === '006_add_complete_workflow_import_type.sql')) {
                        echo "  ⚠ Enum already updated, skipping: " . $e->getMessage() . "\n";
                        continue;
                    }
                    // Re-throw other errors
                    throw $e;
                }
            }
        }

        echo "✓ " . basename($migrationFile) . " completed\n";
    }

    echo "\n✓ All migrations completed successfully!\n";
    echo "✓ All tables created.\n";
    echo "\n🔑 Admin credentials (SAVE THESE!):\n";
    echo "   ═══════════════════════════════════════\n";
    echo "   Username: admin\n";
    echo "   Password: $adminPassword\n";
    echo "   ═══════════════════════════════════════\n";
    echo "   ⚠️  This password will not be shown again!\n";
    echo "   💾 Save it to a secure password manager.\n\n";
    echo "🌐 TLD Registry System:\n";
    echo "   • Import RDAP data: php cron/import_tld_registry.php --rdap-only\n";
    echo "   • Import WHOIS data: php cron/import_tld_registry.php --whois-only\n";
    echo "   • Check for updates: php cron/import_tld_registry.php --check-updates\n";
    echo "   • Full import: php cron/import_tld_registry.php\n\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

