<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
.error { color: #f48771; background: #3c1f1e; padding: 10px; margin: 10px 0; border-left: 4px solid #f48771; }
.success { color: #4ec9b0; }
.info { color: #9cdcfe; }
.warning { color: #ce9178; }
pre { background: #2d2d2d; padding: 10px; overflow-x: auto; }
.step { margin: 15px 0; padding: 10px; border-left: 3px solid #569cd6; }
</style></head><body>";

echo "<h1>🔍 SUPER DETAILNÍ DEBUG IMPORTU</h1>";

require_once __DIR__ . '/../../bootstrap.php';

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Services\XmlImportService;
use App\Modules\Products\Models\Product;

$userId = 1;
$feedId = 2;

try {
    echo "<div class='step'><strong>KROK 1:</strong> Načítání feedu z DB...</div>";
    
    $feedSourceModel = new FeedSource();
    $feed = $feedSourceModel->findById($feedId, $userId);
    
    if (!$feed) {
        throw new Exception("Feed nenalezen!");
    }
    
    echo "<span class='success'>✅ Feed načten: {$feed['name']}</span><br>";
    echo "<span class='info'>URL: {$feed['url']}</span><br><br>";
    
    // KROK 2: Test Product modelu
    echo "<div class='step'><strong>KROK 2:</strong> Test Product::batchUpsert()...</div>";
    
    $productModel = new Product();
    
    // Test prázdné pole
    echo "Test 1: Prázdné pole<br>";
    $result = $productModel->batchUpsert([]);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    // Test s 1 produktem
    echo "Test 2: Jeden produkt<br>";
    $testProduct = [
        'user_id' => $userId,
        'name' => 'Test produkt',
        'code' => 'TEST123',
        'price' => 100,
        'price_vat' => 121,
        'category' => 'Test',
        'description' => 'Test popis',
        'url' => 'http://test.cz',
        'image_url' => 'http://test.cz/img.jpg',
        'availability' => 'Skladem',
        'variants' => [] // BEZ variant
    ];
    
    echo "<strong>Produkt data:</strong><pre>";
    print_r($testProduct);
    echo "</pre>";
    
    echo "<strong>Volání batchUpsert...</strong><br>";
    
    try {
        $result = $productModel->batchUpsert([$testProduct]);
        echo "<span class='success'>✅ batchUpsert OK!</span><br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } catch (\Throwable $e) {
        echo "<div class='error'>";
        echo "<strong>❌ CHYBA V batchUpsert:</strong><br>";
        echo "Message: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
        echo "<strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        
        // Zobraz zdrojový kód okolo chyby
        if (file_exists($e->getFile())) {
            $lines = file($e->getFile());
            $errorLine = $e->getLine();
            $start = max(0, $errorLine - 5);
            $end = min(count($lines), $errorLine + 5);
            
            echo "<div class='error'>";
            echo "<strong>Zdrojový kód okolo řádku {$errorLine}:</strong><br>";
            echo "<pre>";
            for ($i = $start; $i < $end; $i++) {
                $lineNum = $i + 1;
                $prefix = ($lineNum == $errorLine) ? ">>> " : "    ";
                echo $prefix . str_pad($lineNum, 4, ' ', STR_PAD_LEFT) . " | " . htmlspecialchars($lines[$i]);
            }
            echo "</pre>";
            echo "</div>";
        }
        
        throw $e;
    }
    
    // KROK 3: Test XML parseru
    echo "<div class='step'><strong>KROK 3:</strong> Test XML parseru...</div>";
    
    $url = $feed['url'];
    echo "Stahuji XML...<br>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $xmlContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP chyba: {$httpCode}");
    }
    
    echo "<span class='success'>✅ XML staženo: " . round(strlen($xmlContent) / 1024 / 1024, 2) . " MB</span><br><br>";
    
    // Parsuj prvních 5 produktů
    echo "Parsuji prvních 5 SHOPITEM elementů...<br><br>";
    
    $reader = new \XMLReader();
    $reader->XML($xmlContent);
    
    $count = 0;
    $parsed = [];
    
    while ($reader->read() && $count < 5) {
        if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'SHOPITEM') {
            $count++;
            
            $xml = simplexml_load_string($reader->readOuterXml());
            
            echo "<div class='step'><strong>SHOPITEM #{$count}:</strong></div>";
            
            $name = (string) ($xml->NAME ?? '');
            $code = (string) ($xml->CODE ?? '');
            $price = (float) ($xml->PRICE_VAT ?? 0);
            
            echo "Název: <strong>{$name}</strong><br>";
            echo "Kód: <code>{$code}</code><br>";
            echo "Cena: {$price} Kč<br>";
            
            // Kontrola variant
            if (isset($xml->VARIANTS->VARIANT)) {
                $variantCount = count($xml->VARIANTS->VARIANT);
                echo "Varianty: <strong>{$variantCount}</strong><br>";
                
                foreach ($xml->VARIANTS->VARIANT as $idx => $variant) {
                    $vCode = (string) ($variant->CODE ?? '');
                    $vPrice = (float) ($variant->PRICE_VAT ?? 0);
                    echo "&nbsp;&nbsp;└ Varianta {$idx}: {$vCode} ({$vPrice} Kč)<br>";
                }
            } else {
                echo "Varianty: <em>žádné</em><br>";
            }
            
            // Vytvoř produkt pole
            $product = [
                'user_id' => $userId,
                'name' => $name,
                'code' => $code,
                'price' => $price,
                'price_vat' => $price,
                'category' => (string) ($xml->CATEGORIES->DEFAULT_CATEGORY ?? ''),
                'description' => strip_tags((string) ($xml->DESCRIPTION ?? '')),
                'url' => (string) ($xml->ORIG_URL ?? ''),
                'image_url' => (string) ($xml->IMAGES->IMAGE[0] ?? ''),
                'availability' => 'Skladem',
                'variants' => []
            ];
            
            $parsed[] = $product;
            
            echo "<br>";
        }
    }
    
    $reader->close();
    
    echo "<div class='step'><strong>KROK 4:</strong> Test batch upsert s reálnými daty...</div>";
    
    echo "Ukládám " . count($parsed) . " produktů do DB...<br>";
    
    try {
        $result = $productModel->batchUpsert($parsed);
        echo "<span class='success'>✅ Batch upsert úspěšný!</span><br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } catch (\Throwable $e) {
        echo "<div class='error'>";
        echo "<strong>❌ CHYBA při batch upsert:</strong><br>";
        echo "Message: " . $e->getMessage() . "<br>";
        echo "File: " . $e->getFile() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
        
        // Zobraz který produkt způsobil chybu
        echo "<br><strong>Produkty v batchi:</strong><pre>";
        foreach ($parsed as $idx => $p) {
            echo "Produkt #{$idx}: {$p['name']} ({$p['code']})\n";
        }
        echo "</pre>";
        
        echo "<strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
        echo "</div>";
        
        throw $e;
    }
    
    echo "<br><h2 class='success'>✅ VŠECHNY TESTY PROŠLY!</h2>";
    
} catch (\Throwable $e) {
    echo "<div class='error'>";
    echo "<h2>❌ FATAL ERROR</h2>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<br><strong>Full stack trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
