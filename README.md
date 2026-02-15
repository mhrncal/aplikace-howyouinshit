# 🚀 E-shop Analytics Platform v2.0

Moderní, rychlá a bezpečná platforma pro analýzu e-shopů postavená na **PHP 8.2+** bez závislostí.

## ✨ Features

### 🔐 Autentizace & Bezpečnost
- ✅ Přihlašování s Argon2id hashováním
- ✅ Reset hesla emailem
- ✅ CSRF ochrana na všech formulářích
- ✅ Rate limiting proti brute-force útokům
- ✅ Session timeout (2 hodiny)
- ✅ XSS ochrana
- ✅ Validace IČO (česká norma)

### 👥 Správa uživatelů
- ✅ Super Admin & běžný uživatel
- ✅ Multi-tenant architektura
- ✅ CRUD operace uživatelů
- ✅ Aktivace/deaktivace účtů
- ✅ Kompletní profil (IČO, DIČ, firma, adresa)
- ✅ Admin může vidět všechny uživatele a jejich data

### 📊 Funkce platformy
- ✅ Import produktů z XML/CSV feedů
- ✅ Automatický cron import
- ✅ Multi-tenant - každý uživatel vidí svá data
- ✅ Dashboard s statistikami
- ✅ Produkty s variantami
- ✅ Feed sources management
- ✅ Import logs & audit trail

### 🎨 UI/UX
- ✅ Moderní Bootstrap 5 design
- ✅ Gradientové karty
- ✅ Responzivní sidebar
- ✅ Flash messages
- ✅ Plynulé animace
- ✅ Ikony Bootstrap Icons

## 🏗️ Architektura

### Struktura projektu
```
aplikace-howyouinshit/
├── bootstrap.php           # Inicializace aplikace
├── index.php               # Hlavní stránka
├── login.php               # Přihlášení
├── dashboard.php           # Dashboard
├── users.php               # Správa uživatelů
├── *.php                   # Další stránky modulů
├── .htaccess              # Apache konfigurace
├── config/                # Konfigurace
│   ├── app.php
│   └── database.php
├── database/              # SQL schémata & migrace
│   └── schema.sql
├── src/                   # Zdrojové kódy
│   ├── Core/             # Jádro systému
│   │   ├── Autoloader.php
│   │   ├── Auth.php
│   │   ├── Database.php
│   │   ├── Logger.php
│   │   ├── Security.php
│   │   └── Module.php    # Base třída pro moduly
│   ├── Models/           # Datové modely
│   │   └── User.php
│   ├── Modules/          # Aplikační moduly
│   │   └── NazevModulu/
│   │       ├── Controllers/
│   │       ├── Models/
│   │       ├── Services/
│   │       └── Views/
│   └── helpers.php       # Helper funkce
├── storage/              # Úložiště
│   ├── logs/            # Aplikační logy
│   ├── cache/           # Cache
│   └── sessions/        # Session soubory
├── views/               # View šablony
│   └── layouts/
│       └── main.php     # Hlavní layout
├── assets/              # Statické soubory
│   ├── css/
│   ├── js/
│   └── images/
├── README.md            # Tato dokumentace
└── MODULES.md          # Průvodce tvorbou modulů

```

### Technologie
- **PHP 8.2+** - Moderní PHP s typed properties, enums
- **MySQL 8** - Databáze
- **PDO** - Database abstrakce s prepared statements
- **Bootstrap 5.3** - UI framework
- **Bootstrap Icons** - Ikonky
- **PSR-4 Autoloading** - Vlastní autoloader bez Composeru

## 📦 Instalace

### 1. Požadavky
- PHP 8.2 nebo vyšší
- MySQL 8.0+
- Apache/Nginx web server

### 2. Nahrání na server

**Varianta A: FTP Upload (doporučeno pro hosting)**
```bash
# 1. Nahrajte celou složku aplikace-howyouinshit/ na server
# 2. Kořenová složka je WEB ROOT (ne podsložka public/)
# 3. Nastavte oprávnění pro storage/
```

**Varianta B: Git Clone**
```bash
git clone https://github.com/mhrncal/aplikace-howyouinshit.git
cd aplikace-howyouinshit
chmod -R 755 storage/
```

**⚠️ DŮLEŽITÉ:** 
- **Kořen projektu = Web root** (ne podsložka!)
- `.htaccess` chrání citlivé složky (config, src, storage)
- Pro FTP hosting prostě nahrajte vše do veřejné složky (public_html, www, htdocs...)

### 3. Konfigurace

**Database:**
Upravte `config/database.php`:
```php
return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'vas_database',
    'username' => 'vas_uzivatel',
    'password' => 'vase_heslo',
];
```

### 4. Databáze

Importujte schéma:
```bash
mysql -u username -p database_name < database/schema.sql
```

Nebo v phpMyAdmin importujte soubor `database/schema.sql`

### 5. Oprávnění

```bash
chmod -R 755 storage/
chmod -R 755 public/
```

### 6. Web server

**✅ Kořen projektu = Web root**

.htaccess automaticky chrání citlivé složky a zajišťuje routing.

**Apache:**
- Ujistěte se, že `mod_rewrite` je zapnutý
- `.htaccess` již obsažen v projektu

**Nginx (pokud nepoužíváte Apache):**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/aplikace-howyouinshit;
    index index.php;

    # Block access to sensitive folders
    location ~ ^/(config|src|storage|database|views)/ {
        deny all;
        return 404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 7. První přihlášení

```
URL: https://vase-domena.cz
Email: infoshopcode.cz
Heslo: Shopcode2024??
```

**⚠️ DŮLEŽITÉ:** Po prvním přihlášení změňte heslo v profilu!

**Default Super Admin účet:**
- Má přístup ke všem funkcím
- Vidí data všech uživatelů  
- Může vytvářet/upravovat/mazat uživatele
- Může spravovat všechny feed zdroje

## 🔒 Bezpečnost

### Best practices implementované:
- ✅ Argon2id password hashing
- ✅ CSRF tokeny na všech formulářích
- ✅ Prepared statements (SQL injection ochrana)
- ✅ XSS ochrana (htmlspecialchars)
- ✅ Rate limiting
- ✅ Session security (httponly, secure cookies)
- ✅ Input validace
- ✅ Bezpečné session regeneration po přihlášení

### Doporučení:
1. Používejte HTTPS v produkci
2. Pravidelně aktualizujte hesla
3. Zálohujte databázi
4. Sledujte logy v `storage/logs/`

## 🚀 Výkon

### Optimalizace:
- ✅ PDO persistent connections
- ✅ Optimalizované databázové indexy
- ✅ Singleton pattern pro DB
- ✅ Buffered queries
- ✅ Batch insert/update operace
- ✅ Stream processing pro velké XML soubory

### Tipy pro produkci:
1. Zapněte OPcache v PHP
2. Použijte Redis/Memcached pro sessions
3. Optimalizujte MySQL (innodb_buffer_pool_size)
4. Nastavte proper caching headers

## 🧩 Modularita & Rozšiřitelnost

Aplikace je navržena tak, aby byla snadno rozšiřitelná o nové moduly.

### Jak přidat nový modul?

Podrobný průvodce najdete v **[MODULES.md](MODULES.md)**

**Rychlý start:**

1. **Vytvořte strukturu modulu:**
```
src/Modules/NazevModulu/
├── Controllers/NazevController.php
├── Models/NazevModel.php
├── Services/NazevService.php (volitelné)
└── Views/
```

2. **Vytvořte Controller děděním z Module:**
```php
use App\Core\Module;

class ProductController extends Module
{
    public function index(): void
    {
        $this->requireAuth();
        $this->render('products/index', ['title' => 'Produkty']);
    }
}
```

3. **Vytvořte page soubor:**
```php
// products.php
require_once __DIR__ . '/bootstrap.php';
use App\Modules\Products\Controllers\ProductController;

$controller = new ProductController();
$controller->index();
```

4. **Přidejte do menu** v `views/layouts/main.php`

**Výhody modulárního systému:**
- ✅ Snadné přidávání funkcí
- ✅ Oddělené concerns (Model-Service-Controller-View)
- ✅ Znovupoužitelný kód
- ✅ Jednoduchá údržba
- ✅ Rychlý vývoj nových features

## 📚 Použití

### Přidání uživatele
1. Přihlaste se jako Super Admin
2. Jděte na "Uživatelé"
3. Klikněte "Přidat uživatele"
4. Vyplňte formulář (jméno, email, heslo, IČO, firma...)
5. Uložte

### Reset hesla
1. Jděte na login stránku
2. Klikněte "Zapomněli jste heslo?"
3. Zadejte email
4. Zkontrolujte `storage/logs/` pro reset link (v produkci by se poslal emailem)
5. Použijte link pro reset hesla

### Import produktů
1. Přidejte Feed source (URL k XML/CSV)
2. Nastavte schedule (hourly/daily/weekly)
3. Import běží automaticky přes cron
4. Nebo spusťte manuálně: "Spustit import"

## 🛠️ Development

### Helper funkce
```php
// Redirect
redirect('/dashboard.php');

// Flash messages
flash('success', 'Operace proběhla úspěšně');
flash('error', 'Něco se pokazilo');

// Escape output
echo e($userInput);

// Formát ceny
echo formatPrice(1234.56); // "1 234,56 Kč"

// Formát data
echo formatDate($date); // "15.02.2026 14:30"
```

### Logging
```php
use App\Core\Logger;

Logger::info('User logged in', ['user_id' => 123]);
Logger::warning('Invalid login attempt');
Logger::error('Database error', ['error' => $e->getMessage()]);
```

### Validace
```php
use App\Core\Security;

// Email
Security::validateEmail($email);

// Heslo
$errors = Security::validatePassword($password);

// IČO
Security::validateIco($ico);
```

## 📝 TODO / Roadmap

- [ ] Email služba (SMTP)
- [ ] CSV export
- [ ] API endpoints
- [ ] 2FA autentizace
- [ ] Pokročilé filtry produktů
- [ ] Analytics dashboard (grafy)
- [ ] Notifikace
- [ ] Role-based permissions (více rolí)

## 🐛 Troubleshooting

### Problém: Nelze se přihlásit
- Zkontrolujte database credentials v `config/database.php`
- Zkontrolujte, že schéma je importované
- Zkontrolujte logy v `storage/logs/`

### Problém: 404 na všech stránkách
- Zkontrolujte web server konfiguraci
- Ujistěte se, že web root ukazuje na `/public`
- Zkontrolujte `.htaccess` (Apache)

### Problém: Permission denied
```bash
chmod -R 755 storage/
chown -R www-data:www-data storage/
```

## 📄 Licence

Proprietární software. Všechna práva vyhrazena.

## 👨‍💻 Autor

Vytvořeno s ❤️ pro moderní e-shop analytics.

---

**Version:** 2.0  
**Last Updated:** 15.02.2026
