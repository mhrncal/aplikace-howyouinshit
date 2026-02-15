-- =====================================================
-- OPRAVA FEED_SOURCES - Přidání chybějících sloupců
-- =====================================================

-- Spusť tento celý skript v phpMyAdmin

-- Přidat všechny potřebné sloupce najednou
ALTER TABLE `feed_sources` 
ADD COLUMN `description` TEXT NULL AFTER `name`,
ADD COLUMN `feed_type` VARCHAR(50) DEFAULT 'xml' AFTER `description`,
ADD COLUMN `last_import_at` TIMESTAMP NULL,
ADD COLUMN `total_imports` INT DEFAULT 0,
ADD COLUMN `failed_imports` INT DEFAULT 0,
ADD COLUMN `last_import_records` INT NULL,
ADD COLUMN `last_import_duration` INT NULL;

-- Kontrola - mělo by zobrazit všechny nové sloupce
DESCRIBE feed_sources;

-- Zobrazit feedy
SELECT id, name, description, feed_type, last_import_at FROM feed_sources;
