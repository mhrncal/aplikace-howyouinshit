<?php

namespace App\Core;

/**
 * Pokročilý logger s kategoriemi a rotací
 * 
 * KATEGORIE:
 * - app: Obecná aplikace
 * - import: XML/CSV importy
 * - auth: Přihlášení, registrace
 * - api: API volání
 * - error: Chyby aplikace
 * - cron: Cron joby
 * - db: Databázové operace
 */
class Logger
{
    private static string $logPath = '';
    private static int $maxFileSize = 10485760; // 10 MB
    private static int $keepDays = 30; // Kolik dní uchovávat logy

    public static function init(): void
    {
        self::$logPath = dirname(__DIR__, 2) . '/storage/logs';
        
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
        
        // Auto-cleanup při inicializaci (1% šance)
        if (rand(1, 100) === 1) {
            self::cleanOldLogs();
        }
    }

    /**
     * Info level log
     */
    public static function info(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('INFO', $message, $context, $category);
    }

    /**
     * Warning level log
     */
    public static function warning(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('WARNING', $message, $context, $category);
    }

    /**
     * Error level log
     */
    public static function error(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('ERROR', $message, $context, $category);
    }

    /**
     * Debug level log
     */
    public static function debug(string $message, array $context = [], string $category = 'app'): void
    {
        self::log('DEBUG', $message, $context, $category);
    }

    /**
     * Hlavní logging metoda s KATEGORIEMI a ROTACÍ
     */
    private static function log(string $level, string $message, array $context = [], string $category = 'app'): void
    {
        if (empty(self::$logPath)) {
            self::init();
        }

        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        // SPECIÁLNÍ: Import logy jdou přes LogManager do složek
        if ($category === 'import' && isset($context['user_id']) && isset($context['feed_source_id'])) {
            LogManager::log('import', "[$level] $message", $context, $context['user_id'], $context['feed_source_id']);
            return;
        }
        
        // LOG SOUBOR podle KATEGORIE: import-2026-02-15.log, auth-2026-02-15.log
        $logFile = self::$logPath . "/{$category}-{$date}.log";
        
        // ROTACE - pokud soubor > 10 MB, přejmenuj a začni nový
        if (file_exists($logFile) && filesize($logFile) > self::$maxFileSize) {
            $rotated = self::$logPath . "/{$category}-{$date}-" . time() . ".log";
            rename($logFile, $rotated);
        }

        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $logMessage = "[{$time}] [{$level}] {$message}{$contextString}\n";

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Vyčistí staré logy (automaticky)
     */
    public static function cleanOldLogs(int $days = null): void
    {
        if (empty(self::$logPath)) {
            self::init();
        }
        
        $days = $days ?? self::$keepDays;
        $files = glob(self::$logPath . '/*.log');
        $cutoffTime = time() - ($days * 86400);
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            self::info("Cleaned {$deleted} old log files", [], 'app');
        }
    }
    
    /**
     * Smaž všechny logy (pro development/reset)
     */
    public static function clearAll(): void
    {
        if (empty(self::$logPath)) {
            self::init();
        }
        
        $files = glob(self::$logPath . '/*.log');
        
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    /**
     * Získej seznam log souborů podle kategorie
     */
    public static function getLogFiles(string $category = null): array
    {
        if (empty(self::$logPath)) {
            self::init();
        }
        
        $pattern = $category ? "{$category}-*.log" : "*.log";
        $files = glob(self::$logPath . '/' . $pattern);
        
        $result = [];
        foreach ($files as $file) {
            $result[] = [
                'file' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'size_mb' => round(filesize($file) / 1024 / 1024, 2),
                'modified' => filemtime($file),
                'modified_date' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }
        
        // Seřaď podle data (nejnovější první)
        usort($result, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $result;
    }
    
    /**
     * Přečti obsah log souboru (poslední N řádků)
     */
    public static function readLog(string $filename, int $lines = 100): array
    {
        if (empty(self::$logPath)) {
            self::init();
        }
        
        $file = self::$logPath . '/' . basename($filename);
        
        if (!file_exists($file)) {
            return [];
        }
        
        // Čti poslední N řádků
        $content = file($file);
        $content = array_slice($content, -$lines);
        
        return array_reverse($content);
    }
}
