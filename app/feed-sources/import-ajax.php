<?php
// Debug mode pro testování
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nezobrazuj, ale loguj
ini_set('log_errors', 1);

require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json');

try {
    $auth->requireAuth();
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Neautorizováno: ' . $e->getMessage()]);
    exit;
}

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Services\XmlImportService;
use App\Core\Logger;

$feedSourceModel = new FeedSource();
$userId = $auth->userId();
$feedId = (int) ($_POST['feed_id'] ?? 0);

Logger::info('Import AJAX called', ['feed_id' => $feedId, 'user_id' => $userId]);

if (!$feedId) {
    echo json_encode(['success' => false, 'error' => 'Chybí feed_id']);
    exit;
}

$feed = $feedSourceModel->findById($feedId, $userId);

if (!$feed) {
    echo json_encode(['success' => false, 'error' => 'Feed nenalezen']);
    exit;
}

Logger::info('Feed loaded', ['feed' => $feed['name']]);

// KRITICKÉ: Zavři session PŘED dlouhým importem
session_write_close();

// Nastavení pro dlouhý běh
set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '0');

try {
    Logger::info('Starting import', ['url' => $feed['url']]);
    
    $xmlImporter = new XmlImportService();
    
    // Spusť import
    $result = $xmlImporter->importFromUrl(
        $feedId,
        $userId,
        $feed['url'],
        $feed['http_auth_username'] ?? null,
        $feed['http_auth_password'] ?? null
    );
    
    Logger::info('Import completed', $result);
    
    // Update last_import_at
    $feedSourceModel->update($feedId, $userId, [
        'last_import_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'imported' => $result['imported'],
        'updated' => $result['updated'],
        'errors' => $result['errors'],
    ]);
    
} catch (\Throwable $e) {
    Logger::error('Import failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => substr($e->getTraceAsString(), 0, 500)
    ]);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
    ]);
}
