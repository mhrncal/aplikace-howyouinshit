-- =====================================================
-- PŘIDAT is_default do field_mappings
-- =====================================================

-- 1. Přidat sloupec is_default
ALTER TABLE field_mappings 
ADD COLUMN `is_default` BOOLEAN DEFAULT FALSE 
COMMENT 'Výchozí mapping - nelze mazat/měnit' 
AFTER `is_required`;

-- 2. Označit existující výchozí mappingy
UPDATE field_mappings 
SET is_default = 1 
WHERE db_column IN ('name', 'code', 'price_vat', 'category', 'manufacturer', 'url', 'image_url', 'description', 'ean')
AND field_type = 'product'
AND target_type = 'column';

-- 3. Kontrola
SELECT id, db_column, field_type, is_default, is_required FROM field_mappings 
WHERE is_default = 1;

-- Mělo by zobrazit 9 výchozích mappingů
