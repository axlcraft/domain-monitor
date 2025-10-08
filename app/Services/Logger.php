<?php

namespace App\Services;

class Logger
{
    private string $logDir;
    private string $currentLogFile;
    private bool $enabled;

    public function __construct(string $logName = 'app', bool $enabled = true)
    {
        $this->logDir = __DIR__ . '/../../logs';
        $this->enabled = $enabled;
        
        // Create logs directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Set log file name with date
        $date = date('Y-m-d');
        $this->currentLogFile = $this->logDir . '/' . $logName . '_' . $date . '.log';
    }

    /**
     * Log a message with level
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";
        
        file_put_contents($this->currentLogFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Log progress with percentage
     */
    public function progress(string $message, int $current, int $total, array $context = []): void
    {
        $percentage = $total > 0 ? round(($current / $total) * 100, 2) : 0;
        $progressMessage = "{$message} [{$current}/{$total}] ({$percentage}%)";
        $this->info($progressMessage, $context);
    }

    /**
     * Log separator for better readability
     */
    public function separator(string $title = ''): void
    {
        $line = str_repeat('=', 80);
        if (!empty($title)) {
            $titleLine = "=== {$title} " . str_repeat('=', 80 - strlen($title) - 5);
            $this->log('INFO', $titleLine);
        } else {
            $this->log('INFO', $line);
        }
    }

    /**
     * Log start of operation
     */
    public function startOperation(string $operation, array $context = []): void
    {
        $this->separator("START: {$operation}");
        $this->info("Starting operation: {$operation}", $context);
    }

    /**
     * Log end of operation
     */
    public function endOperation(string $operation, array $stats = []): void
    {
        $this->info("Completed operation: {$operation}", $stats);
        $this->separator("END: {$operation}");
    }

    /**
     * Get log file path
     */
    public function getLogFile(): string
    {
        return $this->currentLogFile;
    }

    /**
     * Clear current log file
     */
    public function clear(): void
    {
        if (file_exists($this->currentLogFile)) {
            unlink($this->currentLogFile);
        }
    }

    /**
     * Read last N lines from log file
     */
    public function tail(int $lines = 100): array
    {
        if (!file_exists($this->currentLogFile)) {
            return [];
        }

        $file = file($this->currentLogFile);
        return array_slice($file, -$lines);
    }
}

