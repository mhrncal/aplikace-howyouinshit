<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
.ok { color: #4ec9b0; }
.err { color: #f48771; }
.warn { color: #ce9178; }
pre { background: #2d2d2d; padding: 10px; overflow-x: auto; }
.step { margin: 15px 0; padding: 10px; border-left: 3px solid #569cd6; }
h2 { color: #569cd6; }
</style></head><body>";

echo "<h1>🔍 KOMPLETNÍ DEBUG IMPORT FLOW</h1>";

require_once __DIR__ . '/../../bootstrap.php';

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Models\FieldMapping;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\FlexibleXmlParser;

$userId = 1;

// Automaticky najdi feed
$db = App\Core\Database::getInstance();
$feeds = $db->fetchAll("SELECT id, name, feed_type FROM feed_sources WHERE user_id = ?", [$userId]);

if (empty($feeds)) {
    echo "<span class='err'>❌ Žádné feedy nenalezeny!</span><br>";
    echo "Vytvoř nový feed na /app/feed-sources/create.php";
    exit;
}

echo "<h3>Dostupné feedy:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Název</th><th>Typ</th><th>Akce</th></tr>";
foreach ($feeds as $f) {
    echo "<tr>";
    echo "<td>{$f['id']}</td>";
    echo "<td>{$f['name']}</td>";
    echo "<td>{$f['feed_type']}</td>";
    echo "<td><a href='?feed_id={$f['id']}'>Testovat</a></td>";
    echo "</tr>";
}
echo "</table><br>";

// Pokud není zadáno feed_id, skonči
if (!isset($_GET['feed_id'])) {
    echo "<span class='warn'>⚠️ Vyber feed kliknutím na 'Testovat'</span>";
    exit;
}

$feedId = (int) $_GET['feed_id'];

echo "<div class='step'><h2>KROK 1: Načtení feedu #{$feedId}</h2>";

$feedModel = new FeedSource();
$feed = $feedModel->findById($feedId, $userId);

if (!$feed) {
    echo "<span class='err'>❌ Feed nenalezen!</span>";
    exit;
}

echo "<span class='ok'>✅ Feed načten: {$feed['name']}</span><br>";
echo "URL: {$feed['url']}<br>";
echo "Type: {$feed['feed_type']}<br>";
echo "</div>";

// ============================================================
echo "<div class='step'><h2>KROK 2: Kontrola mappingů</h2>";

$mappingModel = new FieldMapping();
$mappings = $mappingModel->getAllForUser($userId, $feedId, 'product');

echo "Nalezeno mappingů: <strong>" . count($mappings) . "</strong><br><br>";

if (empty($mappings)) {
    echo "<span class='err'>❌ ŽÁDNÉ MAPPINGY! To je problém!</span><br>";
} else {
    echo "<table border='1' cellpadding='5' style='background:#2d2d2d;'>";
    echo "<tr><th>DB Column</th><th>XML Path</th><th>Type</th><th>Target</th><th>Active</th><th>Default</th></tr>";
    foreach ($mappings as $m) {
        $active = $m['is_active'] ? '✅' : '❌';
        $default = $m['is_default'] ? '🔒' : '';
        echo "<tr>";
        echo "<td>{$m['db_column']}</td>";
        echo "<td>{$m['xml_path']}</td>";
        echo "<td>{$m['data_type']}</td>";
        echo "<td>{$m['target_type']}</td>";
        echo "<td>{$active}</td>";
        echo "<td>{$default}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</div>";

// ============================================================
echo "<div class='step'><h2>KROK 3: Stažení XML (prvních 100KB)</h2>";

$url = $feed['url'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RANGE, '0-102400'); // První 100KB

$xmlSample = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 206) {
    echo "<span class='err'>❌ HTTP Error: {$httpCode}</span>";
    exit;
}

echo "<span class='ok'>✅ XML staženo (sample): " . strlen($xmlSample) . " bytes</span><br>";
echo "HTTP Code: {$httpCode}<br>";

// Zobraz prvních 500 znaků
echo "<br><strong>Prvních 500 znaků XML:</strong><pre>";
echo htmlspecialchars(substr($xmlSample, 0, 500));
echo "</pre>";

echo "</div>";

// ============================================================
echo "<div class='step'><h2>KROK 4: Test parsování prvního SHOPITEM</h2>";

// Najdi první SHOPITEM - DOTALL flag pro víceřádkový XML
if (preg_match('/<SHOPITEM[^>]*>(.*?)<\/SHOPITEM>/s', $xmlSample, $matches)) {
    $shopitemXml = '<SHOPITEM>' . $matches[1] . '</SHOPITEM>';
    echo "<span class='ok'>✅ Nalezen SHOPITEM</span><br><br>";
    
    echo "<strong>SHOPITEM XML (prvních 1000 znaků):</strong><pre>";
    echo htmlspecialchars(substr($shopitemXml, 0, 1000));
    echo "\n...\n</pre>";
    
    // Parsuj pomocí FlexibleXmlParser
    try {
        $xml = simplexml_load_string($shopitemXml);
        
        if (!$xml) {
            echo "<span class='err'>❌ Nelze parsovat XML!</span>";
            echo "<br>Chyba: " . print_r(libxml_get_errors(), true);
        } else {
            $parser = new FlexibleXmlParser();
            $product = $parser->parseProduct($xml, $userId);
            
            if (!$product) {
                echo "<span class='err'>❌ Parser vrátil NULL!</span>";
            } else {
                echo "<span class='ok'>✅ Parser vrátil produkt:</span><br>";
                echo "<pre>";
                print_r($product);
                echo "</pre>";
            }
        }
    } catch (\Exception $e) {
        echo "<span class='err'>❌ Parser error: " . $e->getMessage() . "</span><br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<span class='err'>❌ Nenalezen žádný SHOPITEM!</span><br>";
    echo "XML struktura je jiná než očekáváno.<br>";
    echo "Hledám pattern: &lt;SHOPITEM...&gt;...&lt;/SHOPITEM&gt;";
}

echo "</div>";

// ============================================================
echo "<div class='step'><h2>KROK 5: Test uložení do DB</h2>";

if (isset($product) && $product) {
    $productModel = new Product();
    
    echo "Pokusím se uložit produkt do DB...<br>";
    
    try {
        $result = $productModel->batchUpsert([$product]);
        
        echo "<span class='ok'>✅ batchUpsert proběhl</span><br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['inserted'] > 0 || $result['updated'] > 0) {
            echo "<span class='ok'>✅✅✅ PRODUKT ULOŽEN DO DB!</span><br>";
            
            // Zkontroluj v DB
            $db = App\Core\Database::getInstance();
            $saved = $db->fetchOne("SELECT * FROM products WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$userId]);
            
            if ($saved) {
                echo "<br><strong>Uložený produkt v DB:</strong><pre>";
                print_r($saved);
                echo "</pre>";
            }
        } else {
            echo "<span class='err'>❌ Produkt se NEULOŽIL!</span><br>";
            echo "inserted: {$result['inserted']}, updated: {$result['updated']}";
        }
        
    } catch (\Exception $e) {
        echo "<span class='err'>❌ Chyba při ukládání: " . $e->getMessage() . "</span><br>";
        echo "File: " . $e->getFile() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
}

echo "</div>";

// ============================================================
echo "<div class='step'><h2>SHRNUTÍ</h2>";

echo "<ol>";
echo "<li>Feed: " . ($feed ? '✅' : '❌') . "</li>";
echo "<li>Mappingy: " . (count($mappings) > 0 ? '✅ ' . count($mappings) : '❌ 0') . "</li>";
echo "<li>XML staženo: " . ($httpCode === 200 || $httpCode === 206 ? '✅' : '❌') . "</li>";
echo "<li>SHOPITEM nalezen: " . (isset($shopitemXml) ? '✅' : '❌') . "</li>";
echo "<li>Parser OK: " . (isset($product) && $product ? '✅' : '❌') . "</li>";
echo "<li>Uloženo do DB: " . (isset($result) && ($result['inserted'] > 0 || $result['updated'] > 0) ? '✅' : '❌') . "</li>";
echo "</ol>";

echo "</div>";

echo "</body></html>";
