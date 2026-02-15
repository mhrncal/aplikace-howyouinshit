-- =====================================================
-- MIGRACE NA FLEXIBILNÍ SYSTÉM S JSON
-- =====================================================

-- 1. PRODUCTS - Přidat JSON sloupce
ALTER TABLE `products`
ADD COLUMN `custom_data` JSON NULL COMMENT 'Flexibilní custom pole pro každého uživatele',
ADD COLUMN `raw_xml` MEDIUMTEXT NULL COMMENT 'Backup celého XML SHOPITEM elementu',
ADD COLUMN `imported_at` TIMESTAMP NULL COMMENT 'Kdy byl naposledy importován',
ADD INDEX `idx_imported_at` (`imported_at`);

-- 2. FIELD_MAPPINGS - Rozšířit o target_type
ALTER TABLE `field_mappings`
ADD COLUMN `target_type` ENUM('column', 'json') DEFAULT 'column' COMMENT 'Kam uložit: DB sloupec nebo JSON',
ADD COLUMN `transformer` VARCHAR(100) NULL COMMENT 'PHP funkce pro transformaci (strip_tags, strtoupper)',
ADD COLUMN `is_searchable` BOOLEAN DEFAULT FALSE COMMENT 'Vytvořit virtual column pro vyhledávání',
ADD COLUMN `json_path` VARCHAR(255) NULL COMMENT 'Cesta v JSON (např. $.weight)';

-- 3. VIRTUAL COLUMNS pro rychlé vyhledávání v JSON
-- Příklad: váha jako vyhledávatelné pole
ALTER TABLE `products`
ADD COLUMN `weight` DECIMAL(10,2) AS (JSON_UNQUOTE(JSON_EXTRACT(custom_data, '$.weight'))) VIRTUAL,
ADD INDEX `idx_weight` (`weight`);

-- Příklad: barva jako vyhledávatelné pole
ALTER TABLE `products`
ADD COLUMN `color` VARCHAR(50) AS (JSON_UNQUOTE(JSON_EXTRACT(custom_data, '$.color'))) VIRTUAL,
ADD INDEX `idx_color` (`color`);

-- 4. PRODUCT_VARIANTS - Také JSON
ALTER TABLE `product_variants`
ADD COLUMN `custom_data` JSON NULL,
ADD COLUMN `raw_xml` MEDIUMTEXT NULL;

-- 5. VYTVOŘ FULL-TEXT INDEX pro vyhledávání
ALTER TABLE `products`
ADD FULLTEXT INDEX `idx_fulltext_search` (`name`, `description`, `category`);

-- =====================================================
-- KONTROLA
-- =====================================================

-- Zobraz strukturu products
DESCRIBE products;

-- Zobraz strukturu field_mappings
DESCRIBE field_mappings;

-- Test JSON dotazu
SELECT 
    id,
    name,
    code,
    JSON_EXTRACT(custom_data, '$.weight') as weight,
    JSON_EXTRACT(custom_data, '$.color') as color
FROM products
WHERE custom_data IS NOT NULL
LIMIT 5;
