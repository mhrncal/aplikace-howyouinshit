-- ============================================
-- MULTI-STORE MIGRACE
-- Datum: 2026-02-19
-- Přidává podporu pro více e-shopů na účet
-- ============================================

-- ====================
-- KROK 1: Vytvoření tabulky stores
-- ====================

CREATE TABLE IF NOT EXISTS `stores` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL COMMENT 'Název e-shopu (např. "LasiLueta CZ")',
    `code` VARCHAR(50) NOT NULL COMMENT 'Unikátní kód (např. "lasilueta-cz")',
    `currency` VARCHAR(3) DEFAULT 'CZK',
    
    -- Nákladové nastavení
    `cost_sharing_mode` ENUM('own', 'shared', 'combined') DEFAULT 'own'
        COMMENT 'own=vlastní náklady, shared=jen globální, combined=vlastní+globální',
    
    `global_cost_allocation_percent` DECIMAL(5,2) DEFAULT 0
        COMMENT 'Kolik % globálních nákladů připadá na tento shop (0-100)',
    
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_code_per_user` (`user_id`, `code`),
    INDEX `idx_user_active` (`user_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='E-shopy - jeden uživatel může mít více shopů';

-- ====================
-- KROK 2: Přidání store_id do existujících tabulek
-- ====================

-- Products
ALTER TABLE `products` 
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD INDEX `idx_store` (`store_id`);

ALTER TABLE `products`
ADD CONSTRAINT `fk_products_store` 
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- Product variants
ALTER TABLE `product_variants`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `product_id`,
ADD INDEX `idx_store` (`store_id`);

-- Orders
ALTER TABLE `orders`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD INDEX `idx_store` (`store_id`);

ALTER TABLE `orders`
ADD CONSTRAINT `fk_orders_store`
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- Costs - přidáme scope pro globální vs. store-specific
ALTER TABLE `costs`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD COLUMN `scope` ENUM('global', 'store') DEFAULT 'store'
    COMMENT 'global=sdíleno všemi shopy, store=specifické pro jeden shop',
ADD INDEX `idx_store` (`store_id`),
ADD INDEX `idx_scope` (`scope`);

ALTER TABLE `costs`
ADD CONSTRAINT `fk_costs_store`
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- Shipping costs
ALTER TABLE `shipping_costs`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD COLUMN `scope` ENUM('global', 'store') DEFAULT 'store'
    COMMENT 'global=sdíleno všemi shopy, store=specifické pro jeden shop',
ADD INDEX `idx_store` (`store_id`),
ADD INDEX `idx_scope` (`scope`);

ALTER TABLE `shipping_costs`
ADD CONSTRAINT `fk_shipping_costs_store`
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- Drop old unique key, add new one per store
ALTER TABLE `shipping_costs`
DROP INDEX `unique_shipping_per_user`;

ALTER TABLE `shipping_costs`
ADD UNIQUE KEY `unique_shipping_per_store` (`store_id`, `shipping_code`);

-- Billing costs
ALTER TABLE `billing_costs`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD COLUMN `scope` ENUM('global', 'store') DEFAULT 'store'
    COMMENT 'global=sdíleno všemi shopy, store=specifické pro jeden shop',
ADD INDEX `idx_store` (`store_id`),
ADD INDEX `idx_scope` (`scope`);

ALTER TABLE `billing_costs`
ADD CONSTRAINT `fk_billing_costs_store`
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- Drop old unique key, add new one per store
ALTER TABLE `billing_costs`
DROP INDEX `unique_billing_per_user`;

ALTER TABLE `billing_costs`
ADD UNIQUE KEY `unique_billing_per_store` (`store_id`, `billing_code`);

-- Feed sources (produkty)
ALTER TABLE `feed_sources`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD INDEX `idx_store` (`store_id`);

ALTER TABLE `feed_sources`
ADD CONSTRAINT `fk_feed_sources_store`
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- Order feed sources
ALTER TABLE `order_feed_sources`
ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `user_id`,
ADD INDEX `idx_store` (`store_id`);

ALTER TABLE `order_feed_sources`
ADD CONSTRAINT `fk_order_feed_sources_store`
FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE;

-- ====================
-- KROK 3: Vytvoření výchozího shopu pro každého uživatele
-- ====================

-- Pro každého existujícího uživatele vytvoř výchozí shop
INSERT INTO `stores` (`user_id`, `name`, `code`, `currency`, `cost_sharing_mode`, `is_active`)
SELECT 
    id,
    CONCAT(name, ' - Hlavní shop'),
    CONCAT('shop-', id),
    'CZK',
    'own',
    1
FROM `users`
WHERE NOT EXISTS (
    SELECT 1 FROM `stores` WHERE `stores`.`user_id` = `users`.`id`
);

-- ====================
-- KROK 4: Migrace existujících dat na výchozí shop
-- ====================

-- Products
UPDATE `products` p
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON p.user_id = default_stores.user_id
SET p.store_id = default_stores.store_id
WHERE p.store_id IS NULL;

-- Orders
UPDATE `orders` o
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON o.user_id = default_stores.user_id
SET o.store_id = default_stores.store_id
WHERE o.store_id IS NULL;

-- Costs
UPDATE `costs` c
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON c.user_id = default_stores.user_id
SET c.store_id = default_stores.store_id, c.scope = 'store'
WHERE c.store_id IS NULL;

-- Shipping costs
UPDATE `shipping_costs` sc
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON sc.user_id = default_stores.user_id
SET sc.store_id = default_stores.store_id, sc.scope = 'store'
WHERE sc.store_id IS NULL;

-- Billing costs
UPDATE `billing_costs` bc
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON bc.user_id = default_stores.user_id
SET bc.store_id = default_stores.store_id, bc.scope = 'store'
WHERE bc.store_id IS NULL;

-- Feed sources
UPDATE `feed_sources` fs
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON fs.user_id = default_stores.user_id
SET fs.store_id = default_stores.store_id
WHERE fs.store_id IS NULL;

-- Order feed sources
UPDATE `order_feed_sources` ofs
JOIN (
    SELECT s.id as store_id, s.user_id
    FROM `stores` s
    WHERE s.code LIKE 'shop-%'
) AS default_stores ON ofs.user_id = default_stores.user_id
SET ofs.store_id = default_stores.store_id
WHERE ofs.store_id IS NULL;

-- ====================
-- KROK 5: Tabulka pro přiřazení nákladů více shopům (budoucí použití)
-- ====================

CREATE TABLE IF NOT EXISTS `cost_store_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `cost_id` BIGINT UNSIGNED NOT NULL,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`cost_id`) REFERENCES `costs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`store_id`) REFERENCES `stores`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_cost_store` (`cost_id`, `store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Many-to-many: náklad může být přiřazen více shopům';

-- ====================
-- HOTOVO
-- ====================

SELECT 'Multi-store migration completed successfully!' as Status,
       (SELECT COUNT(*) FROM stores) as Total_Stores,
       (SELECT COUNT(*) FROM users) as Total_Users;
