-- =====================================================
-- KOMPLETNÍ SETUP - external_id + Shoptet mappingy
-- =====================================================

-- 1. Přidat external_id pro párování
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS `external_id` VARCHAR(100) NULL 
COMMENT 'ID z feedu (SHOPITEM id="20929")' 
AFTER `id`,
ADD INDEX IF NOT EXISTS `idx_user_external` (`user_id`, `external_id`);

-- 2. Přidat url pokud chybí
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS `url` VARCHAR(500) NULL 
COMMENT 'URL produktu' 
AFTER `description`;

-- 3. Vytvoř/aktualizuj mappingy pro feed #7
DELETE FROM field_mappings WHERE user_id = 1 AND feed_source_id = 7;

INSERT INTO field_mappings 
(user_id, feed_source_id, db_column, xml_path, data_type, field_type, target_type, transformer, is_active, is_required, is_default, created_at)
VALUES
-- external_id pro párování
(1, 7, 'external_id', 'EXTERNAL_ID', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),

-- POVINNÁ
(1, 7, 'name', 'NAME', 'string', 'product', 'column', NULL, 1, 1, 1, NOW()),
(1, 7, 'code', 'CODE', 'string', 'product', 'column', NULL, 1, 1, 1, NOW()),
(1, 7, 'price_vat', 'PRICE', 'float', 'product', 'column', NULL, 1, 1, 1, NOW()),

-- ČASTO POUŽÍVANÁ
(1, 7, 'category', 'CATEGORY', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 7, 'manufacturer', 'MANUFACTURER', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 7, 'url', 'URL', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 7, 'image_url', 'IMAGE', 'string', 'product', 'column', NULL, 1, 0, 1, NOW()),
(1, 7, 'description', 'DESCRIPTION', 'string', 'product', 'column', 'strip_tags', 1, 0, 1, NOW()),
(1, 7, 'ean', 'EAN', 'string', 'product', 'column', NULL, 1, 0, 1, NOW());

-- 4. Kontrola
SELECT COUNT(*) as total_mappings FROM field_mappings WHERE user_id = 1 AND feed_source_id = 7;

SELECT db_column, xml_path, is_required 
FROM field_mappings 
WHERE user_id = 1 AND feed_source_id = 7
ORDER BY is_required DESC, db_column;

-- Mělo by zobrazit 10 mappingů
