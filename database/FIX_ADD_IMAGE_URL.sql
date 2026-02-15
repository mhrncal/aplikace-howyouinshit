-- =====================================================
-- FIX: Přidat image_url do products
-- =====================================================

-- 1. Zkontroluj současnou strukturu
DESCRIBE products;

-- 2. Přidej image_url
ALTER TABLE products 
ADD COLUMN `image_url` VARCHAR(500) NULL 
COMMENT 'URL hlavního obrázku' 
AFTER `images`;

-- 3. Přidej url pokud chybí
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS `url` VARCHAR(500) NULL 
COMMENT 'URL produktu' 
AFTER `description`;

-- 4. Kontrola
DESCRIBE products;

-- Mělo by být:
-- ...
-- url VARCHAR(500) NULL
-- image_url VARCHAR(500) NULL
-- ...

SELECT 'SQL HOTOVO - image_url přidán!' as status;
