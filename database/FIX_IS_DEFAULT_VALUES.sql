-- =====================================================
-- OPRAVA: Nastavit is_default pro existující mappingy
-- =====================================================

-- 1. Nastavit is_default pro výchozí sloupce
UPDATE field_mappings 
SET is_default = 1 
WHERE db_column IN ('name', 'code', 'price_vat', 'category', 'manufacturer', 'url', 'image_url', 'description', 'ean')
AND field_type = 'product'
AND target_type = 'column';

-- 2. Kontrola - mělo by zobrazit 9 mappingů s is_default = 1
SELECT id, user_id, db_column, field_type, is_default, feed_source_id 
FROM field_mappings 
WHERE is_default = 1
ORDER BY user_id, db_column;

-- 3. Celkový přehled
SELECT 
    user_id,
    SUM(CASE WHEN is_default = 1 THEN 1 ELSE 0 END) as default_mappings,
    SUM(CASE WHEN is_default = 0 THEN 1 ELSE 0 END) as custom_mappings,
    COUNT(*) as total_mappings
FROM field_mappings
GROUP BY user_id;

-- Mělo by zobrazit:
-- user_id | default_mappings | custom_mappings | total_mappings
-- 1       | 9                | 0               | 9
