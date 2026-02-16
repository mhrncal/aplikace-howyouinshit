<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(10); // Max 10 sekund

echo "<h2>Test feed-sources (detailní)</h2><pre>";

try {
    echo "1. Načítám bootstrap...\n";
    flush();
    ob_flush();
    require_once __DIR__ . '/../../bootstrap.php';
    echo "   ✅ Bootstrap načten\n";
    flush();
    ob_flush();
    
    echo "2. Kontroluji auth objekt...\n";
    flush();
    ob_flush();
    if (!isset($auth)) {
        throw new Exception("Auth objekt neexistuje!");
    }
    echo "   ✅ Auth objekt existuje\n";
    echo "   - Class: " . get_class($auth) . "\n";
    flush();
    ob_flush();
    
    echo "3. Kontroluji, jestli je check() metoda...\n";
    flush();
    ob_flush();
    if (!method_exists($auth, 'check')) {
        throw new Exception("Auth nemá metodu check()");
    }
    echo "   ✅ check() metoda existuje\n";
    flush();
    ob_flush();
    
    echo "4. Volám check()...\n";
    flush();
    ob_flush();
    $isLoggedIn = $auth->check();
    echo "   ✅ check() vrátil: " . ($isLoggedIn ? 'TRUE' : 'FALSE') . "\n";
    flush();
    ob_flush();
    
    if (!$isLoggedIn) {
        echo "   ⚠️  Nejsi přihlášený - přesměrování by proběhlo\n";
        echo "   Zkus otevřít normální stránku /app/orders/feed-sources.php\n";
        exit;
    }
    
    echo "5. Volám requireAuth()...\n";
    flush();
    ob_flush();
    // Toto může redirectovat, takže musí být poslední
    $auth->requireAuth();
    echo "   ✅ requireAuth() prošel\n";
    flush();
    ob_flush();
    
    echo "6. Získávám userId...\n";
    flush();
    ob_flush();
    $userId = $auth->userId();
    echo "   ✅ UserId: $userId\n";
    flush();
    ob_flush();
    
    echo "7. Načítám OrderFeedSource model...\n";
    flush();
    ob_flush();
    $feedModel = new \App\Models\OrderFeedSource();
    echo "   ✅ Model načten\n";
    flush();
    ob_flush();
    
    echo "8. Načítám feed sources...\n";
    flush();
    ob_flush();
    $feeds = $feedModel->getAll($userId);
    echo "   ✅ Počet feedů: " . count($feeds) . "\n";
    flush();
    ob_flush();
    
    echo "\n9. Feedy:\n";
    foreach ($feeds as $feed) {
        echo "   - ID: {$feed['id']}, Name: {$feed['name']}\n";
    }
    
    echo "\n✅ VŠE FUNGUJE!\n";
    echo "\nPokud toto funguje, problém je v samotné feed-sources.php stránce.\n";
    
} catch (\Exception $e) {
    echo "\n❌ CHYBA:\n";
    echo "   Zpráva: " . $e->getMessage() . "\n";
    echo "   Soubor: " . $e->getFile() . "\n";
    echo "   Řádek: " . $e->getLine() . "\n";
    echo "\n   Stack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

echo "<hr>";
echo "<p><strong>Pokud se to zaseklo na requireAuth():</strong></p>";
echo "<p>Auth třída asi volá redirect() a stránka se přesměruje.</p>";
echo "<p>V tom případě zkus přímo otevřít: <a href='/app/orders/feed-sources.php'>/app/orders/feed-sources.php</a></p>";
