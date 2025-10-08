<?php

namespace Core;

class Encryption
{
    private string $key;
    private string $cipher = 'AES-256-CBC';

    public function __construct()
    {
        $key = $_ENV['APP_ENCRYPTION_KEY'] ?? null;
        
        if (empty($key)) {
            throw new \Exception('APP_ENCRYPTION_KEY is not set in .env file. Generate one using: php -r "echo base64_encode(random_bytes(32));"');
        }

        // Decode the base64 key
        $this->key = base64_decode($key);
        
        if (strlen($this->key) !== 32) {
            throw new \Exception('APP_ENCRYPTION_KEY must be 32 bytes (base64 encoded). Generate one using: php -r "echo base64_encode(random_bytes(32));"');
        }
    }

    /**
     * Encrypt a value
     */
    public function encrypt(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        // Generate a random IV (Initialization Vector)
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));

        // Encrypt the value
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);

        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }

        // Combine IV and encrypted data, then base64 encode
        $result = base64_encode($iv . $encrypted);

        return $result;
    }

    /**
     * Decrypt a value
     */
    public function decrypt(string $encrypted): string
    {
        if (empty($encrypted)) {
            return '';
        }

        // Decode from base64
        $data = base64_decode($encrypted);

        if ($data === false) {
            throw new \Exception('Invalid encrypted data');
        }

        // Extract IV and encrypted data
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encryptedData = substr($data, $ivLength);

        // Decrypt the value
        $decrypted = openssl_decrypt($encryptedData, $this->cipher, $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Generate a new encryption key (base64 encoded)
     * This should be run once and the result stored in .env
     */
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Check if a value is encrypted (basic heuristic)
     */
    public function isEncrypted(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Encrypted values are base64 encoded
        // They should be longer than typical plaintext passwords
        // and contain only base64 characters
        return preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) > 40;
    }
}

