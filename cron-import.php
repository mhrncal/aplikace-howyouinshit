#!/usr/bin/env php
<?php
/**
 * CRON IMPORT - Postupný import všech aktivních feedů
 * 
 * POUŽITÍ:
 * */5 * * * * /usr/bin/php /cesta/k/cron-import.php >> /var/log/feed-import.log 2>&1
 * 
 * Běží každých 5 minut, importuje max 1 feed najednou
 */

require_once __DIR__ . '/bootstrap.php';

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Services\XmlImportService;
use App\Core\Logger;

// NASTAVENÍ
$MAX_CONCURRENT_IMPORTS = 1; // Jen 1 import najednou
$IMPORT_INTERVAL_MINUTES = 60; // Minimální interval mezi importy (1 hodina)
$LOCK_FILE = __DIR__ . '/storage/import.lock';
$MAX_EXECUTION_TIME = 1800; // 30 minut max

// Kontrola lock souboru (prevence duplicitních běhů)
if (file_exists($LOCK_FILE)) {
    $lockTime = filemtime($LOCK_FILE);
    
    // Pokud lock je starší než MAX_EXECUTION_TIME, smaž ho (stuck import)
    if (time() - $lockTime > $MAX_EXECUTION_TIME) {
        unlink($LOCK_FILE);
        Logger::warning('Removed stuck import lock', ['lock_age_seconds' => time() - $lockTime]);
    } else {
        // Běží jiný import, exit
        Logger::info('Import already running, skipping');
        exit(0);
    }
}

// Vytvoř lock
touch($LOCK_FILE);

try {
    set_time_limit($MAX_EXECUTION_TIME);
    ini_set('memory_limit', '512M');
    
    Logger::info('=== CRON IMPORT STARTED ===');
    
    $feedSourceModel = new FeedSource();
    $db = App\Core\Database::getInstance();
    
    // Najdi JEDEN feed k importu
    // Priority:
    // 1. Feedy které nikdy nebyly importovány
    // 2. Feedy kde poslední import byl před X minutami
    // 3. Podle schedule (daily, hourly...)
    
    $sql = "
        SELECT fs.*, u.email as user_email 
        FROM feed_sources fs
        JOIN users u ON fs.user_id = u.id
        WHERE fs.is_active = 1
        AND u.is_active = 1
        AND (
            fs.last_imported_at IS NULL
            OR fs.last_imported_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)
        )
        ORDER BY 
            fs.last_imported_at IS NULL DESC,
            fs.last_imported_at ASC
        LIMIT 1
    ";
    
    $feed = $db->fetchOne($sql, [$IMPORT_INTERVAL_MINUTES]);
    
    if (!$feed) {
        Logger::info('No feeds to import');
        unlink($LOCK_FILE);
        exit(0);
    }
    
    Logger::info('Starting import', [
        'feed_id' => $feed['id'],
        'feed_name' => $feed['name'],
        'user_id' => $feed['user_id'],
        'user_email' => $feed['user_email'],
        'url' => substr($feed['url'], 0, 100) . '...'
    ]);
    
    echo "Importing feed #{$feed['id']}: {$feed['name']}\n";
    echo "User: {$feed['user_email']}\n";
    echo "URL: {$feed['url']}\n\n";
    
    $startTime = microtime(true);
    
    // SPUSŤ IMPORT
    $importer = new XmlImportService();
    $result = $importer->importFromUrl(
        $feed['id'],
        $feed['user_id'],
        $feed['url'],
        $feed['http_auth_username'] ?? null,
        $feed['http_auth_password'] ?? null
    );
    
    $duration = round(microtime(true) - $startTime);
    $memoryPeak = round(memory_get_peak_usage() / 1024 / 1024, 2);
    
    Logger::info('Import completed', [
        'feed_id' => $feed['id'],
        'imported' => $result['imported'],
        'updated' => $result['updated'],
        'errors' => $result['errors'],
        'duration_seconds' => $duration,
        'memory_peak_mb' => $memoryPeak
    ]);
    
    echo "\n✅ IMPORT DOKONČEN!\n";
    echo "Importováno: {$result['imported']}\n";
    echo "Aktualizováno: {$result['updated']}\n";
    echo "Chyby: {$result['errors']}\n";
    echo "Čas: {$duration}s\n";
    echo "Paměť: {$memoryPeak} MB\n";
    
    // Update last_import_at
    $db->query(
        "UPDATE feed_sources SET last_imported_at = NOW() WHERE id = ?",
        [$feed['id']]
    );
    
} catch (\Throwable $e) {
    Logger::error('Cron import failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "\n❌ CHYBA IMPORTU!\n";
    echo $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    
} finally {
    // Vždy odstraň lock
    if (file_exists($LOCK_FILE)) {
        unlink($LOCK_FILE);
    }
    
    Logger::info('=== CRON IMPORT FINISHED ===');
}

exit(0);
