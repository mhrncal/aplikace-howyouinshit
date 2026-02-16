-- Analytika objednávek a výnosů
-- Datum: 2026-02-16

-- ====================
-- ORDER FEED SOURCES
-- ====================
DROP TABLE IF EXISTS `order_feed_sources`;
CREATE TABLE `order_feed_sources` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    
    -- Plánování
    `schedule` ENUM('hourly','daily','weekly','manual') DEFAULT 'daily',
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Statistiky
    `last_imported_at` TIMESTAMP NULL,
    `total_imports` INT DEFAULT 0,
    `last_import_records` INT NULL,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_active` (`user_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Zdroje CSV feedů objednávek';

-- ====================
-- SHIPPING COSTS MAPPING
-- ====================
DROP TABLE IF EXISTS `shipping_costs`;
CREATE TABLE `shipping_costs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `shipping_code` VARCHAR(50) NOT NULL,
    `shipping_name` VARCHAR(255) NOT NULL,
    `cost` DECIMAL(12,2) DEFAULT 0 COMMENT 'Náklady na dopravu',
    `is_positive` BOOLEAN DEFAULT TRUE COMMENT 'TRUE = příjem, FALSE = náklad',
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_shipping_per_user` (`user_id`, `shipping_code`),
    INDEX `idx_shipping_code` (`shipping_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mapování nákladů na dopravu';

-- ====================
-- BILLING COSTS MAPPING
-- ====================
DROP TABLE IF EXISTS `billing_costs`;
CREATE TABLE `billing_costs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `billing_code` VARCHAR(50) NOT NULL,
    `billing_name` VARCHAR(255) NOT NULL,
    `cost_fixed` DECIMAL(12,2) DEFAULT 0 COMMENT 'Fixní náklad (např. 50 Kč)',
    `cost_percent` DECIMAL(5,2) DEFAULT 0 COMMENT 'Procentní náklad (např. 2%)',
    `is_positive` BOOLEAN DEFAULT TRUE COMMENT 'TRUE = příjem, FALSE = náklad',
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_billing_per_user` (`user_id`, `billing_code`),
    INDEX `idx_billing_code` (`billing_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mapování nákladů na platby';

-- ====================
-- ORDERS
-- ====================
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `order_code` VARCHAR(50) NOT NULL,
    `order_date` DATETIME NOT NULL,
    `status` VARCHAR(100) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'CZK',
    `exchange_rate` DECIMAL(10,8) DEFAULT 1.00000000,
    `source` VARCHAR(100) NULL,
    `customer_group` VARCHAR(100) NULL,
    
    -- Součty za objednávku
    `total_revenue` DECIMAL(12,2) DEFAULT 0 COMMENT 'Celkový obrat',
    `total_cost` DECIMAL(12,2) DEFAULT 0 COMMENT 'Celkové náklady',
    `total_profit` DECIMAL(12,2) DEFAULT 0 COMMENT 'Celkový zisk',
    `margin_percent` DECIMAL(5,2) DEFAULT 0 COMMENT 'Marže v %',
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_order_per_user` (`user_id`, `order_code`),
    INDEX `idx_order_code` (`order_code`),
    INDEX `idx_order_date` (`order_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Objednávky';

-- ====================
-- ORDER ITEMS
-- ====================
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `item_type` ENUM('product','shipping','billing','discount') NOT NULL,
    `item_name` VARCHAR(500) NOT NULL,
    `item_code` VARCHAR(255) NULL,
    `variant_name` VARCHAR(255) NULL,
    `manufacturer` VARCHAR(255) NULL,
    `supplier` VARCHAR(255) NULL,
    `amount` INT DEFAULT 1,
    
    -- Ceny
    `unit_price_sale` DECIMAL(12,2) DEFAULT 0 COMMENT 'Prodejní cena za kus',
    `unit_price_cost` DECIMAL(12,2) DEFAULT 0 COMMENT 'Nákupní cena za kus',
    `total_revenue` DECIMAL(12,2) DEFAULT 0 COMMENT 'Celkový obrat položky',
    `total_cost` DECIMAL(12,2) DEFAULT 0 COMMENT 'Celkové náklady položky',
    `total_profit` DECIMAL(12,2) DEFAULT 0 COMMENT 'Celkový zisk položky',
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    INDEX `idx_item_type` (`item_type`),
    INDEX `idx_item_code` (`item_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Položky objednávek';

SELECT 'Order analytics tables created successfully!' as Status;
