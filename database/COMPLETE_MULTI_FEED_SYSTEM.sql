-- =====================================================
-- KOMPLETNÍ DATABÁZOVÁ STRUKTURA - Multi-Feed System
-- =====================================================
-- Spusť tento celý soubor postupně (po sekcích)

-- =====================================================
-- SEKCE 1: FEED SOURCES (již existuje, jen kontrola)
-- =====================================================

CREATE TABLE IF NOT EXISTS `feed_sources` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `feed_type` VARCHAR(50) DEFAULT 'shoptet_products',
    `type` ENUM('products_xml','orders_xml','stock_xml','prices_xml') NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    
    `schedule` ENUM('hourly','daily','weekly','manual') DEFAULT 'daily',
    `schedule_time` TIME NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    
    `last_import_at` TIMESTAMP NULL,
    `next_import_at` TIMESTAMP NULL,
    `total_imports` INT DEFAULT 0,
    `failed_imports` INT DEFAULT 0,
    `last_import_records` INT NULL,
    `last_import_duration` INT NULL,
    
    `http_auth_username` VARCHAR(255) NULL,
    `http_auth_password` VARCHAR(255) NULL,
    
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_active` (`user_id`, `is_active`),
    INDEX `idx_type` (`type`),
    INDEX `idx_feed_type` (`feed_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEKCE 2: PRODUCTS (Produktový feed)
-- =====================================================

-- Přidat chybějící sloupce do products
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT 0 AFTER `manufacturer`,
ADD COLUMN IF NOT EXISTS `ean` VARCHAR(50) NULL AFTER `code`,
ADD COLUMN IF NOT EXISTS `custom_data` JSON NULL COMMENT 'Flexibilní custom pole',
ADD COLUMN IF NOT EXISTS `raw_xml` MEDIUMTEXT NULL COMMENT 'Backup celého XML',
ADD COLUMN IF NOT EXISTS `imported_at` TIMESTAMP NULL COMMENT 'Kdy byl importován',
ADD INDEX IF NOT EXISTS `idx_imported_at` (`imported_at`),
ADD INDEX IF NOT EXISTS `idx_ean` (`ean`);

-- Virtual columns pro rychlé vyhledávání (příklady)
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `weight` DECIMAL(10,2) AS (JSON_UNQUOTE(JSON_EXTRACT(custom_data, '$.weight'))) VIRTUAL,
ADD COLUMN IF NOT EXISTS `color` VARCHAR(50) AS (JSON_UNQUOTE(JSON_EXTRACT(custom_data, '$.color'))) VIRTUAL,
ADD INDEX IF NOT EXISTS `idx_weight` (`weight`),
ADD INDEX IF NOT EXISTS `idx_color` (`color`);

-- Full-text index
ALTER TABLE `products` ADD FULLTEXT INDEX IF NOT EXISTS `idx_fulltext_search` (`name`, `description`, `category`);

-- =====================================================
-- SEKCE 3: ORDERS (Objednávkový feed)
-- =====================================================

CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `feed_source_id` BIGINT UNSIGNED NULL,
    
    -- Základní info
    `order_number` VARCHAR(100) NOT NULL,
    `external_id` VARCHAR(100) NULL COMMENT 'ID z Shoptetu',
    `status` VARCHAR(50) NULL,
    `payment_status` VARCHAR(50) NULL,
    `shipping_status` VARCHAR(50) NULL,
    
    -- Zákazník
    `customer_name` VARCHAR(255) NULL,
    `customer_email` VARCHAR(255) NULL,
    `customer_phone` VARCHAR(50) NULL,
    `customer_company` VARCHAR(255) NULL,
    
    -- Dodací adresa
    `shipping_address` TEXT NULL,
    `shipping_city` VARCHAR(100) NULL,
    `shipping_zip` VARCHAR(20) NULL,
    `shipping_country` VARCHAR(100) NULL,
    
    -- Fakturační adresa
    `billing_address` TEXT NULL,
    `billing_city` VARCHAR(100) NULL,
    `billing_zip` VARCHAR(20) NULL,
    `billing_country` VARCHAR(100) NULL,
    
    -- Ceny
    `total_price` DECIMAL(10,2) DEFAULT 0,
    `total_vat` DECIMAL(10,2) DEFAULT 0,
    `shipping_price` DECIMAL(10,2) DEFAULT 0,
    `payment_price` DECIMAL(10,2) DEFAULT 0,
    `currency` VARCHAR(10) DEFAULT 'CZK',
    
    -- Datumy
    `ordered_at` TIMESTAMP NULL,
    `paid_at` TIMESTAMP NULL,
    `shipped_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,
    
    -- Flexibilní pole
    `custom_data` JSON NULL,
    `raw_xml` MEDIUMTEXT NULL,
    
    -- Metadata
    `imported_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `idx_user_order` (`user_id`, `order_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_customer_email` (`customer_email`),
    INDEX `idx_ordered_at` (`ordered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEKCE 4: ORDER ITEMS (Položky objednávek)
-- =====================================================

CREATE TABLE IF NOT EXISTS `order_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    
    `product_name` VARCHAR(255) NOT NULL,
    `product_code` VARCHAR(100) NULL,
    `product_ean` VARCHAR(50) NULL,
    
    `quantity` INT DEFAULT 1,
    `unit_price` DECIMAL(10,2) DEFAULT 0,
    `vat_rate` DECIMAL(5,2) DEFAULT 21,
    `total_price` DECIMAL(10,2) DEFAULT 0,
    
    `variant_name` VARCHAR(255) NULL,
    
    `custom_data` JSON NULL,
    
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEKCE 5: FIELD MAPPINGS (Universal)
-- =====================================================

CREATE TABLE IF NOT EXISTS `field_mappings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `feed_source_id` BIGINT UNSIGNED NULL,
    
    `db_column` VARCHAR(100) NOT NULL,
    `xml_path` VARCHAR(255) NOT NULL,
    `data_type` ENUM('string','int','float','bool','date','json') DEFAULT 'string',
    `default_value` TEXT NULL,
    
    `field_type` ENUM('product','variant','order','order_item','stock','price') DEFAULT 'product',
    `target_type` ENUM('column', 'json') DEFAULT 'column',
    `transformer` VARCHAR(100) NULL,
    `is_searchable` BOOLEAN DEFAULT FALSE,
    `json_path` VARCHAR(255) NULL,
    
    `is_active` BOOLEAN DEFAULT TRUE,
    `is_required` BOOLEAN DEFAULT FALSE,
    
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE SET NULL,
    
    INDEX `idx_user_field` (`user_id`, `field_type`, `is_active`),
    INDEX `idx_feed_field` (`feed_source_id`, `field_type`),
    UNIQUE KEY `idx_unique_mapping` (`user_id`, `feed_source_id`, `db_column`, `field_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEKCE 6: IMPORT LOGS
-- =====================================================

CREATE TABLE IF NOT EXISTS `import_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `feed_source_id` BIGINT UNSIGNED NULL,
    `type` ENUM('products','orders','stock','prices') NOT NULL,
    `status` ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    
    `total_records` INT DEFAULT 0,
    `processed_records` INT DEFAULT 0,
    `created_records` INT DEFAULT 0,
    `updated_records` INT DEFAULT 0,
    `failed_records` INT DEFAULT 0,
    
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `duration_seconds` INT NULL,
    
    `file_size` BIGINT NULL,
    `memory_peak_mb` DECIMAL(8,2) NULL,
    
    `error_message` TEXT NULL,
    `error_trace` TEXT NULL,
    
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_type` (`user_id`, `type`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- KONTROLA STRUKTUR
-- =====================================================

SELECT '=== FEED SOURCES ===' as 'TABLE';
DESCRIBE feed_sources;

SELECT '=== PRODUCTS ===' as 'TABLE';
DESCRIBE products;

SELECT '=== ORDERS ===' as 'TABLE';
DESCRIBE orders;

SELECT '=== ORDER ITEMS ===' as 'TABLE';
DESCRIBE order_items;

SELECT '=== FIELD MAPPINGS ===' as 'TABLE';
DESCRIBE field_mappings;

SELECT '=== IMPORT LOGS ===' as 'TABLE';
DESCRIBE import_logs;

-- =====================================================
-- HOTOVO!
-- =====================================================
SELECT 'Všechny tabulky vytvořeny!' as 'STATUS';
