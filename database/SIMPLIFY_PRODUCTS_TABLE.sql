-- Zjednodušení tabulky products - odstranění nepotřebných sloupců
-- Datum: 2026-02-16

-- Products tabulka - odstranit nepotřebné sloupce
ALTER TABLE `products`
  DROP COLUMN IF EXISTS `ean`,
  DROP COLUMN IF EXISTS `description`,
  DROP COLUMN IF EXISTS `short_description`,
  DROP COLUMN IF EXISTS `purchase_price`,
  DROP COLUMN IF EXISTS `standard_price`,
  DROP COLUMN IF EXISTS `action_price`,
  DROP COLUMN IF EXISTS `vat_rate`,
  DROP COLUMN IF EXISTS `stock`,
  DROP COLUMN IF EXISTS `availability_status`,
  DROP COLUMN IF EXISTS `delivery_days`,
  DROP COLUMN IF EXISTS `weight`,
  DROP COLUMN IF EXISTS `width`,
  DROP COLUMN IF EXISTS `height`,
  DROP COLUMN IF EXISTS `depth`,
  DROP COLUMN IF EXISTS `is_sale`,
  DROP COLUMN IF EXISTS `is_new`,
  DROP COLUMN IF EXISTS `is_top`,
  DROP COLUMN IF EXISTS `images`,
  DROP COLUMN IF EXISTS `parameters`,
  DROP COLUMN IF EXISTS `raw_data`;

-- Product variants - odstranit nepotřebné sloupce
ALTER TABLE `product_variants`
  DROP COLUMN IF EXISTS `ean`,
  DROP COLUMN IF EXISTS `parameters`,
  DROP COLUMN IF EXISTS `purchase_price`,
  DROP COLUMN IF EXISTS `standard_price`,
  DROP COLUMN IF EXISTS `action_price`,
  DROP COLUMN IF EXISTS `stock`,
  DROP COLUMN IF EXISTS `availability_status`,
  DROP COLUMN IF EXISTS `delivery_days`,
  DROP COLUMN IF EXISTS `image_url`,
  DROP COLUMN IF EXISTS `raw_data`;

-- Smazat field_mappings tabulku (pokud existuje)
DROP TABLE IF EXISTS `field_mappings`;

SELECT 'Products table simplified successfully!' as Status;
