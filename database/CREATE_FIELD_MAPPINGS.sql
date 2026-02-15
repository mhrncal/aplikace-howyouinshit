-- =====================================================
-- FIELD MAPPINGS - Správa mapování polí z adminu
-- =====================================================

CREATE TABLE IF NOT EXISTS `field_mappings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `feed_source_id` BIGINT UNSIGNED NULL COMMENT 'Null = globální pro všechny feedy',
    
    -- Mapování
    `db_column` VARCHAR(100) NOT NULL COMMENT 'Název sloupce v products',
    `xml_path` VARCHAR(255) NOT NULL COMMENT 'XPath k elementu (např. WARRANTY)',
    `xml_path_alt` VARCHAR(255) NULL COMMENT 'Alternativní cesta',
    `xml_path_alt2` VARCHAR(255) NULL COMMENT 'Další alternativa',
    
    -- Transformace
    `transform_type` ENUM('none','floatval','intval','strip_tags','boolean','custom') DEFAULT 'none',
    `transform_custom` TEXT NULL COMMENT 'Vlastní PHP kód pro transformaci',
    
    -- Nastavení
    `default_value` VARCHAR(255) NULL,
    `is_required` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Typ pole (produkt nebo varianta)
    `field_type` ENUM('product','variant') DEFAULT 'product',
    
    -- Metadata
    `description` TEXT NULL COMMENT 'Popis pro admina',
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_user_active` (`user_id`, `is_active`),
    INDEX `idx_feed_type` (`feed_source_id`, `field_type`),
    UNIQUE KEY `unique_mapping` (`user_id`, `feed_source_id`, `db_column`, `field_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vložit defaultní mapování pro Shoptet
INSERT INTO `field_mappings` (`user_id`, `feed_source_id`, `db_column`, `xml_path`, `xml_path_alt`, `transform_type`, `default_value`, `is_required`, `field_type`, `description`) VALUES
(1, NULL, 'name', 'NAME', 'PRODUCT', 'none', '', 1, 'product', 'Název produktu'),
(1, NULL, 'code', 'CODE', NULL, 'none', '', 0, 'product', 'Kód produktu'),
(1, NULL, 'ean', 'EAN', NULL, 'none', '', 0, 'product', 'EAN kód'),
(1, NULL, 'manufacturer', 'MANUFACTURER', NULL, 'none', '', 0, 'product', 'Výrobce'),
(1, NULL, 'category', 'CATEGORIES/DEFAULT_CATEGORY', 'CATEGORIES/CATEGORY[0]', 'none', '', 0, 'product', 'Kategorie'),
(1, NULL, 'description', 'DESCRIPTION', 'SHORT_DESCRIPTION', 'strip_tags', '', 0, 'product', 'Popis produktu'),
(1, NULL, 'price', 'PRICE_VAT', NULL, 'floatval', '0', 0, 'product', 'Cena s DPH'),
(1, NULL, 'price_vat', 'PRICE_VAT', NULL, 'floatval', '0', 0, 'product', 'Cena s DPH'),
(1, NULL, 'url', 'ORIG_URL', 'URL', 'none', '', 0, 'product', 'URL produktu'),
(1, NULL, 'image_url', 'IMAGES/IMAGE[0]', 'IMGURL', 'none', '', 0, 'product', 'Hlavní obrázek'),
(1, NULL, 'availability', 'STOCK/AMOUNT', NULL, 'custom', 'Skladem', 0, 'product', 'Dostupnost')
ON DUPLICATE KEY UPDATE xml_path=VALUES(xml_path);

-- Kontrola
SELECT * FROM field_mappings;
