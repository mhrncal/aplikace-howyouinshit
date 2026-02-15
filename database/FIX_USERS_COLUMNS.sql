-- =====================================================
-- USERS TABULKA - PŘIDÁNÍ CHYBĚJÍCÍCH SLOUPCŮ
-- =====================================================

-- Přidání sloupců pro adresu a firemní údaje
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `company_name` VARCHAR(255) NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `ico` VARCHAR(20) NULL AFTER `company_name`,
ADD COLUMN IF NOT EXISTS `dic` VARCHAR(30) NULL AFTER `ico`,
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `dic`,
ADD COLUMN IF NOT EXISTS `address` VARCHAR(255) NULL AFTER `phone`,
ADD COLUMN IF NOT EXISTS `city` VARCHAR(100) NULL AFTER `address`,
ADD COLUMN IF NOT EXISTS `zip` VARCHAR(20) NULL AFTER `city`,
ADD COLUMN IF NOT EXISTS `country` VARCHAR(100) DEFAULT 'Česká republika' AFTER `zip`;

-- Kontrola
DESCRIBE users;
