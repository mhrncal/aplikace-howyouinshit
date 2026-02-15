-- =====================================================
-- KOMPLETNÍ FIX - Všechny chybějící sloupce
-- =====================================================

-- 1. PRODUCTS - přidat price_vat
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS `price_vat` DECIMAL(10,2) DEFAULT 0 COMMENT 'Cena s DPH' AFTER `price`;

-- 2. FEED_SOURCES - přidat last_imported_at
ALTER TABLE feed_sources
ADD COLUMN IF NOT EXISTS `last_imported_at` TIMESTAMP NULL COMMENT 'Poslední import' AFTER `last_import_at`;

-- 3. Opravit feed_type u existujících feedů
UPDATE feed_sources 
SET feed_type = 'shoptet_products' 
WHERE feed_type IN ('xml', 'shoptet', 'products_xml')
AND type = 'products_xml';

-- 4. Kontrola
SELECT id, name, feed_type, last_import_at, last_imported_at FROM feed_sources;

DESCRIBE products;
DESCRIBE feed_sources;

SELECT 'SQL HOTOVO - Všechny sloupce přidány!' as status;
