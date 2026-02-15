# CRON IMPORT - Automatický import feedů

## 🎯 Jak to funguje

### Postupný import (Queue system)
- ✅ Jeden feed najednou (šetří server)
- ✅ Postupně všechny uživatele
- ✅ Priority: nejstarší import první
- ✅ Minimální interval 60 minut mezi importy

### Optimalizace pro 50+ uživatelů, 100+ feedů
```
50 uživatelů × 2 feedy = 100 feedů
Běží každých 5 minut = 12× za hodinu
1 feed za běh = 12 feedů za hodinu
100 feedů ÷ 12 = ~8 hodin na všechny feedy
```

## 📝 Instalace

### 1. Nastav práva
```bash
chmod +x /srv/app/cron-import.php
```

### 2. Přidej do crontab
```bash
crontab -e
```

### 3. Přidej řádek (běží každých 5 minut)
```cron
*/5 * * * * /usr/bin/php /srv/app/cron-import.php >> /srv/app/storage/logs/cron-import.log 2>&1
```

### Nebo každých 10 minut (pomalejší, šetrnější)
```cron
*/10 * * * * /usr/bin/php /srv/app/cron-import.php >> /srv/app/storage/logs/cron-import.log 2>&1
```

### Nebo každou hodinu (velmi šetrné)
```cron
0 * * * * /usr/bin/php /srv/app/cron-import.php >> /srv/app/storage/logs/cron-import.log 2>&1
```

## 🔧 Nastavení (v cron-import.php)

```php
$MAX_CONCURRENT_IMPORTS = 1;      // Jen 1 feed najednou
$IMPORT_INTERVAL_MINUTES = 60;    // Min. 1 hodina mezi importy
$MAX_EXECUTION_TIME = 1800;       // Max 30 minut na import
```

## 📊 Monitoring

### Sledování logů
```bash
tail -f /srv/app/storage/logs/cron-import.log
```

### Hlavní aplikační log
```bash
tail -f /srv/app/storage/logs/app.log
```

### Kontrola běžících importů
```bash
ps aux | grep cron-import
```

### Kontrola lock souboru
```bash
ls -lah /srv/app/storage/import.lock
```

## 🚨 Troubleshooting

### Import se zasekl
```bash
# Smaž lock soubor
rm /srv/app/storage/import.lock
```

### Duplicitní běhy
- Kontroluj že NENÍ více cron záznamů
- Zkontroluj lock soubor
```bash
crontab -l | grep cron-import
```

### Vysoká zátěž serveru
- Zvětši interval: `*/10` nebo `*/15` místo `*/5`
- Zmenši `IMPORT_INTERVAL_MINUTES` na 120 (2 hodiny)

### Chyby paměti
- Zvětši `memory_limit` v php.ini
- Nebo v cron-import.php: `ini_set('memory_limit', '1024M')`

## 📈 Optimalizace výkonu

### Pro 500 MB+ feedy:
```php
// V XmlImportService.php:
$batchSize = 10; // Místo 20 (šetří paměť)
usleep(50000);   // Delší pauzy (50ms)
```

### Pro pomalý server:
```cron
*/15 * * * *  # Každých 15 minut
```

### Pro výkonný server:
```cron
*/3 * * * *   # Každé 3 minuty (4 feedy/hod = rychlejší)
```

## 🎯 Priority importu

1. **Nikdy neimportované** feedy (první)
2. **Nejstarší import** (podle last_imported_at)
3. **Aktivní** feedy (is_active = 1)
4. **Aktivní** uživatelé (user.is_active = 1)

## 💡 Tips

### Testování manuálně
```bash
php /srv/app/cron-import.php
```

### Debug mode (podrobnější logy)
```php
// Na začátku cron-import.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Kontrola všech feedů
```sql
SELECT 
    fs.id, 
    fs.name, 
    u.email, 
    fs.last_imported_at,
    TIMESTAMPDIFF(MINUTE, fs.last_imported_at, NOW()) as minutes_ago
FROM feed_sources fs
JOIN users u ON fs.user_id = u.id
WHERE fs.is_active = 1
ORDER BY fs.last_imported_at ASC;
```

## 🔒 Bezpečnost

- Lock soubor zabraňuje duplicitním běhům
- Timeout po 30 minutách (automatické uvolnění)
- Jeden feed najednou (kontrola zátěže)
- Logování všech akcí

## 📧 Notifikace (TODO)

Můžeš přidat email notifikace po dokončení:

```php
// Na konci úspěšného importu
mail(
    $feed['user_email'],
    "Import dokončen: {$feed['name']}",
    "Importováno: {$result['imported']} produktů"
);
```
