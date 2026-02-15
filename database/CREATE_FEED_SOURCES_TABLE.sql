-- =====================================================
-- FEED_SOURCES - KOMPLETNÍ VYTVOŘENÍ TABULKY
-- =====================================================

-- KROK 1: Smazat starou tabulku (VAROVÁNÍ: Smaže všechna data!)
-- Odkomentuj pokud chceš smazat a vytvořit znovu:
-- DROP TABLE IF EXISTS `feed_sources`;

-- KROK 2: Vytvořit novou tabulku s VŠEMI sloupci
CREATE TABLE IF NOT EXISTS `feed_sources` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `feed_type` VARCHAR(50) DEFAULT 'xml',
    `type` ENUM('products_xml','orders_xml','products_csv','orders_csv') NOT NULL,
    `url` VARCHAR(2048) NOT NULL,
    
    -- Plánování
    `schedule` ENUM('hourly','daily','weekly','manual') DEFAULT 'daily',
    `schedule_time` TIME NULL COMMENT 'Čas pro denní import',
    `is_active` BOOLEAN DEFAULT TRUE,
    
    -- Statistiky importu
    `last_import_at` TIMESTAMP NULL,
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
    INDEX `idx_next_import` (`next_import_at`),
    INDEX `idx_last_import` (`last_import_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KROK 3: Kontrola struktury
DESCRIBE feed_sources;

-- KROK 4: Vložit testovací feed (volitelné)
INSERT INTO `feed_sources` 
(`user_id`, `name`, `description`, `feed_type`, `type`, `url`, `is_active`) 
VALUES 
(1, 'Test Shoptet Feed', 'Testovací produktový feed', 'xml', 'products_xml', 'https://example.shoptet.cz/action/Products/xml', 1)
ON DUPLICATE KEY UPDATE name=name;

-- KROK 5: Zobrazit všechny feedy
SELECT * FROM feed_sources;
