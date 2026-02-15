# 📝 JAK PŘIDAT NOVÉ POLE DO IMPORTU

## 🎯 Rychlý návod (3 kroky)

### 1️⃣ Přidej sloupec do databáze

```sql
ALTER TABLE `products` 
ADD COLUMN `warranty` VARCHAR(100) NULL AFTER `availability`;
```

### 2️⃣ Přidej mapping v konfiguraci

Otevři: `src/Modules/Products/Config/XmlFieldMapping.php`

Přidej do `getProductMapping()`:

```php
'warranty' => [
    'xml_path' => 'WARRANTY',
    'default' => '24 měsíců',
],
```

### 3️⃣ HOTOVO! 🎉

Import automaticky začne ukládat nové pole!

---

## 📚 KOMPLETNÍ PŘÍKLADY

### Příklad 1: Přidat váhu produktu

**1. SQL:**
```sql
ALTER TABLE `products` 
ADD COLUMN `weight` DECIMAL(10,2) NULL COMMENT 'Váha v kg';
```

**2. Config:**
```php
'weight' => [
    'xml_path' => 'LOGISTIC/WEIGHT',
    'transform' => 'floatval',
    'default' => 0,
],
```

### Příklad 2: Přidat skladové množství

**1. SQL:**
```sql
ALTER TABLE `products` 
ADD COLUMN `stock_amount` INT DEFAULT 0;
```

**2. Config:**
```php
'stock_amount' => [
    'xml_path' => 'STOCK/AMOUNT',
    'transform' => 'intval',
    'default' => 0,
],
```

### Příklad 3: Přidat sazbu DPH

**1. SQL:**
```sql
ALTER TABLE `products` 
ADD COLUMN `vat_rate` INT DEFAULT 21;
```

**2. Config:**
```php
'vat_rate' => [
    'xml_path' => 'VAT',
    'transform' => 'intval',
    'default' => 21,
],
```

### Příklad 4: Přidat brand (s alternativou)

**1. SQL:**
```sql
ALTER TABLE `products` 
ADD COLUMN `brand` VARCHAR(255) NULL;
```

**2. Config:**
```php
'brand' => [
    'xml_path' => 'BRAND',
    'xml_path_alt' => 'MANUFACTURER',  // Záložní možnost
    'default' => '',
],
```

### Příklad 5: Přidat aktivní/neaktivní

**1. SQL:**
```sql
ALTER TABLE `products` 
ADD COLUMN `is_active` BOOLEAN DEFAULT 1;
```

**2. Config:**
```php
'is_active' => [
    'xml_path' => 'VISIBLE',
    'transform' => function($value) {
        return (int)$value === 1;
    },
    'default' => true,
],
```

---

## 🔧 POKROČILÉ MOŽNOSTI

### Transformace dat

```php
// Jednoduchá funkce
'weight' => [
    'xml_path' => 'WEIGHT',
    'transform' => 'floatval',  // Převede na float
],

// Vlastní transformace
'delivery_days' => [
    'xml_path' => 'DELIVERY_DATE',
    'transform' => function($value) {
        // Převeď "2-3 dny" → 3
        preg_match('/(\d+)/', $value, $matches);
        return isset($matches[1]) ? (int)$matches[1] : 0;
    },
],

// Strip HTML tags
'description' => [
    'xml_path' => 'DESCRIPTION',
    'transform' => 'strip_tags',
],
```

### Více alternativ

```php
'category' => [
    'xml_path' => 'CATEGORIES/DEFAULT_CATEGORY',
    'xml_path_alt' => 'CATEGORIES/CATEGORY[0]',
    'xml_path_alt2' => 'CATEGORYTEXT',
    'default' => 'Nezařazeno',
],
```

### XPath s indexem

```php
'main_image' => [
    'xml_path' => 'IMAGES/IMAGE[0]',  // První obrázek
],

'second_image' => [
    'xml_path' => 'IMAGES/IMAGE[1]',  // Druhý obrázek
],
```

### Vnořené elementy

```php
'shipping_weight' => [
    'xml_path' => 'LOGISTIC/WEIGHT',  // <LOGISTIC><WEIGHT>...</WEIGHT></LOGISTIC>
],

'stock_min' => [
    'xml_path' => 'STOCK/MINIMAL_AMOUNT',
],
```

---

## 🎨 VARIANTY PRODUKTU

Pro přidání pole do variant (stejný postup):

**1. SQL:**
```sql
-- Varianty jsou v JSON, takže není potřeba ALTER TABLE
```

**2. Config v `getVariantMapping()`:**
```php
'stock_amount' => [
    'xml_path' => 'STOCK/AMOUNT',
    'transform' => 'intval',
    'default' => 0,
],
```

---

## 📋 KONTROLNÍ SEZNAM

- [ ] Přidal jsi sloupec do `products` tabulky?
- [ ] Přidal jsi mapping do `XmlFieldMapping.php`?
- [ ] Zadal jsi správnou `xml_path` (zkontroluj XML)?
- [ ] Přidal jsi `transform` pokud je potřeba?
- [ ] Přidal jsi `default` hodnotu?
- [ ] Otestoval jsi import?

---

## 🧪 TESTOVÁNÍ

```bash
# Spusť import
https://superapka-8716.rostiapp.cz/app/feed-sources/import-now.php?id=1

# Zkontroluj v DB
SELECT id, name, warranty, weight, stock_amount FROM products LIMIT 10;
```

---

## 💡 TIPY

1. **Nejdřív zjisti XML strukturu** - otevři XML feed v prohlížeči
2. **Použij alternativy** - pokud není jisté kde bude element
3. **Transform vždy** - převádění typů je důležité
4. **Default hodnoty** - když XML element chybí
5. **Test postupně** - přidej jedno pole, otestuj, pak další

---

## 🚀 PŘÍKLADY Z PRAXE

### Shoptet XML elementy:

```xml
<SHOPITEM>
    <NAME>Produkt</NAME>
    <WARRANTY>24 měsíců</WARRANTY>
    <LOGISTIC>
        <WEIGHT>1.5</WEIGHT>
    </LOGISTIC>
    <STOCK>
        <AMOUNT>10</AMOUNT>
        <MINIMAL_AMOUNT>2</MINIMAL_AMOUNT>
    </STOCK>
    <VAT>21</VAT>
    <VISIBLE>1</VISIBLE>
    <CATEGORIES>
        <DEFAULT_CATEGORY>Kategorie</DEFAULT_CATEGORY>
    </CATEGORIES>
    <IMAGES>
        <IMAGE>url1.jpg</IMAGE>
        <IMAGE>url2.jpg</IMAGE>
    </IMAGES>
</SHOPITEM>
```

### Odpovídající mapping:

```php
'warranty' => ['xml_path' => 'WARRANTY'],
'weight' => ['xml_path' => 'LOGISTIC/WEIGHT', 'transform' => 'floatval'],
'stock_amount' => ['xml_path' => 'STOCK/AMOUNT', 'transform' => 'intval'],
'stock_min' => ['xml_path' => 'STOCK/MINIMAL_AMOUNT', 'transform' => 'intval'],
'vat_rate' => ['xml_path' => 'VAT', 'transform' => 'intval'],
'is_active' => ['xml_path' => 'VISIBLE', 'transform' => fn($v) => (int)$v === 1],
'category' => ['xml_path' => 'CATEGORIES/DEFAULT_CATEGORY'],
'image_1' => ['xml_path' => 'IMAGES/IMAGE[0]'],
'image_2' => ['xml_path' => 'IMAGES/IMAGE[1]'],
```

---

## ❓ HELP

Pokud něco nefunguje, zkontroluj:

1. **Log soubor:** `storage/logs/app.log`
2. **XML strukturu:** Otevři feed v prohlížeči
3. **DB sloupec:** `DESCRIBE products;`
4. **Mapping config:** Je správně napsaný?

---

**Máš otázku? Kontaktuj vývojáře!** 🚀
