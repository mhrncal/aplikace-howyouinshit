-- =====================================================
-- FIELD MAPPINGS - Mapování XML polí na DB sloupce
-- =====================================================

CREATE TABLE IF NOT EXISTS `field_mappings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `feed_source_id` BIGINT UNSIGNED NULL,
    
    -- Mapování
    `db_column` VARCHAR(100) NOT NULL COMMENT 'Název sloupce v DB (např. name, custom_field)',
    `xml_path` VARCHAR(255) NOT NULL COMMENT 'XPath k elementu (např. NAME, IMAGES/IMAGE)',
    `data_type` ENUM('string','int','float','bool','date','json') DEFAULT 'string',
    `default_value` TEXT NULL COMMENT 'Výchozí hodnota pokud element neexistuje',
    
    -- Kontext
    `entity_type` ENUM('product','variant') DEFAULT 'product' COMMENT 'Pro jakou entitu je mapování',
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Metadata
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE CASCADE,
    
    INDEX `idx_user_entity` (`user_id`, `entity_type`, `is_active`),
    INDEX `idx_feed_entity` (`feed_source_id`, `entity_type`),
    UNIQUE KEY `idx_unique_mapping` (`user_id`, `feed_source_id`, `db_column`, `entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Příklady mapování pro Shoptet
INSERT INTO `field_mappings` 
(`user_id`, `feed_source_id`, `db_column`, `xml_path`, `data_type`, `entity_type`, `is_active`) 
VALUES
-- PRODUKTY
(1, NULL, 'name', 'NAME', 'string', 'product', 1),
(1, NULL, 'code', 'CODE', 'string', 'product', 1),
(1, NULL, 'manufacturer', 'MANUFACTURER', 'string', 'product', 1),
(1, NULL, 'price_vat', 'PRICE_VAT', 'float', 'product', 1),
(1, NULL, 'url', 'ORIG_URL', 'string', 'product', 1),
(1, NULL, 'image_url', 'IMAGES/IMAGE', 'string', 'product', 1),
(1, NULL, 'category', 'CATEGORIES/DEFAULT_CATEGORY', 'string', 'product', 1),
(1, NULL, 'description', 'DESCRIPTION', 'string', 'product', 1),

-- VARIANTY
(1, NULL, 'code', 'CODE', 'string', 'variant', 1),
(1, NULL, 'price', 'PRICE_VAT', 'float', 'variant', 1)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Kontrola
SELECT * FROM field_mappings;
