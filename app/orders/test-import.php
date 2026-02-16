<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

$userId = $auth->userId();

echo "<h2>Test importu CSV</h2><pre>";

// Test URL
$url = "https://www.lasilueta.cz/export/orders.csv?patternId=43&dateFrom=2026-1-1&partnerId=3&dateUntil=2026-2-5&hash=2232b4a013150f6c70bf31f1bff16a45e4e6f0f3c9908283b838f2480d0ce494";

echo "1. Inicializace importu...\n";
$importService = new \App\Services\OrderCsvImportService();
echo "   ✅ Service vytvořen\n\n";

echo "2. Spouštím import...\n";
$result = $importService->importFromUrl($userId, $url);

echo "\n3. Výsledek:\n";
print_r($result);

if ($result['success']) {
    echo "\n✅ Import úspěšný!\n";
    echo "   - Objednávek: {$result['orders_imported']}\n";
    echo "   - Položek: {$result['items_imported']}\n";
    
    // Zobraz objednávky z DB
    echo "\n4. Kontrola v databázi:\n";
    $db = \App\Core\Database::getInstance();
    $orders = $db->fetchAll("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 5", [$userId]);
    echo "   - Nalezeno v DB: " . count($orders) . " objednávek\n";
    
    foreach ($orders as $order) {
        echo "   - {$order['order_code']}: {$order['total_revenue']} Kč\n";
    }
} else {
    echo "\n❌ Import selhal: {$result['error']}\n";
}

echo "</pre>";

echo "<hr>";
echo "<p><a href='/app/orders/'>Zobrazit objednávky</a></p>";
