-- =====================================================
-- FINÁLNÍ OPRAVA - Spusť celý tento soubor!
-- =====================================================

-- 1. PŘIDAT price_vat do products
ALTER TABLE products 
ADD COLUMN `price_vat` DECIMAL(10,2) DEFAULT 0 AFTER `price`;

-- 2. VYTVOŘ mappingy pro existující feedy
-- Najdi všechny produktové feedy bez mappingů
INSERT INTO field_mappings 
    (user_id, feed_source_id, db_column, xml_path, data_type, field_type, target_type, transformer, is_active, is_required)
SELECT 
    fs.user_id,
    fs.id as feed_source_id,
    mapping.db_column,
    mapping.xml_path,
    mapping.data_type,
    'product' as field_type,
    'column' as target_type,
    mapping.transformer,
    1 as is_active,
    mapping.is_required
FROM feed_sources fs
CROSS JOIN (
    SELECT 'name' as db_column, 'NAME' as xml_path, 'string' as data_type, NULL as transformer, 1 as is_required UNION ALL
    SELECT 'code', 'CODE', 'string', NULL, 1 UNION ALL
    SELECT 'price_vat', 'PRICE_VAT', 'float', NULL, 1 UNION ALL
    SELECT 'category', 'CATEGORY', 'string', NULL, 0 UNION ALL
    SELECT 'manufacturer', 'MANUFACTURER', 'string', NULL, 0 UNION ALL
    SELECT 'url', 'ORIG_URL', 'string', NULL, 0 UNION ALL
    SELECT 'image_url', 'IMAGE', 'string', NULL, 0 UNION ALL
    SELECT 'description', 'DESCRIPTION', 'string', 'strip_tags', 0 UNION ALL
    SELECT 'ean', 'EAN', 'string', NULL, 0
) as mapping
WHERE fs.feed_type IN ('shoptet_products', 'shoptet', 'xml', 'products_xml')
AND NOT EXISTS (
    SELECT 1 FROM field_mappings fm 
    WHERE fm.feed_source_id = fs.id 
    AND fm.db_column = mapping.db_column
)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- 3. KONTROLA
SELECT 
    fs.id as feed_id,
    fs.name as feed_name,
    COUNT(fm.id) as mappings_count
FROM feed_sources fs
LEFT JOIN field_mappings fm ON fs.id = fm.feed_source_id
WHERE fs.feed_type IN ('shoptet_products', 'shoptet', 'xml', 'products_xml')
GROUP BY fs.id
ORDER BY fs.id;

-- 4. ZOBRAZ všechny mappingy pro feed #4
SELECT * FROM field_mappings WHERE feed_source_id = 4;

-- Mělo by zobrazit 9 mappingů!
