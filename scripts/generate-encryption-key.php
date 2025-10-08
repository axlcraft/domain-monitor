#!/usr/bin/env php
<?php

/**
 * Generate Encryption Key
 * 
 * This script generates a secure encryption key for the application.
 * The key is used to encrypt sensitive data in the database (like SMTP passwords).
 * 
 * Usage: php scripts/generate-encryption-key.php
 */

echo "===========================================\n";
echo "  Domain Monitor - Encryption Key Generator\n";
echo "===========================================\n\n";

// Generate a secure 32-byte (256-bit) key
$key = random_bytes(32);
$encodedKey = base64_encode($key);

echo "Your encryption key has been generated:\n\n";
echo "\033[1;32m$encodedKey\033[0m\n\n";

echo "Add this to your .env file:\n\n";
echo "APP_ENCRYPTION_KEY=$encodedKey\n\n";

echo "⚠️  IMPORTANT SECURITY NOTES:\n";
echo "-------------------------------------------\n";
echo "1. Keep this key SECRET - never share it\n";
echo "2. Never commit this key to version control\n";
echo "3. If you lose this key, encrypted data cannot be recovered\n";
echo "4. Don't change this key after encrypting data\n";
echo "5. Store a backup of this key in a secure location\n\n";

echo "✅ Done! Copy the key to your .env file.\n";

