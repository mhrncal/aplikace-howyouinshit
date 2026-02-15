# 🚀 KOMPLETNÍ SETUP NÁVOD - Flexibilní Import Systém

## 📋 PŘEHLED:
- ✅ 100+ uživatelů
- ✅ Každý uživatel vlastní mappingy
- ✅ JSON custom pole (neomezené)
- ✅ Shoptet + jiné XML
- ✅ CSV/XLSX export
- ✅ Rozšířené vyhledávání

---

## 🎯 KROK ZA KROKEM:

### **KROK 1: Spusť SQL** (phpMyAdmin)

```sql
-- HLAVNÍ SQL - Spusť celý soubor:
database/COMPLETE_MULTI_FEED_SYSTEM.sql
```

**Co udělá:**
- ✅ Vytvoří/opraví `products` (+ custom_data JSON)
- ✅ Vytvoří `orders`, `order_items`
- ✅ Vytvoří/opraví `field_mappings`
- ✅ Vytvoří/opraví `import_logs`

**Pak spusť:**
```sql
-- Oprví feed_type u existujících feedů:
database/UPDATE_FEED_TYPES.sql
```

---

### **KROK 2: Restartuj aplikaci**

```
Rosti.cz admin panel → Aplikace → Restart
```

**Důvod:** Vyčistit opcache (stará verze kódu)

---

### **KROK 3: Nastav výchozí mappingy**

Otevři v prohlížeči:
```
https://superapka-8716.rostiapp.cz/app/products/setup-default-mappings.php
```

**Co to udělá:**
- Vytvoří 11 výchozích mappingů pro tvůj účet
- POVINNÁ pole: name, code, price_vat (→ column)
- ČASTO používaná: category, url, image (→ column)
- CUSTOM pole: weight, stock_amount (→ JSON)

---

### **KROK 4: Zkontroluj mappingy**

```
https://superapka-8716.rostiapp.cz/app/products/field-mapping.php
```

**Mělo by tam být:**
```
Mapování produktů (11)
- name → NAME (column)
- code → CODE (column)
- price_vat → PRICE_VAT (column)
- category → CATEGORY (column)
- manufacturer → MANUFACTURER (column)
- url → ORIG_URL (column)
- image_url → IMAGE (column)
- description → DESCRIPTION (column, transformer: strip_tags)
- ean → EAN (column)
- weight → WEIGHT (json)
- stock_amount → STOCK_AMOUNT (json)
```

---

### **KROK 5: Zkus import**

```
https://superapka-8716.rostiapp.cz/app/feed-sources/import-now.php?id=2
```

**Klikni:** "Spustit import"

**Mělo by:**
1. ✅ Zobrazit progress bar
2. ✅ Live log
3. ✅ Import dokončen!
4. ✅ Statistiky (importováno X produktů)

---

### **KROK 6: Zkontroluj produkty**

```
https://superapka-8716.rostiapp.cz/app/products/
```

**Mělo by zobrazit:**
- ✅ Seznam produktů
- ✅ Název, kód, cena
- ✅ Kategorie

**Zkontroluj databázi:**
```sql
SELECT 
    id, name, code, price_vat, 
    JSON_EXTRACT(custom_data, '$.weight') as weight,
    JSON_EXTRACT(custom_data, '$.stock_amount') as stock
FROM products 
LIMIT 5;
```

**Mělo by:**
- ✅ Sloupce vyplněné
- ✅ custom_data obsahuje JSON
- ✅ weight, stock_amount v JSON

---

## 🎨 PŘIDÁNÍ VLASTNÍHO POLE:

### Příklad: Přidej "barva" do custom_data

**1. Jdi na:**
```
/app/products/field-mapping.php
```

**2. Klikni:**
"Nové mapování produktu"

**3. Vyplň:**
```
DB Sloupec: color
XML Cesta: COLOR
Kam uložit: 🔧 Custom pole (flexibilní)
Typ dat: string
```

**4. Příští import:**
```
custom_data: {
  "weight": 1.5,
  "stock_amount": 10,
  "color": "červená"  ← NOVÉ!
}
```

---

## 📊 EXPORT DO CSV/XLSX:

### CSV Export:
```
https://superapka-8716.rostiapp.cz/app/products/export.php?format=csv
```

**Výsledek:**
```csv
ID;Název;Kód;Cena;Kategorie;URL;Obrázek;weight;stock_amount;color
1;Produkt A;ABC123;100;Test;http://...;http://...;1.5;10;červená
```

### XLSX Export:
```
/app/products/export.php?format=xlsx
```

**Potřebuje:** PhpSpreadsheet
```bash
composer require phpoffice/phpspreadsheet
```

---

## 🔍 ROZŠÍŘENÉ VYHLEDÁVÁNÍ:

```
https://superapka-8716.rostiapp.cz/app/products/advanced-search.php
```

**Funkce:**
- ✅ Hledat v názvu, kódu
- ✅ Filtr kategorie
- ✅ Filtr ceny (od-do)
- ✅ Filtry custom polí (weight, color, atd.)
- ✅ Export CSV/XLSX přímo z výsledků

---

## 🧪 TESTOVÁNÍ:

### Test 1: Základní import
```
1. Setup mappingy
2. Import feed
3. Kontrola products tabulky
```

### Test 2: Custom pole
```
1. Přidej mapping: color → COLOR (json)
2. Import znovu
3. Kontrola: SELECT JSON_EXTRACT(custom_data, '$.color')
```

### Test 3: Export
```
1. Export CSV
2. Otevři v Excelu
3. Kontrola sloupců (včetně custom)
```

---

## ⚠️ TROUBLESHOOTING:

### Problém: "Unknown column 'price'"
**Řešení:** Spusť `COMPLETE_MULTI_FEED_SYSTEM.sql` znovu

### Problém: "Neznámý typ feedu: shoptet"
**Řešení:** Spusť `UPDATE_FEED_TYPES.sql`

### Problém: Import 0 produktů
**Řešení:** 
1. Zkontroluj mappingy (`/app/products/field-mapping.php`)
2. Spusť `setup-default-mappings.php`
3. Restartuj aplikaci (opcache)

### Problém: custom_data je NULL
**Řešení:** 
1. Kontrola mappingů (target_type = json?)
2. Restartuj aplikaci
3. Re-import

---

## 📝 POZNÁMKY:

### Povinná pole (VŽDY column):
- `name` - Název produktu
- `code` - Kód produktu
- `price_vat` - Cena s DPH

### Doporučené column (rychlé vyhledávání):
- `category`
- `manufacturer`
- `url`
- `image_url`
- `ean`

### Custom JSON (flexibilní):
- `weight` - váha
- `color` - barva
- `stock_amount` - sklad
- `supplier_code` - kód dodavatele
- ... (cokoliv dalšího)

---

## ✅ CHECKLIST:

- [ ] Spustil jsem `COMPLETE_MULTI_FEED_SYSTEM.sql`
- [ ] Spustil jsem `UPDATE_FEED_TYPES.sql`
- [ ] Restartoval jsem aplikaci
- [ ] Spustil jsem `setup-default-mappings.php`
- [ ] Zkontroloval jsem mappingy
- [ ] Zkusil jsem import
- [ ] Import proběhl úspěšně
- [ ] Produkty jsou v databázi
- [ ] custom_data obsahuje JSON
- [ ] Vyzkoušel jsem export CSV
- [ ] Vyzkoušel jsem rozšířené vyhledávání

---

## 🎉 HOTOVO!

Máš plně funkční flexibilní import systém pro 100+ uživatelů! 🚀
