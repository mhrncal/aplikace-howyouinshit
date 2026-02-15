<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 SUPER DEBUG - Mappingy + Struktura</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}.ok{color:#4ec9b0;}.err{color:#f48771;}.warn{color:#ce9178;}pre{background:#2d2d2d;padding:10px;}</style>";

require_once __DIR__ . '/../../bootstrap.php';

use App\Modules\Products\Models\FieldMapping;
use App\Core\Database;

$db = Database::getInstance();
$mappingModel = new FieldMapping();
$userId = 1;
$feedId = 4; // Tvůj nový feed

echo "<h2>1️⃣ KONTROLA PRODUCTS TABULKY</h2>";

try {
    $columns = $db->fetchAll("DESCRIBE products");
    
    echo "<table border='1' cellpadding='5' style='background:#2d2d2d;'>";
    echo "<tr><th>Sloupec</th><th>Typ</th><th>Null</th><th>Default</th></tr>";
    
    $requiredColumns = ['name', 'code', 'price_vat', 'price', 'ean', 'custom_data', 'raw_xml'];
    $foundColumns = [];
    
    foreach ($columns as $col) {
        $foundColumns[] = $col['Field'];
        $isRequired = in_array($col['Field'], $requiredColumns);
        $color = $isRequired ? 'color:#4ec9b0;' : '';
        echo "<tr style='{$color}'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>Kontrola povinných sloupců:</h3>";
    foreach ($requiredColumns as $req) {
        if (in_array($req, $foundColumns)) {
            echo "<span class='ok'>✅ {$req}</span><br>";
        } else {
            echo "<span class='err'>❌ {$req} - CHYBÍ!</span><br>";
        }
    }
    
} catch (\Exception $e) {
    echo "<span class='err'>❌ Chyba: " . $e->getMessage() . "</span>";
}

echo "<hr><h2>2️⃣ KONTROLA FIELD_MAPPINGS TABULKY</h2>";

try {
    $columns = $db->fetchAll("DESCRIBE field_mappings");
    
    echo "<table border='1' cellpadding='5' style='background:#2d2d2d;'>";
    echo "<tr><th>Sloupec</th><th>Typ</th></tr>";
    
    $requiredColumns = ['target_type', 'transformer', 'is_searchable', 'json_path'];
    $foundColumns = [];
    
    foreach ($columns as $col) {
        $foundColumns[] = $col['Field'];
        $isRequired = in_array($col['Field'], $requiredColumns);
        $color = $isRequired ? 'color:#4ec9b0;' : '';
        echo "<tr style='{$color}'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>Kontrola nových sloupců:</h3>";
    foreach ($requiredColumns as $req) {
        if (in_array($req, $foundColumns)) {
            echo "<span class='ok'>✅ {$req}</span><br>";
        } else {
            echo "<span class='err'>❌ {$req} - CHYBÍ!</span><br>";
        }
    }
    
} catch (\Exception $e) {
    echo "<span class='err'>❌ Chyba: " . $e->getMessage() . "</span>";
}

echo "<hr><h2>3️⃣ MAPPINGY PRO FEED #{$feedId}</h2>";

try {
    $mappings = $mappingModel->getAllForUser($userId, $feedId, 'product');
    
    if (empty($mappings)) {
        echo "<span class='err'>❌ ŽÁDNÉ MAPPINGY NENALEZENY!</span><br>";
        echo "<p>Zkus:</p>";
        echo "<ol>";
        echo "<li>Smaž feed a vytvoř znovu</li>";
        echo "<li>Nebo spusť setup-default-mappings.php</li>";
        echo "</ol>";
    } else {
        echo "<span class='ok'>✅ Nalezeno " . count($mappings) . " mappingů</span><br><br>";
        
        echo "<table border='1' cellpadding='5' style='background:#2d2d2d;'>";
        echo "<tr><th>DB Sloupec</th><th>XML Path</th><th>Type</th><th>Target</th><th>Transformer</th><th>Active</th></tr>";
        
        foreach ($mappings as $m) {
            echo "<tr>";
            echo "<td><strong>{$m['db_column']}</strong></td>";
            echo "<td>{$m['xml_path']}</td>";
            echo "<td>{$m['data_type']}</td>";
            echo "<td>" . ($m['target_type'] ?? '<span class="err">NULL</span>') . "</td>";
            echo "<td>" . ($m['transformer'] ?? '-') . "</td>";
            echo "<td>" . ($m['is_active'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (\Exception $e) {
    echo "<span class='err'>❌ Chyba: " . $e->getMessage() . "</span>";
}

echo "<hr><h2>4️⃣ TEST FLEXIBLE PARSER</h2>";

try {
    $testXml = <<<'XML'
<SHOPITEM>
    <NAME>Test Product</NAME>
    <CODE>TEST123</CODE>
    <PRICE_VAT>100</PRICE_VAT>
    <MANUFACTURER>Test</MANUFACTURER>
    <CATEGORY>Test Category</CATEGORY>
    <ORIG_URL>http://test.cz</ORIG_URL>
    <IMAGES><IMAGE>http://test.cz/img.jpg</IMAGE></IMAGES>
    <DESCRIPTION>Test description</DESCRIPTION>
    <EAN>1234567890</EAN>
</SHOPITEM>
XML;
    
    $xml = simplexml_load_string($testXml);
    
    $flexibleParser = new \App\Modules\Products\Services\FlexibleXmlParser();
    $product = $flexibleParser->parseProduct($xml, $userId);
    
    echo "<h3>Výsledek parsingu:</h3>";
    echo "<pre>";
    print_r($product);
    echo "</pre>";
    
    if ($product) {
        echo "<span class='ok'>✅ Parser funguje!</span><br>";
        
        echo "<h3>Kontrola sloupců:</h3>";
        foreach (['name', 'code', 'price_vat'] as $req) {
            if (isset($product[$req])) {
                echo "<span class='ok'>✅ {$req}: {$product[$req]}</span><br>";
            } else {
                echo "<span class='err'>❌ {$req} - CHYBÍ V PARSERU!</span><br>";
            }
        }
    } else {
        echo "<span class='err'>❌ Parser vrátil NULL!</span>";
    }
    
} catch (\Exception $e) {
    echo "<span class='err'>❌ Chyba parseru: " . $e->getMessage() . "</span>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><h2>5️⃣ SQL PŘÍKAZY K OPRAVĚ</h2>";

echo "<h3>Pokud chybí sloupce v products:</h3>";
echo "<pre>ALTER TABLE products 
ADD COLUMN IF NOT EXISTS price_vat DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS ean VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS custom_data JSON NULL,
ADD COLUMN IF NOT EXISTS raw_xml MEDIUMTEXT NULL;</pre>";

echo "<h3>Pokud chybí sloupce v field_mappings:</h3>";
echo "<pre>ALTER TABLE field_mappings 
ADD COLUMN IF NOT EXISTS target_type ENUM('column','json') DEFAULT 'column',
ADD COLUMN IF NOT EXISTS transformer VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS is_searchable BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS json_path VARCHAR(255) NULL;</pre>";

echo "<h3>Pokud chybí mappingy:</h3>";
echo "<p>Smaž feed a vytvoř znovu, nebo navštiv:</p>";
echo "<a href='/app/products/setup-default-mappings.php' style='color:#4ec9b0;'>Setup Default Mappings</a>";
