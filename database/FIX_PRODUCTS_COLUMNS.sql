-- =====================================================
-- PRODUCTS TABLE - Oprava struktury
-- =====================================================

-- Přidat chybějící sloupce
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `price` DECIMAL(10,2) DEFAULT 0 AFTER `manufacturer`,
ADD COLUMN IF NOT EXISTS `ean` VARCHAR(50) NULL AFTER `code`;

-- Kontrola struktury
DESCRIBE products;

-- Zobrazit prvních 5 produktů
SELECT id, name, code, price, price_vat FROM products LIMIT 5;
