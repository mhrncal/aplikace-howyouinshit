<?php
require_once __DIR__ . '/../../bootstrap.php';

header('Content-Type: application/json');

$auth->requireAuth();

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Services\XmlImportService;

$feedSourceModel = new FeedSource();
$userId = $auth->userId();
$feedId = (int) ($_POST['feed_id'] ?? 0);

if (!$feedId) {
    echo json_encode(['error' => 'Chybí feed_id']);
    exit;
}

$feed = $feedSourceModel->findById($feedId, $userId);

if (!$feed) {
    echo json_encode(['error' => 'Feed nenalezen']);
    exit;
}

// KRITICKÉ: Zavři session PŘED dlouhým importem
// Jinak se zamkne pro ostatní requesty
session_write_close();

// Nastavení pro dlouhý běh
set_time_limit(0); // Neomezený čas
ignore_user_abort(true); // Pokračuj i když uživatel zavře okno
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '0');

try {
    $xmlImporter = new XmlImportService();
    
    // Spusť import
    $result = $xmlImporter->importFromUrl(
        $feedId,
        $userId,
        $feed['url'],
        $feed['http_auth_username'] ?? null,
        $feed['http_auth_password'] ?? null
    );
    
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
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
