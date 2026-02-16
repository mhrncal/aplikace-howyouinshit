<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test feed-sources</h2><pre>";

try {
    echo "1. Načítám bootstrap...\n";
    require_once __DIR__ . '/../../bootstrap.php';
    echo "   ✅ Bootstrap načten\n";
    
    echo "2. Kontroluji auth...\n";
    $auth->requireAuth();
    echo "   ✅ Uživatel přihlášen\n";
    
    echo "3. Načítám OrderFeedSource model...\n";
    $feedModel = new \App\Models\OrderFeedSource();
    echo "   ✅ Model načten\n";
    
    echo "4. Získávám userId...\n";
    $userId = $auth->userId();
    echo "   ✅ UserId: $userId\n";
    
    echo "5. Načítám feed sources...\n";
    $feeds = $feedModel->getAll($userId);
    echo "   ✅ Počet feedů: " . count($feeds) . "\n";
    
    echo "\n6. Feedy:\n";
    foreach ($feeds as $feed) {
        echo "   - ID: {$feed['id']}, Name: {$feed['name']}\n";
    }
    
    echo "\n✅ VŠE FUNGUJE!\n";
    
} catch (\Exception $e) {
    echo "\n❌ CHYBA:\n";
    echo "   Zpráva: " . $e->getMessage() . "\n";
    echo "   Soubor: " . $e->getFile() . "\n";
    echo "   Řádek: " . $e->getLine() . "\n";
    echo "\n   Stack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
