<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
.ok { color: #4ec9b0; }
.err { color: #f48771; }
.warn { color: #ce9178; }
pre { background: #2d2d2d; padding: 10px; overflow-x: auto; white-space: pre-wrap; }
.step { margin: 15px 0; padding: 10px; border-left: 3px solid #569cd6; }
h2 { color: #569cd6; }
table { background: #2d2d2d; border-collapse: collapse; }
td, th { padding: 8px; border: 1px solid #444; }
</style></head><body>";

echo "<h1>🔍 DEBUG KONKRÉTNÍHO PRODUKTU</h1>";

require_once __DIR__ . '/../../bootstrap.php';

use App\Modules\Products\Services\FlexibleXmlParser;

$feedId = 7;
$userId = 1;

// ID produktu k debugování
$productCode = $_GET['code'] ?? '20971'; // Můžeš změnit

echo "<div class='step'><h2>Hledám produkt s kódem začínajícím: {$productCode}</h2>";

// Načti feed
$feedModel = new \App\Modules\FeedSources\Models\FeedSource();
$feed = $feedModel->findById($feedId, $userId);

if (!$feed) {
    echo "<span class='err'>❌ Feed nenalezen!</span>";
    exit;
}

echo "URL: {$feed['url']}<br>";
echo "</div>";

// ============================================================
echo "<div class='step'><h2>Stahování XML a hledání produktu</h2>";

$ch = curl_init($feed['url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$xmlContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "<span class='err'>❌ HTTP Error: {$httpCode}</span>";
    exit;
}

echo "<span class='ok'>✅ XML staženo: " . strlen($xmlContent) . " bytes</span><br>";

// Parsuj celý XML
$xml = simplexml_load_string($xmlContent);

if (!$xml) {
    echo "<span class='err'>❌ Nelze parsovat XML!</span>";
    exit;
}

echo "Celkem SHOPITEM: " . count($xml->SHOPITEM) . "<br>";

// Najdi produkt podle kódu v názvu nebo ID
$found = null;
$foundIndex = null;

foreach ($xml->SHOPITEM as $index => $item) {
    $itemId = (string) $item['id'];
    $hasVariants = isset($item->VARIANTS->VARIANT) && count($item->VARIANTS->VARIANT) > 0;
    
    // Kontroluj ID nebo kód první varianty
    if (strpos($itemId, $productCode) === 0) {
        $found = $item;
        $foundIndex = $index;
        break;
    }
    
    if ($hasVariants) {
        $firstVariantCode = (string) $item->VARIANTS->VARIANT[0]->CODE;
        if (strpos($firstVariantCode, $productCode) === 0) {
            $found = $item;
            $foundIndex = $index;
            break;
        }
    }
}

if (!$found) {
    echo "<span class='err'>❌ Produkt s kódem {$productCode} nenalezen!</span><br>";
    echo "Zkus jiný kód pomocí ?code=XXXXX";
    exit;
}

echo "<span class='ok'>✅ Nalezen produkt na indexu #{$foundIndex}</span><br>";
echo "SHOPITEM id: " . $found['id'] . "<br>";
echo "</div>";

// ============================================================
echo "<div class='step'><h2>RAW XML produktu</h2>";
echo "<pre>";
echo htmlspecialchars($found->asXML());
echo "</pre>";
echo "</div>";

// ============================================================
echo "<div class='step'><h2>Parsování pomocí FlexibleXmlParser</h2>";

$parser = new FlexibleXmlParser();
$parsed = $parser->parseProduct($found, $userId);

if (!$parsed) {
    echo "<span class='err'>❌ Parser vrátil NULL!</span>";
    exit;
}

echo "<span class='ok'>✅ Parser vrátil data</span><br><br>";

echo "<strong>PRODUKT DATA:</strong><pre>";
print_r($parsed);
echo "</pre>";

echo "<h3>📊 ANALÝZA:</h3>";
echo "<table>";
echo "<tr><th>Pole</th><th>Hodnota</th><th>Status</th></tr>";

$fields = ['external_id', 'name', 'code', 'price_vat', 'category', 'url', 'image_url'];
foreach ($fields as $field) {
    $value = $parsed[$field] ?? '';
    $status = empty($value) ? "<span class='err'>❌ PRÁZDNÉ</span>" : "<span class='ok'>✅</span>";
    $displayValue = empty($value) ? '-' : substr($value, 0, 80);
    
    echo "<tr>";
    echo "<td><strong>{$field}</strong></td>";
    echo "<td>" . htmlspecialchars($displayValue) . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>🔢 VARIANTY: " . count($parsed['variants'] ?? []) . "</h3>";

if (!empty($parsed['variants'])) {
    echo "<table>";
    echo "<tr><th>#</th><th>Název</th><th>CODE</th><th>Cena</th></tr>";
    
    foreach ($parsed['variants'] as $i => $v) {
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . htmlspecialchars($v['name'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($v['code'] ?? '-') . "</td>";
        echo "<td>" . ($v['price'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p><span class='warn'>⚠️ Žádné varianty</span></p>";
}

echo "</div>";

// ============================================================
echo "<div class='step'><h2>Test uložení do DB</h2>";

$productModel = new \App\Modules\Products\Models\Product();

try {
    $result = $productModel->batchUpsert([$parsed]);
    
    echo "<span class='ok'>✅ batchUpsert proběhl</span><br>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['inserted'] > 0 || $result['updated'] > 0) {
        echo "<br><span class='ok'>✅✅✅ PRODUKT ULOŽEN!</span><br>";
        
        // Najdi v DB
        $db = App\Core\Database::getInstance();
        
        $saved = $db->fetchOne(
            "SELECT * FROM products WHERE user_id = ? AND external_id = ? LIMIT 1",
            [$userId, $parsed['external_id']]
        );
        
        if ($saved) {
            echo "<br><strong>Uložený produkt v DB:</strong><pre>";
            print_r($saved);
            echo "</pre>";
            
            // Najdi varianty
            $variants = $db->fetchAll(
                "SELECT * FROM product_variants WHERE product_id = ?",
                [$saved['id']]
            );
            
            echo "<br><strong>Uložené varianty ({count($variants)}):</strong><pre>";
            print_r($variants);
            echo "</pre>";
        }
    }
    
} catch (\Exception $e) {
    echo "<span class='err'>❌ Chyba: " . $e->getMessage() . "</span><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";

echo "</body></html>";
