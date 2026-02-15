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
├── config/                 # Konfigurace
│   ├── app.php
│   └── database.php
├── database/               # SQL schémata
│   └── schema.sql
├── public/                 # Veřejné soubory (web root)
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── users.php
│   ├── forgot-password.php
│   └── reset-password.php
├── src/                    # Zdrojové kódy
│   ├── Core/              # Jádro systému
│   │   ├── Autoloader.php
│   │   ├── Auth.php
│   │   ├── Database.php
│   │   ├── Logger.php
│   │   └── Security.php
│   ├── Models/            # Datové modely
│   │   └── User.php
│   ├── Services/          # Business logika
│   ├── Controllers/       # Controllery
│   ├── Middleware/        # Middleware
│   ├── Validators/        # Validátory
│   └── helpers.php        # Helper funkce
├── storage/               # Úložiště
│   ├── logs/             # Aplikační logy
│   ├── cache/            # Cache
│   └── sessions/         # Session soubory
└── views/                # View šablony
    └── layouts/
        └── main.php      # Hlavní layout

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

**Varianta A: FTP Upload**
```bash
# 1. Nahrajte celou složku na server
# 2. Nastavte web root na: /public
```

**Varianta B: Git Clone**
```bash
git clone https://github.com/mhrncal/aplikace-howyouinshit.git
cd aplikace-howyouinshit
```

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

**Apache (.htaccess v public/):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 7. První přihlášení

```
URL: https://vase-domena.cz
Email: infoshopcode.cz
Heslo: Shopcode2024??
```

**⚠️ DŮLEŽITÉ:** Po prvním přihlášení změňte heslo!

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
