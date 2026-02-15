<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\Products\Models\FieldMapping;

$mappingModel = new FieldMapping();
$userId = $auth->userId();

// Vytvoř výchozí mappingy pokud ještě neexistují
$existing = $mappingModel->getAllForUser($userId, null, 'product');

if (empty($existing)) {
    echo "<h2>Vytváření výchozích mappingů...</h2>";
    
    $defaultMappings = [
        // POVINNÁ POLE - column
        ['db_column' => 'name', 'xml_path' => 'NAME', 'data_type' => 'string', 'target_type' => 'column', 'is_required' => 1],
        ['db_column' => 'code', 'xml_path' => 'CODE', 'data_type' => 'string', 'target_type' => 'column', 'is_required' => 1],
        ['db_column' => 'price_vat', 'xml_path' => 'PRICE_VAT', 'data_type' => 'float', 'target_type' => 'column', 'is_required' => 1],
        
        // ČASTO POUŽÍVANÁ - column
        ['db_column' => 'category', 'xml_path' => 'CATEGORY', 'data_type' => 'string', 'target_type' => 'column'],
        ['db_column' => 'manufacturer', 'xml_path' => 'MANUFACTURER', 'data_type' => 'string', 'target_type' => 'column'],
        ['db_column' => 'url', 'xml_path' => 'ORIG_URL', 'data_type' => 'string', 'target_type' => 'column'],
        ['db_column' => 'image_url', 'xml_path' => 'IMAGE', 'data_type' => 'string', 'target_type' => 'column'],
        ['db_column' => 'description', 'xml_path' => 'DESCRIPTION', 'data_type' => 'string', 'target_type' => 'column', 'transformer' => 'strip_tags'],
        ['db_column' => 'ean', 'xml_path' => 'EAN', 'data_type' => 'string', 'target_type' => 'column'],
        
        // CUSTOM POLE - json (příklady)
        ['db_column' => 'weight', 'xml_path' => 'WEIGHT', 'data_type' => 'float', 'target_type' => 'json'],
        ['db_column' => 'stock_amount', 'xml_path' => 'STOCK_AMOUNT', 'data_type' => 'int', 'target_type' => 'json'],
    ];
    
    $created = 0;
    foreach ($defaultMappings as $mapping) {
        $data = array_merge($mapping, [
            'user_id' => $userId,
            'field_type' => 'product',
            'is_active' => 1,
        ]);
        
        try {
            if ($mappingModel->create($userId, $data)) {
                $created++;
                echo "✅ Vytvořeno: {$mapping['db_column']} → {$mapping['xml_path']}<br>";
            }
        } catch (\Exception $e) {
            echo "❌ Chyba: {$mapping['db_column']} - {$e->getMessage()}<br>";
        }
    }
    
    echo "<br><h3>✅ Vytvořeno {$created} výchozích mappingů!</h3>";
    echo "<br><a href='/app/products/field-mapping.php' class='btn btn-primary'>Zobrazit mappingy</a>";
    
} else {
    echo "<h2>✅ Mappingy již existují</h2>";
    echo "<p>Nalezeno " . count($existing) . " mappingů.</p>";
    echo "<br><a href='/app/products/field-mapping.php' class='btn btn-primary'>Zobrazit mappingy</a>";
}
