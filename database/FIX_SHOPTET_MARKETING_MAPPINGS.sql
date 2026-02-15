-- =====================================================
-- KOMPLETNÍ FIX pro Shoptet Marketing Feed
-- =====================================================

-- 1. Přidat chybějící sloupec URL
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS `url` VARCHAR(500) NULL 
COMMENT 'URL produktu' 
AFTER `description`;

-- 2. Oprav mappingy podle skutečné struktury Shoptet Marketing feedu
UPDATE field_mappings SET xml_path = 'CODE' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'code';
UPDATE field_mappings SET xml_path = 'NAME' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'name';
UPDATE field_mappings SET xml_path = 'PRICE' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'price_vat';
UPDATE field_mappings SET xml_path = 'CATEGORY' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'category';
UPDATE field_mappings SET xml_path = 'MANUFACTURER' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'manufacturer';
UPDATE field_mappings SET xml_path = 'URL' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'url';
UPDATE field_mappings SET xml_path = 'IMAGE' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'image_url';
UPDATE field_mappings SET xml_path = 'DESCRIPTION' WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'description';
UPDATE field_mappings SET xml_path = 'EAN', is_required = 0 WHERE user_id = 1 AND feed_source_id = 7 AND db_column = 'ean';

-- 3. Kontrola mappingů
SELECT db_column, xml_path, is_required, is_default 
FROM field_mappings 
WHERE user_id = 1 AND feed_source_id = 7
ORDER BY is_required DESC, db_column;

-- Mělo by zobrazit:
-- code        | @id          | 1 | 1  (ID z atributu <SHOPITEM id="20929">)
-- name        | NAME         | 1 | 1
-- price_vat   | PRICE        | 1 | 1
-- category    | CATEGORY     | 0 | 1
-- url         | URL          | 0 | 1
-- image_url   | IMAGE        | 0 | 1
-- ...
