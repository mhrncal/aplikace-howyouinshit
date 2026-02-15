-- =====================================================
-- VYTVOŘ výchozí mappingy pro feed #5
-- =====================================================

-- Nejdřív smaž staré (pokud existují)
DELETE FROM field_mappings WHERE user_id = 1 AND feed_source_id = 5;

-- Vytvoř 9 výchozích mappingů
INSERT INTO field_mappings 
(user_id, feed_source_id, db_column, xml_path, data_type, field_type, target_type, transformer, is_active, is_required, is_default, created_at)
VALUES
-- POVINNÁ POLE
(1, 5, 'name', 'NAME', 'string', 'product', 'column', NULL, 1, 1, 1, NOW()),
(1, 5, 'code', 'CODE', 'string', 'product', 'column', NULL, 1, 1, 1, NOW()),
(1, 5, 'price_vat', 'PRICE_VAT', 'float', 'product', 'column', NULL, 1, 1, 1, NOW()),

-- ČASTO POUŽÍVANÁ
(1, 5, 'category', 'CATEGORY', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 5, 'manufacturer', 'MANUFACTURER', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 5, 'url', 'ORIG_URL', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 5, 'image_url', 'IMAGE', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 5, 'description', 'DESCRIPTION', 'string', 'product', 'column', 'strip_tags', 1, 0, 1, NOW()),
(1, 5, 'ean', 'EAN', 'string', 'product', 'column', NULL, 1, 0, 1, NOW());

-- Kontrola
SELECT 
    COUNT(*) as total_mappings,
    SUM(CASE WHEN is_default = 1 THEN 1 ELSE 0 END) as default_mappings,
    SUM(CASE WHEN is_required = 1 THEN 1 ELSE 0 END) as required_mappings
FROM field_mappings 
WHERE user_id = 1 AND feed_source_id = 5;

-- Mělo by zobrazit:
-- total: 9, default: 9, required: 3

-- Detail všech mappingů
SELECT id, db_column, xml_path, is_default, is_required 
FROM field_mappings 
WHERE user_id = 1 AND feed_source_id = 5
ORDER BY is_required DESC, db_column;
