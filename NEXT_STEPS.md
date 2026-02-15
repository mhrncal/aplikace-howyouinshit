# 📋 DALŠÍ KROKY VÝVOJE

## ✅ CO JE HOTOVO

### 🏗️ Struktura
```
/ (kořen)
├── index.php           # Entry point
├── login.php           # Přihlášení
├── bootstrap.php       # Inicializace
├── app/                # Aplikační moduly
│   ├── auth/          # Autentizace
│   │   ├── logout.php
│   │   ├── forgot-password.php
│   │   └── reset-password.php
│   ├── dashboard/     # Dashboard
│   │   └── index.php
│   ├── users/         # Správa uživatelů
│   ├── products/      # Produkty
│   ├── settings/      # Nastavení
│   └── costs/         # Náklady (připraveno)
├── src/               # PHP třídy
├── views/             # Šablony
├── config/            # Konfigurace
└── database/          # SQL

```

### ✅ Funkční moduly
- [x] Přihlášení/Odhlášení
- [x] Reset hesla
- [x] Dashboard se statistikami
- [x] Core systém (Auth, Database, Security, Logger)
- [x] User model s validací
- [x] Products model
- [x] XML Import Service

## 🚀 CO DODĚL AT

### 1️⃣ PRIORITA VYSOKÁ - Základní CRUD

#### Users modul
- [ ] `/app/users/index.php` - Seznam uživatelů
- [ ] `/app/users/create.php` - Vytvoření uživatele
- [ ] `/app/users/edit.php?id=X` - Úprava uživatele
- [ ] Mazání uživatele (POST akce)

#### Products modul  
- [ ] `/app/products/index.php` - Seznam produktů
- [ ] `/app/products/detail.php?id=X` - Detail produktu + varianty
- [ ] Export CSV
- [ ] Filtrace a vyhledávání

#### Settings modul
- [ ] `/app/settings/profile.php` - Úprava profilu
- [ ] `/app/settings/password.php` - Změna hesla
- [ ] `/app/settings/company.php` - Firemní údaje

### 2️⃣ PRIORITA STŘEDNÍ - Feed Sources & Import

#### Feed Sources modul
- [ ] `/app/feed-sources/index.php` - Seznam feedů
- [ ] `/app/feed-sources/create.php` - Nový feed
- [ ] `/app/feed-sources/edit.php?id=X` - Úprava feedu
- [ ] `/app/feed-sources/import-now.php?id=X` - Manuální import
- [ ] Aktivace/deaktivace feedu

#### Import Logs
- [ ] `/app/import-logs/index.php` - Historie importů
- [ ] `/app/import-logs/detail.php?id=X` - Detail importu

### 3️⃣ PRIORITA NÍZKÁ - Rozšíření

#### Costs modul (Náklady)
- [ ] `/app/costs/index.php` - Seznam nákladů
- [ ] `/app/costs/create.php` - Nový náklad
- [ ] `/app/costs/edit.php?id=X` - Úprava nákladu
- [ ] Kategorie nákladů (fixní, variabilní)
- [ ] Přiřazení k obdobím

#### Analytics modul
- [ ] `/app/analytics/products.php` - Analytics produktů
- [ ] `/app/analytics/margins.php` - Marže
- [ ] `/app/analytics/regions.php` - Regionální analýzy
- [ ] Grafy (Chart.js)

#### API
- [ ] `/api/products.php` - REST API pro produkty
- [ ] `/api/import.php` - Webhook pro importy
- [ ] API dokumentace

## 🗄️ DATABÁZOVÉ ZMĚNY

### Costs tabulka
```sql
CREATE TABLE `costs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `type` ENUM('fixed','variable') DEFAULT 'fixed',
    `category` VARCHAR(100) NULL,
    `period` DATE NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_period` (`user_id`, `period`)
);
```

### Product varianty - zobrazení
- Přidat indikátor "má varianty" v seznamu produktů
- Detail produktu zobrazuje tabulku variant
- Export zahrnuje i varianty

## 🎨 UI VYLEPŠENÍ

### Obecné
- [ ] Breadcrumbs navigace
- [ ] Pokročilé filtry (datum, kategorie, status)
- [ ] Bulk akce (hromadné mazání, export)
- [ ] Paginace - zobrazit aktuální stránku/celkem
- [ ] Loading states při AJAX operacích

### Dashboard
- [ ] Grafy (prodeje, importy, růst)
- [ ] Widget rychlého importu
- [ ] Poslední chyby/upozornění

## 🔒 BEZPEČNOST & OPTIMALIZACE

### Bezpečnost
- [ ] Rate limiting pro API
- [ ] Audit log všech změn
- [ ] IP whitelist pro importy
- [ ] 2FA autentizace (volitelně)

### Výkon
- [ ] Cache layer (Redis/Memcached)
- [ ] Optimalizace SQL dotazů (indexy)
- [ ] Lazy loading obrázků
- [ ] CDN pro assets

## 📱 MOBILNÍ VERZE

- [ ] Responzivní menu (hamburger)
- [ ] Touch-friendly ovládání
- [ ] PWA manifest
- [ ] Offline mode (Service Worker)

## 📚 DOKUMENTACE

- [ ] Uživatelská příručka
- [ ] API dokumentace
- [ ] Video tutoriály
- [ ] FAQ sekce

## 🧪 TESTOVÁNÍ

- [ ] Unit testy (PHPUnit)
- [ ] Integration testy
- [ ] End-to-end testy (Playwright)
- [ ] Performance testy

## 🔄 CI/CD

- [ ] GitHub Actions workflow
- [ ] Automatické testy při push
- [ ] Deployment script
- [ ] Database migrace systém

---

## 🎯 DOPORUČENÝ POSTUP

### Týden 1 - Základy
1. Dodělat Users CRUD
2. Dodělat Products seznam a detail
3. Settings - profil a heslo

### Týden 2 - Import
1. Feed Sources CRUD
2. Manuální import
3. Import logs

### Týden 3 - Rozšíření
1. Costs modul
2. Varianty produktů
3. Export CSV

### Týden 4 - Polish
1. UI vylepšení
2. Optimalizace
3. Dokumentace

---

**Vytvořeno:** 15.02.2026  
**Verze:** 2.0  
**Status:** V aktivním vývoji
