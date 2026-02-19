# Multi-Store System - Dokumentace

## 🎯 Přehled

Systém umožňuje jednomu uživateli spravovat **více e-shopů** s flexibilním přiřazováním nákladů.

---

## 📦 Instalace

### 1. Stáhni aktuální verzi
```bash
cd /path/to/app
git pull
```

### 2. Spusť SQL migraci
```bash
mysql -u USER -p DATABASE < database/MULTI_STORE_MIGRATION.sql
```

**Co migrace dělá:**
- ✅ Vytvoří tabulku `stores`
- ✅ Přidá `store_id` do všech relevantních tabulek
- ✅ Přidá `scope` (global/store) pro náklady
- ✅ Vytvoří výchozí shop pro každého uživatele
- ✅ Migruje všechna existující data na výchozí shop

---

## 🏪 Koncept

### Hierarchie
```
User (Milan)
├── Store: LasiLueta CZ
│   ├── Produkty (feed XML)
│   ├── Objednávky (feed CSV)
│   └── Náklady [vlastní / sdílené / kombinované]
│
├── Store: LasiLueta SK
│   ├── Produkty (feed XML)
│   ├── Objednávky (feed CSV)
│   └── Náklady [vlastní / sdílené / kombinované]
│
└── Globální náklady
    ├── Nájem kanceláře: 50 000 Kč
    ├── Účetní: 15 000 Kč
    └── Hosting: 5 000 Kč
```

### Nákladové režimy

#### 1. **own** (Vlastní náklady)
Shop má pouze vlastní náklady, globální ignoruje.

```
LasiLueta CZ: cost_sharing_mode = 'own'
└── Facebook Ads CZ: 10 000 Kč
└── Google Ads CZ: 8 000 Kč
= 18 000 Kč celkem
```

#### 2. **shared** (Sdílené náklady)
Shop používá jen globální náklady s % alokací.

```
Globální: 70 000 Kč celkem

LasiLueta CZ: cost_sharing_mode = 'shared', allocation = 60%
└── 60% z globálních = 42 000 Kč

LasiLueta SK: cost_sharing_mode = 'shared', allocation = 40%
└── 40% z globálních = 28 000 Kč
```

#### 3. **combined** (Kombinované)
Shop má vlastní + část globálních nákladů.

```
LasiLueta CZ: cost_sharing_mode = 'combined', allocation = 60%
├── Vlastní: 18 000 Kč
└── Globální (60%): 42 000 Kč
= 60 000 Kč celkem
```

---

## 🎨 Uživatelské rozhraní

### Store Selector
V top baru vpravo vedle uživatelského jména:
```
🏪 [LasiLueta CZ ▼]  Milan (Admin)
```

**Funkce:**
- Dropdown pro přepínání mezi shopy
- Zobrazuje se jen když má uživatel 2+ shopů
- Přepnutí redirectuje přes `/app/switch-store.php`

### Správa e-shopů
**Stránka:** `/app/stores/`

**Funkce:**
- Přehled všech shopů v kartách
- Statistiky: produkty, objednávky, náklady
- Vytvoření nového shopu
- Editace: název, měna, nákladový režim, alokace
- Aktivace/deaktivace
- Smazání (jen když nemá data)

**Nákladový režim:**
- Výběr: Vlastní / Globální / Kombinované
- Alokace %: Kolik procent globálních nákladů připadá shopu

---

## 💻 Pro vývojáře

### Helper funkce

```php
// Získej ID aktuálního shopu
$storeId = currentStoreId(); // int|null

// Získej celý objekt shopu
$store = currentStore(); // array|null

// Přepni na jiný shop
switchStore(5); // předej store_id

// Všechny shopy uživatele
$stores = userStores(); // array
```

### Store Model

```php
use App\Models\Store;

$storeModel = new Store();

// Všechny shopy uživatele
$stores = $storeModel->getAllForUser($userId);

// Aktivní shopy
$activeStores = $storeModel->getActiveForUser($userId);

// Najít shop
$store = $storeModel->findById($storeId, $userId);

// Vytvořit shop
$storeId = $storeModel->create([
    'user_id' => $userId,
    'name' => 'LasiLueta CZ',
    'code' => 'lasilueta-cz',
    'currency' => 'CZK',
    'cost_sharing_mode' => 'combined',
    'global_cost_allocation_percent' => 60
]);

// Výchozí shop uživatele
$defaultStore = $storeModel->getDefaultForUser($userId);

// Spočítat náklady shopu (podle režimu)
$totalCosts = $storeModel->calculateTotalCosts($storeId, '2026-02');
```

### Aktualizace modelů

Všechny modely byly aktualizovány pro store filtrování:

```php
// Product
$products = $productModel->getAll($userId, $page, 20, $storeId);

// Order
$orders = $orderModel->getAll($userId, $page, 50, $filters, $storeId);
$analytics = $orderModel->getAnalytics($userId, $dateFrom, $dateTo, $storeId);
$topProducts = $orderModel->getTopProducts($userId, 10, $dateFrom, $dateTo, $storeId);
$trends = $orderModel->getMonthlyTrends($userId, $year, $storeId);

// Cost (zobrazí globální + store-specific)
$costs = $costModel->getAll($userId, $page, 20, $filters, $storeId);
```

### Import služby

```php
// XML import produktů
$xmlImporter = new XmlImportService();
$result = $xmlImporter->importFromUrl(
    $feedSourceId,
    $userId,
    $url,
    $storeId, // ← nový parametr
    $httpAuthUser,
    $httpAuthPass
);

// CSV import objednávek
$csvImporter = new OrderCsvImportService();
$result = $csvImporter->importFromUrl(
    $userId,
    $url,
    $storeId, // ← nový parametr
    $httpAuthUser,
    $httpAuthPass
);
```

---

## 🗄️ Databáze

### Nové tabulky

#### `stores`
```sql
id, user_id, name, code, currency,
cost_sharing_mode, global_cost_allocation_percent,
is_active, created_at, updated_at
```

#### `cost_store_assignments`
```sql
id, cost_id, store_id, created_at
```
*M:N vztah - náklad může být přiřazen více shopům*

### Upravené tabulky

Všechny tyto tabulky mají nový sloupec `store_id`:
- `products`
- `product_variants`
- `orders`
- `costs` *(+ scope: global/store)*
- `shipping_costs` *(+ scope)*
- `billing_costs` *(+ scope)*
- `feed_sources`
- `order_feed_sources`

---

## 📊 Scope logika

### Produkty a objednávky
- Vždy patří konkrétnímu shopu
- `store_id` je **povinný**

### Náklady
- `scope = 'global'` → viditelné pro všechny shopy
- `scope = 'store'` → specifické pro jeden shop

**Příklady:**

```sql
-- Globální náklad (všechny shopy)
INSERT INTO costs (user_id, store_id, scope, name, amount, type)
VALUES (1, NULL, 'global', 'Nájem kanceláře', 50000, 'fixed');

-- Store-specific náklad (jen LasiLueta CZ)
INSERT INTO costs (user_id, store_id, scope, name, amount, type)
VALUES (1, 5, 'store', 'Facebook Ads CZ', 10000, 'variable');
```

**Query pro zobrazení:**
```sql
SELECT * FROM costs
WHERE user_id = ?
  AND (scope = 'global' OR (scope = 'store' AND store_id = ?))
```

---

## ✅ Checklist po instalaci

- [ ] Spustit SQL migraci
- [ ] Zkontrolovat že byl vytvořen výchozí shop (`SELECT * FROM stores`)
- [ ] Přihlásit se a ověřit že shop selector funguje
- [ ] Vytvořit nový shop v `/app/stores/`
- [ ] Přepnout mezi shopy v top baru
- [ ] Spustit import produktů → ověřit že mají `store_id`
- [ ] Spustit import objednávek → ověřit že mají `store_id`
- [ ] Zkontrolovat že analytika filtruje podle shopu

---

## 🐛 Řešení problémů

### Produkty/objednávky se nezobrazují
**Příčina:** Nemají `store_id` nebo store není aktivní  
**Řešení:**
```sql
-- Zkontroluj store_id
SELECT id, name, store_id FROM products LIMIT 10;

-- Přiřaď výchozí shop pokud je NULL
UPDATE products SET store_id = (
    SELECT id FROM stores WHERE user_id = products.user_id LIMIT 1
)
WHERE store_id IS NULL;
```

### Store selector se nezobrazuje
**Příčina:** Uživatel má jen 1 shop  
**Řešení:** To je očekávané chování. Vytvoř druhý shop.

### Import selhává s foreign key error
**Příčina:** Import service nemá storeId  
**Řešení:** Zkontroluj že volání importu obsahuje storeId parametr

---

## 📚 Další zdroje

- **SQL Migrace:** `/database/MULTI_STORE_MIGRATION.sql`
- **Store Model:** `/src/Models/Store.php`
- **Helper funkce:** `/src/helpers.php` (řádek 246+)
- **UI správa:** `/app/stores/index.php`

---

## 🎉 Hotovo!

Systém je plně funkční a připravený k použití. Každý uživatel může:

1. ✅ Vytvořit více e-shopů
2. ✅ Přepínat mezi nimi
3. ✅ Importovat produkty a objednávky per shop
4. ✅ Vidět analytiku per shop
5. ✅ Sdílet náklady nebo mít vlastní
6. ✅ Alokovat globální náklady podle %

**Happy multi-shopping! 🛍️**
