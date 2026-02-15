-- Zobrazit strukturu users tabulky
DESCRIBE users;

-- Pokud sloupce NEEXISTUJÍ, přidej je:
ALTER TABLE `users` 
ADD COLUMN `address` VARCHAR(255) NULL AFTER `phone`,
ADD COLUMN `city` VARCHAR(100) NULL AFTER `address`,
ADD COLUMN `zip` VARCHAR(20) NULL AFTER `city`,
ADD COLUMN `country` VARCHAR(100) DEFAULT 'Česká republika' AFTER `zip`;

-- Kontrola po přidání
DESCRIBE users;

-- Test update
UPDATE users SET address = 'Test 123', city = 'Ostrava', zip = '702 00', country = 'Česká republika' WHERE id = 1;

-- Zobrazit výsledek
SELECT id, name, email, address, city, zip, country FROM users WHERE id = 1;
