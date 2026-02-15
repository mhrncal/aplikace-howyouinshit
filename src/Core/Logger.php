<?php

namespace App\Core;

/**
 * Jednoduchý logger do souborů
 */
class Logger
{
    private static string $logPath = '';

    public static function init(): void
    {
        self::$logPath = dirname(__DIR__, 2) . '/storage/logs';
        
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }

    /**
     * Info level log
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Warning level log
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Error level log
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Debug level log
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Hlavní logging metoda
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        if (empty(self::$logPath)) {
            self::init();
        }

        $date = date('Y-m-d');
        $time = date('H:i:s');
        $logFile = self::$logPath . "/app-{$date}.log";

        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[{$time}] [{$level}] {$message}{$contextString}\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Vyčistí staré logy (starší než 30 dní)
     */
    public static function cleanOldLogs(int $days = 30): void
    {
        if (empty(self::$logPath)) {
            self::init();
        }

        $files = glob(self::$logPath . '/app-*.log');
        $cutoffTime = time() - ($days * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}
