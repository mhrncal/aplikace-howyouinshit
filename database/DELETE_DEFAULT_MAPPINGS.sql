-- =====================================================
-- SMAZÁNÍ DEFAULTNÍCH MAPPINGŮ
-- =====================================================
-- Defaultní pole jsou nyní HARDCODED, ne v DB
-- V DB budou JEN custom mappingy přidané uživatelem

-- Zobraz co je v DB
SELECT * FROM field_mappings;

-- Smaž defaultní pole (budou hardcoded)
DELETE FROM field_mappings 
WHERE db_column IN (
    'name', 'code', 'price_vat', 
    'category', 'manufacturer', 'url', 'image_url', 
    'description', 'ean', 'price'
);

-- Kontrola - mělo by být 0 nebo jen custom pole
SELECT * FROM field_mappings;

SELECT 'Defaultní mappingy smazány - nyní jsou hardcoded!' as status;
