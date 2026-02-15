# 📥 INSTALAČNÍ INSTRUKCE

## Co jsem vytvořil

✅ **Kompletní E-shop Analytics Platform v čistém PHP 8.2+**

Celkem **21 souborů**:
- 15 PHP souborů (Core, Services, Views, Public pages)
- 1 SQL databázový setup
- 3 konfigurační soubory
- 2 dokumentace (README + DEPLOYMENT)

## Soubory ke stažení

1. **eshop-analytics.tar.gz** - Kompletní archiv se všemi soubory

## Jak nahrát na FTP

### Varianta A: Rozbalení lokálně + nahrání přes FTP

1. Stáhněte `eshop-analytics.tar.gz`
2. Rozbalte lokálně:
   ```bash
   tar -xzf eshop-analytics.tar.gz
   ```
3. Připojte se k FTP (použijte FileZilla, WinSCP apod.)
4. Nahrajte celou složku `eshop-analytics/` na server do `/var/www/html/`

### Varianta B: Nahrání archívu + rozbalení na serveru

1. Stáhněte `eshop-analytics.tar.gz`
2. Nahrajte přes FTP do `/var/www/html/`
3. Připojte se přes SSH
4. Spusťte:
   ```bash
   cd /var/www/html
   tar -xzf eshop-analytics.tar.gz
   rm eshop-analytics.tar.gz
   ```

## Po nahrání na server

1. Nastavte oprávnění:
   ```bash
   cd /var/www/html/eshop-analytics
   chmod -R 755 storage/
   ```

2. Spusťte setup:
   ```bash
   php setup.php
   ```

3. Nastavte web server root na `/var/www/html/eshop-analytics/public`

4. Nastavte cron:
   ```bash
   */15 * * * * php /var/www/html/eshop-analytics/cron/import.php
   ```

5. Přihlaste se:
   - URL: https://your-domain.cz
   - Email: infoshopcode.cz
   - Heslo: Shopcode2024??

## Detailní návod

Viz soubor `DEPLOYMENT.md` v archivu.

## Struktura projektu

```
eshop-analytics/
├── app/
│   ├── Core/              (Database, Auth)
│   ├── Services/          (XmlImportService)
│   └── Helpers/           (Helper funkce)
├── config/                (Konfigurace DB a app)
├── public/                (Web root - sem nastavit server)
│   ├── dashboard.php
│   ├── products.php
│   ├── feed_sources.php
│   └── ...
├── cron/                  (Import skripty)
├── storage/               (Logy, cache)
├── views/                 (Šablony)
├── database_setup.sql     (SQL schema)
├── setup.php              (Inicializace)
├── README.md
└── DEPLOYMENT.md
```

## Databáze

```
Server: store6.rosti.cz:3306
Database: infoshop_3342
Username: infoshop_3342
Password: Shopcode2024??
```

## Funkce

✅ Multi-tenancy (každý uživatel vidí svá data)
✅ Super Admin (infoshopcode.cz)
✅ XML import Shoptet feedů
✅ Streamované zpracování (XML Reader)
✅ Batch insert/update
✅ Podpora variant produktů
✅ Rotační cron (15 minut na uživatele)
✅ Bootstrap 5 UI
✅ Produkty, Feed zdroje, Dashboard
✅ Import logs & statistiky

## Podpora

Všechny soubory jsou kompletní a otestované.
Při problémech zkontrolujte logy v `storage/logs/`.
