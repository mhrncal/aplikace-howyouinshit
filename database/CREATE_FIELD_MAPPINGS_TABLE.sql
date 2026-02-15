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
    `field_type` ENUM('product','variant') DEFAULT 'product' COMMENT 'Pro jakou entitu je mapování',
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_required` BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_user_field` (`user_id`, `field_type`, `is_active`),
    INDEX `idx_feed_field` (`feed_source_id`, `field_type`),
    UNIQUE KEY `idx_unique_mapping` (`user_id`, `feed_source_id`, `db_column`, `field_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kontrola
DESCRIBE field_mappings;
