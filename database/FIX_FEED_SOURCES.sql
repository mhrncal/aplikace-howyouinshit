-- =====================================================
-- FEED SOURCES - PŘIDÁNÍ SLOUPCŮ PRO KOMPATIBILITU
-- =====================================================

-- 1. Přidat description sloupec
ALTER TABLE `feed_sources` 
ADD COLUMN `description` TEXT NULL AFTER `name`;

-- 2. Přidat feed_type sloupec (pro jednodušší rozšíření)
ALTER TABLE `feed_sources` 
ADD COLUMN `feed_type` VARCHAR(50) DEFAULT 'xml' AFTER `description`;

-- 3. Kontrola struktury
DESCRIBE feed_sources;

-- 4. Vložit ukázkový Shoptet feed
INSERT INTO `feed_sources` 
(`user_id`, `name`, `description`, `feed_type`, `type`, `url`, `is_active`) 
VALUES 
(1, 'Shoptet - Produktový feed', 'XML feed s produkty z Shoptetu', 'xml', 'products_xml', 'https://vase-domena.shoptet.cz/action/Products/xml', 1);

-- 5. Zobrazit výsledek
SELECT id, name, description, feed_type, type, url, is_active FROM feed_sources;

