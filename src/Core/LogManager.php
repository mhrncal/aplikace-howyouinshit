<?php

namespace App\Core;

/**
 * Organizovaný logging systém s projekty a typy
 */
class LogManager
{
    private static string $baseLogDir = __DIR__ . '/../../storage/logs';
    
    /**
     * Vyčistí logy před importem
     */
    public static function clearImportLogs(int $userId, int $feedId): void
    {
        $logFile = self::getImportLogPath($userId, $feedId);
        
        if (file_exists($logFile)) {
            // Archivuj starý log
            $archiveFile = str_replace('.log', '_' . date('Ymd_His') . '.log', $logFile);
            rename($logFile, $archiveFile);
            
            // Smaž staré archivy (starší než 7 dní)
            self::cleanOldArchives(dirname($logFile), 7);
        }
    }
    
    /**
     * Získá cestu k logu importu
     */
    public static function getImportLogPath(int $userId, int $feedId): string
    {
        $dir = self::$baseLogDir . "/imports/user_{$userId}/feed_{$feedId}";
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        return $dir . '/import_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Získá cestu k logu produktů
     */
    public static function getProductLogPath(int $userId): string
    {
        $dir = self::$baseLogDir . "/products/user_{$userId}";
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        return $dir . '/products_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Získá cestu k logu nákladů
     */
    public static function getCostsLogPath(int $userId): string
    {
        $dir = self::$baseLogDir . "/costs/user_{$userId}";
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        return $dir . '/costs_' . date('Y-m-d') . '.log';
    }
    
    /**
     * Získá cestu k aplikačnímu logu
     */
    public static function getAppLogPath(): string
    {
        return self::$baseLogDir . '/app.log';
    }
    
    /**
     * Loguje zprávu do specifického logu
     */
    public static function log(string $type, string $message, array $context = [], ?int $userId = null, ?int $relatedId = null): void
    {
        $logFile = match($type) {
            'import' => $relatedId ? self::getImportLogPath($userId, $relatedId) : self::getAppLogPath(),
            'product' => $userId ? self::getProductLogPath($userId) : self::getAppLogPath(),
            'costs' => $userId ? self::getCostsLogPath($userId) : self::getAppLogPath(),
            default => self::getAppLogPath(),
        };
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $logLine = "[{$timestamp}] [{$type}] {$message}{$contextStr}\n";
        
        // Vytvoř složku pokud neexistuje
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        // TAKÉ do hlavního logu pro přehled
        if ($type !== 'app') {
            file_put_contents(self::getAppLogPath(), $logLine, FILE_APPEND);
        }
    }
    
    /**
     * Vyčistí staré archivy
     */
    private static function cleanOldArchives(string $dir, int $daysOld): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = glob($dir . '/*_*.log');
        $cutoff = time() - ($daysOld * 86400);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    /**
     * Získá seznam logů pro zobrazení
     */
    public static function getRecentLogs(string $type, int $userId, int $limit = 100): array
    {
        $logFile = match($type) {
            'import' => self::$baseLogDir . "/imports/user_{$userId}",
            'product' => self::getProductLogPath($userId),
            'costs' => self::getCostsLogPath($userId),
            default => self::getAppLogPath(),
        };
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        return array_slice(array_reverse($lines), 0, $limit);
    }
}
