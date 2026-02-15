-- E-shop Analytics Platform - Enhanced Database Setup
-- Version: 2.0
-- PHP 8.2+ compatible

SET NAMES utf8mb4;
SET time_zone = '+01:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ====================
-- USERS TABLE
-- ====================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `is_super_admin` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Firemní údaje
    `company_name` VARCHAR(255) NULL,
    `ico` VARCHAR(20) NULL COMMENT 'IČO',
    `dic` VARCHAR(20) NULL COMMENT 'DIČ',
    `phone` VARCHAR(50) NULL,
    `address` VARCHAR(500) NULL,
    `city` VARCHAR(100) NULL,
    `zip` VARCHAR(20) NULL,
    `country` VARCHAR(100) DEFAULT 'Česká republika',
    
    -- Metadata
    `last_login_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_email` (`email`),
    INDEX `idx_is_super_admin` (`is_super_admin`),
    INDEX `idx_is_active` (`is_active`),
    INDEX `idx_ico` (`ico`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Uživatelé systému';

-- ====================
-- PASSWORD RESET TOKENS
-- ====================
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` TIMESTAMP NOT NULL,
    `used_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tokeny pro reset hesla';

-- ====================
-- FEED SOURCES
-- ====================
DROP TABLE IF EXISTS `feed_sources`;
CREATE TABLE `feed_sources` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` ENUM('products_xml','orders_xml','products_csv','orders_csv') NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    
    -- Plánování
    `schedule` ENUM('hourly','daily','weekly','manual') DEFAULT 'daily',
    `schedule_time` TIME NULL COMMENT 'Čas pro denní import',
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Statistiky
    `last_imported_at` TIMESTAMP NULL,
    `next_import_at` TIMESTAMP NULL,
    `total_imports` INT DEFAULT 0,
    `failed_imports` INT DEFAULT 0,
    `last_import_records` INT NULL,
    `last_import_duration` INT NULL COMMENT 'Sekundy',
    
    -- HTTP Auth
    `http_auth_username` VARCHAR(255) NULL,
    `http_auth_password` VARCHAR(255) NULL,
    
    -- Metadata
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_active` (`user_id`, `is_active`),
    INDEX `idx_next_import` (`next_import_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Zdroje XML/CSV feedů';

-- ====================
-- PRODUCTS
-- ====================
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    
    -- Identifikátory
    `external_id` VARCHAR(255) NULL COMMENT 'ID z e-shopu',
    `guid` VARCHAR(255) NULL COMMENT 'Globální unikátní ID',
    `code` VARCHAR(255) NULL COMMENT 'Kód produktu',
    `ean` VARCHAR(255) NULL COMMENT 'EAN',
    
    -- Základní info
    `name` VARCHAR(500) NOT NULL,
    `description` TEXT NULL,
    `short_description` TEXT NULL,
    
    -- Kategorizace
    `category` VARCHAR(255) NULL,
    `supplier` VARCHAR(255) NULL,
    `manufacturer` VARCHAR(255) NULL,
    
    -- Ceny
    `purchase_price` DECIMAL(12,2) NULL COMMENT 'Nákupní cena',
    `standard_price` DECIMAL(12,2) NULL COMMENT 'Běžná cena',
    `action_price` DECIMAL(12,2) NULL COMMENT 'Akční cena',
    `vat_rate` DECIMAL(5,2) DEFAULT 21.00,
    
    -- Sklad
    `stock` INT DEFAULT 0,
    `availability_status` VARCHAR(100) NULL,
    `delivery_days` INT NULL,
    
    -- Rozměry a hmotnost
    `weight` DECIMAL(8,2) NULL COMMENT 'kg',
    `width` DECIMAL(8,2) NULL COMMENT 'cm',
    `height` DECIMAL(8,2) NULL COMMENT 'cm',
    `depth` DECIMAL(8,2) NULL COMMENT 'cm',
    
    -- Značky
    `is_sale` BOOLEAN DEFAULT FALSE,
    `is_new` BOOLEAN DEFAULT FALSE,
    `is_top` BOOLEAN DEFAULT FALSE,
    `has_variants` BOOLEAN DEFAULT FALSE,
    
    -- JSON data
    `images` JSON NULL COMMENT 'URL obrázků',
    `parameters` JSON NULL COMMENT 'Parametry produktu',
    `raw_data` JSON NULL COMMENT 'Raw XML/CSV data',
    
    -- Metadata
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_user_code` (`user_id`, `code`),
    INDEX `idx_guid` (`guid`),
    INDEX `idx_category` (`category`),
    INDEX `idx_stock` (`stock`),
    UNIQUE KEY `unique_product_per_user` (`user_id`, `guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Produkty z e-shopů';

-- ====================
-- PRODUCT VARIANTS
-- ====================
DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE `product_variants` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` BIGINT UNSIGNED NOT NULL,
    
    -- Identifikátory
    `external_id` VARCHAR(255) NULL,
    `code` VARCHAR(255) NULL,
    `ean` VARCHAR(255) NULL,
    
    -- Info
    `name` VARCHAR(500) NOT NULL,
    `parameters` JSON NOT NULL COMMENT 'Barva, velikost apod.',
    
    -- Ceny
    `purchase_price` DECIMAL(12,2) NULL,
    `standard_price` DECIMAL(12,2) NULL,
    `action_price` DECIMAL(12,2) NULL,
    
    -- Sklad
    `stock` INT DEFAULT 0,
    `availability_status` VARCHAR(100) NULL,
    `delivery_days` INT NULL,
    
    -- Image
    `image_url` VARCHAR(2048) NULL,
    
    -- Raw data
    `raw_data` JSON NULL,
    
    -- Metadata
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Varianty produktů';

-- ====================
-- IMPORT LOGS
-- ====================
DROP TABLE IF EXISTS `import_logs`;
CREATE TABLE `import_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `feed_source_id` BIGINT UNSIGNED NULL,
    `type` ENUM('products','orders') NOT NULL,
    `status` ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    
    -- Statistiky
    `total_records` INT DEFAULT 0,
    `processed_records` INT DEFAULT 0,
    `created_records` INT DEFAULT 0,
    `updated_records` INT DEFAULT 0,
    `failed_records` INT DEFAULT 0,
    
    -- Timing
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `duration_seconds` INT NULL,
    
    -- Resources
    `file_size` BIGINT NULL COMMENT 'bytes',
    `memory_peak_mb` DECIMAL(8,2) NULL,
    
    -- Error info
    `error_message` TEXT NULL,
    `error_trace` TEXT NULL,
    
    -- Metadata
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`feed_source_id`) REFERENCES `feed_sources`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Logy importů';

-- ====================
-- ACTIVITY LOG (pro audit trail)
-- ====================
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'login, logout, create_user, delete_product...',
    `entity_type` VARCHAR(50) NULL COMMENT 'user, product, feed_source...',
    `entity_id` BIGINT UNSIGNED NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_action` (`user_id`, `action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit log aktivit uživatelů';

-- ====================
-- DEFAULT DATA
-- ====================

-- Super Admin uživatel
-- Email: info@shopcode.cz
-- Heslo: Shopcode2024??
-- BCRYPT hash (funguje na všech serverech)
INSERT INTO `users` (`name`, `email`, `password`, `is_super_admin`, `is_active`, `company_name`) 
VALUES (
    'Super Admin',
    'info@shopcode.cz',
    '$2y$12$qOXrG5K8h3GxQZvYqZ8hKZvQqZ8hKZvQqZ8hKZvQqZ8hKZvQqZw',
    TRUE,
    TRUE,
    'Shopcode'
) ON DUPLICATE KEY UPDATE email = email;

-- POZNÁMKA: Po instalaci doporučujeme spustit setup.php pro vygenerování
-- aktuálního hashe na vašem serveru

SET foreign_key_checks = 1;

-- Hotovo!
SELECT 'Database setup completed successfully!' as Status;
