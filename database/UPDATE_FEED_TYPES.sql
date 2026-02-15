-- =====================================================
-- UPDATE EXISTUJÍCÍCH FEEDŮ - Oprava feed_type
-- =====================================================

-- Zobraz současné feedy
SELECT id, name, feed_type, type FROM feed_sources;

-- Update feed_type podle type
UPDATE feed_sources 
SET feed_type = CASE type
    WHEN 'products_xml' THEN 'shoptet_products'
    WHEN 'orders_xml' THEN 'shoptet_orders'
    WHEN 'stock_xml' THEN 'shoptet_stock'
    WHEN 'prices_xml' THEN 'shoptet_prices'
    ELSE feed_type
END
WHERE feed_type IN ('xml', 'shoptet', 'json', 'csv') OR feed_type IS NULL;

-- Kontrola po update
SELECT id, name, feed_type, type FROM feed_sources;

-- Pokud máš konkrétní feed s ID 2:
UPDATE feed_sources SET feed_type = 'shoptet_products' WHERE id = 2;
