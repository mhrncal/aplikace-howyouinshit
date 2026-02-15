# 🧩 Průvodce tvorbou modulů

Tento dokument popisuje, jak vytvářet nové moduly a rozšiřovat funkcionalitu aplikace.

## 📁 Struktura modulů

Každý modul má svou samostatnou strukturu:

```
src/Modules/
└── NazevModulu/
    ├── Controllers/
    │   └── NazevController.php
    ├── Models/
    │   └── NazevModel.php
    ├── Services/
    │   └── NazevService.php
    └── Views/
        └── nazev.php
```

## 🎯 Vytvoření nového modulu

### 1. Model (Data layer)

```php
<?php

namespace App\Modules\Products\Models;

use App\Core\Database;

class Product
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(int $userId, int $page = 1): array
    {
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $products = $this->db->fetchAll(
            "SELECT * FROM products WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );
        
        return $products;
    }

    public function create(array $data): int
    {
        return $this->db->insert('products', $data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->update('products', $data, 'id = ?', [$id]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('products', 'id = ?', [$id]) > 0;
    }
}
```

### 2. Service (Business logic)

```php
<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\Product;
use App\Core\Logger;

class ProductService
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    public function importFromXml(string $url, int $userId): array
    {
        // Business logika pro import
        try {
            // XML parsing
            // Validace
            // Uložení do DB
            
            Logger::info('XML import completed', ['user_id' => $userId]);
            
            return ['success' => true, 'records' => 100];
            
        } catch (\Exception $e) {
            Logger::error('XML import failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

### 3. Controller (Request handling)

```php
<?php

namespace App\Modules\Products\Controllers;

use App\Core\Module;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\ProductService;

class ProductController extends Module
{
    private Product $productModel;
    private ProductService $productService;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->productService = new ProductService();
    }

    public function index(): void
    {
        $this->requireAuth();
        
        $page = (int) get('page', 1);
        $userId = $this->auth->userId();
        
        $products = $this->productModel->getAll($userId, $page);
        
        $this->render('products/index', [
            'title' => 'Produkty',
            'products' => $products
        ]);
    }

    public function create(): void
    {
        $this->requireAuth();
        
        if ($this->validatePost()) {
            $data = [
                'user_id' => $this->auth->userId(),
                'name' => post('name'),
                'price' => post('price'),
                // ... další fields
            ];
            
            if ($this->productModel->create($data)) {
                flash('success', 'Produkt byl vytvořen');
                redirect('/products.php');
            } else {
                flash('error', 'Nepodařilo se vytvořit produkt');
            }
        }
        
        $this->render('products/create', [
            'title' => 'Nový produkt'
        ]);
    }

    public function import(): void
    {
        $this->requireAuth();
        
        if ($this->validatePost()) {
            $url = post('xml_url');
            $userId = $this->auth->userId();
            
            $result = $this->productService->importFromXml($url, $userId);
            
            if ($result['success']) {
                flash('success', "Import dokončen: {$result['records']} záznamů");
            } else {
                flash('error', "Import selhal: {$result['error']}");
            }
            
            redirect('/products.php');
        }
    }
}
```

### 4. View (Presentation)

```php
<!-- views/products/index.php -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Produkty</h2>
    <a href="/products-create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>
        Nový produkt
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Název</th>
                    <th>Cena</th>
                    <th>Sklad</th>
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product['name']) ?></td>
                    <td><?= formatPrice($product['price']) ?></td>
                    <td><?= number_format($product['stock']) ?> ks</td>
                    <td>
                        <a href="/products-edit.php?id=<?= $product['id'] ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

### 5. Page file (Entry point)

```php
<?php
// products.php

require_once __DIR__ . '/bootstrap.php';

use App\Modules\Products\Controllers\ProductController;

$controller = new ProductController();
$controller->index();
```

## 🔌 Registrace modulu v menu

Upravte `views/layouts/main.php` a přidejte odkaz do sidebaru:

```php
<li class="nav-item">
    <a class="nav-link <?= ($_SERVER['PHP_SELF'] ?? '') === '/products.php' ? 'active' : '' ?>" 
       href="/products.php">
        <i class="bi bi-box-seam"></i>
        <span>Produkty</span>
    </a>
</li>
```

## 🗄️ Databázové migrace

Vytvořte migrační soubor pro novou tabulku:

```sql
-- database/migrations/2026_02_15_create_products_table.sql

CREATE TABLE `products` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(500) NOT NULL,
    `price` DECIMAL(12,2) NULL,
    `stock` INT DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 📦 Příklad komplexního modulu

### Modul "Objednávky"

**1. Struktura:**
```
src/Modules/Orders/
├── Controllers/
│   └── OrderController.php
├── Models/
│   ├── Order.php
│   └── OrderItem.php
├── Services/
│   ├── OrderService.php
│   └── InvoiceService.php
└── Views/
    ├── index.php
    ├── detail.php
    └── create.php
```

**2. Soubory stránek:**
```
orders.php          -> seznam objednávek
orders-detail.php   -> detail objednávky
orders-create.php   -> nová objednávka
```

**3. Využití Services:**
```php
// OrderService - business logika
- createOrder()
- updateOrderStatus()
- calculateTotal()
- sendOrderEmail()

// InvoiceService - generování faktur
- generateInvoice()
- sendInvoice()
- downloadPdf()
```

## 🎨 Custom CSS/JS pro modul

Vytvořte `assets/css/products.css`:
```css
.product-card {
    border-radius: 12px;
    transition: transform 0.2s;
}

.product-card:hover {
    transform: translateY(-4px);
}
```

A přidejte do view:
```php
<?php
$extraStyles = '<link rel="stylesheet" href="/assets/css/products.css">';
?>
```

## 🔐 Oprávnění v modulech

```php
class ProductController extends Module
{
    public function index(): void
    {
        // Vyžaduje přihlášení
        $this->requireAuth();
        
        // Pokud potřebujete admin
        // $this->requireAdmin();
        
        // Vlastní kontrola
        if (!$this->canViewProducts()) {
            flash('error', 'Nemáte oprávnění');
            redirect('/dashboard.php');
        }
    }
    
    private function canViewProducts(): bool
    {
        // Custom logika oprávnění
        return true;
    }
}
```

## ✅ Checklist pro nový modul

- [ ] Vytvořit strukturu složek v `src/Modules/NazevModulu/`
- [ ] Vytvořit Model v `Models/`
- [ ] Vytvořit Service v `Services/` (pokud je business logika)
- [ ] Vytvořit Controller v `Controllers/`
- [ ] Vytvořit Views v `views/nazevmodulu/`
- [ ] Vytvořit page soubory v kořeni (`modul.php`)
- [ ] Přidat do menu v `views/layouts/main.php`
- [ ] Vytvořit databázové tabulky (pokud potřeba)
- [ ] Přidat testy (budoucnost)
- [ ] Dokumentovat API (pokud je)

## 🚀 Best Practices

1. **Separation of concerns** - Model = data, Service = logika, Controller = flow, View = prezentace
2. **Používat připravené třídy** - Database, Auth, Security, Logger
3. **Validace na všech úrovních** - Model validuje data, Controller validuje requests
4. **Error handling** - Try-catch bloky, logování chyb
5. **Security first** - CSRF tokeny, XSS ochrana, validace vstupů
6. **Reusable components** - Vytvářet helper funkce pro opakující se kód
7. **Documentation** - Komentovat složitější logiku

## 📚 Další zdroje

- `src/Core/` - Core komponenty k použití
- `src/Models/User.php` - Příklad model třídy
- `src/helpers.php` - Užitečné helper funkce
- `views/layouts/main.php` - Hlavní layout

---

**Happy coding! 🎉**
