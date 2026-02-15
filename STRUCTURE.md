# 📁 STRUKTURA PROJEKTU

## 🗂️ Kořenová struktura

```
/
├── index.php              # Entry point (redirect na dashboard/login)
├── login.php              # Přihlášení
├── bootstrap.php          # Inicializace aplikace
├── .htaccess              # Apache konfigurace
├── .gitignore             # Git ignore
│
├── app/                   # 🎯 APLIKAČNÍ MODULY (18 souborů)
│   ├── auth/             # Autentizace (3)
│   ├── dashboard/        # Dashboard (1)
│   ├── users/            # Uživatelé (3)
│   ├── settings/         # Nastavení (1)
│   ├── costs/            # Náklady (4)
│   ├── products/         # Produkty (1)
│   ├── feed-sources/     # Feed zdroje (4)
│   └── import-logs/      # Import logy (1)
│
├── src/                   # PHP třídy
│   ├── Core/             # Jádro (Auth, Database, Security, Logger, Module)
│   ├── Models/           # Modely (User, Cost)
│   └── Modules/          # Modulární systém
│       ├── Products/     # Product Model, Controller, XmlImportService
│       └── FeedSources/  # FeedSource Model
│
├── views/                 # Šablony
│   ├── layouts/          # Layout (main.php)
│   ├── products/         # Product views (index, detail)
│   └── feed-sources/     # Feed sources views
│
├── config/                # Konfigurace
│   ├── app.php           # App config
│   └── database.php      # DB credentials
│
├── database/              # SQL
│   ├── schema.sql        # Databázové schéma
│   └── costs_migration.sql  # Migrace pro costs
│
├── assets/                # Statické soubory
│   ├── css/
│   ├── js/
│   └── images/
│
├── storage/               # Storage (logy, cache, sessions)
│   ├── logs/
│   ├── cache/
│   └── sessions/
│
└── docs/                  # Dokumentace
    ├── README.md
    ├── MODULES.md
    ├── NEXT_STEPS.md
    └── STATUS.md
```

---

## 📦 APP/ - Detailní struktura

### 1. AUTH (3 soubory)
```
app/auth/
├── logout.php              # Odhlášení
├── forgot-password.php     # Zapomenuté heslo
└── reset-password.php      # Reset hesla
```

### 2. DASHBOARD (1 soubor)
```
app/dashboard/
└── index.php               # Hlavní dashboard
```

### 3. USERS (3 soubory)
```
app/users/
├── index.php               # Seznam uživatelů
├── create.php              # Nový uživatel
└── edit.php                # Úprava uživatele
```

### 4. SETTINGS (1 soubor)
```
app/settings/
└── profile.php             # Můj profil + změna hesla
```

### 5. COSTS (4 soubory)
```
app/costs/
├── index.php               # Seznam nákladů
├── create.php              # Nový náklad
├── edit.php                # Úprava nákladu
└── analytics.php           # Analytika (roční/měsíční)
```

### 6. PRODUCTS (1 soubor)
```
app/products/
└── index.php               # Seznam produktů + routing
```

### 7. FEED SOURCES (4 soubory)
```
app/feed-sources/
├── index.php               # Seznam feed zdrojů
├── create.php              # Nový feed
├── edit.php                # Úprava feedu
└── import-now.php          # Manuální import
```

### 8. IMPORT LOGS (1 soubor)
```
app/import-logs/
└── index.php               # Historie importů
```

---

## 🎯 CELKEM: 18 PHP souborů v app/

✅ Všechny funkční  
✅ Všechny v Gitu  
✅ Čistá struktura  
✅ Žádné duplicity  

---

**Vytvořeno:** 15.02.2026  
**Verze:** 2.0 Final  
**Status:** ✅ Production Ready
