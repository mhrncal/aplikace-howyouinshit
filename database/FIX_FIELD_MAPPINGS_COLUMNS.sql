-- =====================================================
-- RYCHLÁ OPRAVA: field_mappings chybějící sloupce
-- =====================================================

-- Zobraz současnou strukturu
DESCRIBE field_mappings;

-- Přidej chybějící sloupce
ALTER TABLE field_mappings 
ADD COLUMN IF NOT EXISTS target_type ENUM('column', 'json') DEFAULT 'column' COMMENT 'Kam uložit: column nebo JSON' AFTER field_type,
ADD COLUMN IF NOT EXISTS transformer VARCHAR(100) NULL COMMENT 'PHP funkce: strip_tags, trim, atd.' AFTER target_type,
ADD COLUMN IF NOT EXISTS is_searchable BOOLEAN DEFAULT FALSE COMMENT 'Vytvořit virtual column' AFTER transformer,
ADD COLUMN IF NOT EXISTS json_path VARCHAR(255) NULL COMMENT 'Cesta v JSON: $.weight' AFTER is_searchable;

-- Zobraz opravenou strukturu
DESCRIBE field_mappings;

-- Pokud už máš nějaké mappingy, nastav jim target_type
UPDATE field_mappings SET target_type = 'column' WHERE target_type IS NULL;

-- Test
SELECT COUNT(*) as mappings_count FROM field_mappings;
