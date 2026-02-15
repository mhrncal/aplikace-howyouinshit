-- =====================================================
-- FEED SOURCES - KOMPLETNÍ KONTROLA A OPRAVA
-- =====================================================

-- 1. Zkontrolovat strukturu (spusť a podívej se na výsledek)
DESCRIBE feed_sources;

-- 2. Přidat chybějící sloupce (pokud neexistují)
-- Tento příkaz NESELŽE pokud sloupec už existuje (bezpečné)

-- Description a feed_type (pro UI)
ALTER TABLE `feed_sources` 
ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `name`;

ALTER TABLE `feed_sources` 
ADD COLUMN IF NOT EXISTS `feed_type` VARCHAR(50) DEFAULT 'xml' AFTER `description`;

-- Kontrola že last_imported_at existuje (mělo by být ze schema.sql)
ALTER TABLE `feed_sources`
ADD COLUMN IF NOT EXISTS `last_imported_at` TIMESTAMP NULL AFTER `is_active`;

ALTER TABLE `feed_sources`
ADD COLUMN IF NOT EXISTS `total_imports` INT DEFAULT 0 AFTER `last_imported_at`;

ALTER TABLE `feed_sources`
ADD COLUMN IF NOT EXISTS `failed_imports` INT DEFAULT 0 AFTER `total_imports`;

ALTER TABLE `feed_sources`
ADD COLUMN IF NOT EXISTS `last_import_records` INT NULL AFTER `failed_imports`;

ALTER TABLE `feed_sources`
ADD COLUMN IF NOT EXISTS `last_import_duration` INT NULL AFTER `last_import_records`;

-- 3. Kontrola finální struktury
DESCRIBE feed_sources;

-- 4. Zobrazit všechny feedy
SELECT 
    id,
    name,
    description,
    feed_type,
    type,
    url,
    is_active,
    last_imported_at,
    total_imports,
    failed_imports
FROM feed_sources
ORDER BY id;

-- 5. Test update (simulace cronu)
-- UPDATE feed_sources SET last_imported_at = NOW() WHERE id = 1;
