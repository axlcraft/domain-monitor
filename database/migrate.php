#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
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

