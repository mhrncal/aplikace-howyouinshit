-- Tabulka pro evidenci nákladů
CREATE TABLE IF NOT EXISTS `costs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `type` ENUM('fixed','variable') DEFAULT 'fixed' COMMENT 'Fixní nebo variabilní náklad',
    `frequency` ENUM('daily','weekly','monthly','quarterly','yearly','once') DEFAULT 'monthly' COMMENT 'Frekvence nákladu',
    `category` VARCHAR(100) NULL COMMENT 'Kategorie (mzdy, energie, marketing...)',
    `start_date` DATE NOT NULL COMMENT 'Od kdy platí',
    `end_date` DATE NULL COMMENT 'Do kdy platí (NULL = neomezeno)',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_frequency` (`frequency`),
    INDEX `idx_category` (`category`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ukázková data
INSERT INTO `costs` (`user_id`, `name`, `description`, `amount`, `type`, `frequency`, `category`, `start_date`) VALUES
(1, 'Kancelář - nájem', 'Měsíční nájem kanceláře', 25000.00, 'fixed', 'monthly', 'Provoz', '2026-01-01'),
(1, 'Mzdy', 'Platy zaměstnanců', 120000.00, 'fixed', 'monthly', 'Mzdy', '2026-01-01'),
(1, 'Marketing - Google Ads', 'PPC kampaně', 15000.00, 'variable', 'monthly', 'Marketing', '2026-01-01'),
(1, 'Elektřina', 'Energie kancelář', 3500.00, 'fixed', 'monthly', 'Provoz', '2026-01-01'),
(1, 'Internet', 'Firemní internet', 1200.00, 'fixed', 'monthly', 'IT', '2026-01-01'),
(1, 'Software licen ce', 'SaaS nástroje', 8000.00, 'fixed', 'monthly', 'IT', '2026-01-01'),
(1, 'Účetnictví', 'Služby účetní', 5000.00, 'fixed', 'quarterly', 'Služby', '2026-01-01');
