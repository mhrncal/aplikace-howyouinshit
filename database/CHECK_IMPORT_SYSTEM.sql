-- =====================================================
-- KOMPLETNÍ KONTROLA DATABÁZE PRO IMPORT SYSTÉM
-- =====================================================

-- Spusť celý tento skript v phpMyAdmin
-- Ukáže ti co chybí a opraví to

-- =====================================================
-- 1. FEED SOURCES
-- =====================================================

SELECT '=== FEED SOURCES ===' as '';

-- Přidat všechny potřebné sloupce
ALTER TABLE `feed_sources` 
ADD COLUMN IF NOT EXISTS `description` TEXT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `feed_type` VARCHAR(50) DEFAULT 'xml' AFTER `description`,
ADD COLUMN IF NOT EXISTS `last_imported_at` TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS `total_imports` INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS `failed_imports` INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS `last_import_records` INT NULL,
ADD COLUMN IF NOT EXISTS `last_import_duration` INT NULL;

-- Zobrazit strukturu
DESCRIBE feed_sources;

-- Zobrazit všechny feedy
SELECT 
    id,
    name,
    COALESCE(description, 'N/A') as description,
    COALESCE(feed_type, 'N/A') as feed_type,
    type,
    is_active,
    last_imported_at,
    total_imports,
    failed_imports
FROM feed_sources;

-- =====================================================
-- 2. IMPORT LOGS
-- =====================================================

SELECT '=== IMPORT LOGS ===' as '';

-- Import logs by měly existovat z schema.sql
-- Kontrola existence
SELECT COUNT(*) as import_logs_count FROM import_logs;

-- Poslední importy
SELECT 
    il.id,
    u.email as user,
    fs.name as feed,
    il.status,
    il.processed_records,
    il.created_records,
    il.updated_records,
    il.failed_records,
    il.duration_seconds,
    il.created_at
FROM import_logs il
LEFT JOIN users u ON il.user_id = u.id
LEFT JOIN feed_sources fs ON il.feed_source_id = fs.id
ORDER BY il.created_at DESC
LIMIT 10;

-- =====================================================
-- 3. PRODUCTS
-- =====================================================

SELECT '=== PRODUCTS ===' as '';

-- Zkontrolovat produkty
SELECT COUNT(*) as total_products FROM products;

SELECT 
    user_id,
    COUNT(*) as products_count
FROM products
GROUP BY user_id;

-- =====================================================
-- 4. KONTROLA INDEXŮ (pro výkon)
-- =====================================================

SELECT '=== INDEXY ===' as '';

-- Zobrazit indexy na feed_sources
SHOW INDEX FROM feed_sources;

-- Zobrazit indexy na import_logs
SHOW INDEX FROM import_logs;

-- =====================================================
-- 5. STORAGE SLOŽKA
-- =====================================================

SELECT '=== POZNÁMKY ===' as '';

SELECT 'DŮLEŽITÉ: Vytvoř složky pro logs a lock file:' as message
UNION ALL
SELECT 'mkdir -p /srv/app/storage/logs'
UNION ALL
SELECT 'mkdir -p /srv/app/storage/sessions'
UNION ALL
SELECT 'chmod 777 /srv/app/storage/logs'
UNION ALL
SELECT 'chmod 777 /srv/app/storage/sessions'
UNION ALL
SELECT ''
UNION ALL
SELECT 'Pro CRON:'
UNION ALL
SELECT 'chmod +x /srv/app/cron-import.php'
UNION ALL
SELECT 'crontab -e'
UNION ALL
SELECT '*/5 * * * * /usr/bin/php /srv/app/cron-import.php >> /srv/app/storage/logs/cron-import.log 2>&1';

-- =====================================================
-- HOTOVO!
-- =====================================================
