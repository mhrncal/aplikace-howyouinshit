# 📝 LOGGING SYSTÉM - Dokumentace

## ✨ NOVÉ FUNKCE:

### 1. **Kategorie logů**
Logy jsou rozdělené podle účelu:

```
storage/logs/
├── app-2026-02-15.log        # Obecná aplikace
├── import-2026-02-15.log     # XML/CSV importy
├── auth-2026-02-15.log       # Přihlášení, registrace
├── api-2026-02-15.log        # API volání
├── error-2026-02-15.log      # Chyby aplikace
├── cron-2026-02-15.log       # Cron joby
└── db-2026-02-15.log         # Databázové operace
```

### 2. **Automatická rotace**
- Když soubor přesáhne **10 MB**, automaticky se rotuje
- Starý: `import-2026-02-15.log`
- Nový: `import-2026-02-15-1739737200.log` (timestamp)

### 3. **Automatické čištění**
- Staré logy (30+ dní) se automaticky mažou
- Běží při inicializaci (1% šance)
- Nebo manuálně: `Logger::cleanOldLogs()`

---

## 📖 POUŽITÍ:

### Základní logging:

```php
use App\Core\Logger;

// Obecné logy (kategorie 'app')
Logger::info('Něco se stalo');
Logger::warning('Pozor!');
Logger::error('Chyba!');
Logger::debug('Debug info');

// S kontextem
Logger::info('User logged in', ['user_id' => 123]);

// S KATEGORIÍ
Logger::info('Import started', ['feed_id' => 1], 'import');
Logger::error('Auth failed', ['email' => 'test@test.cz'], 'auth');
Logger::debug('API request', ['endpoint' => '/api/products'], 'api');
```

### Kategorie:

| Kategorie | Použití |
|-----------|---------|
| `app` | Obecná aplikace (výchozí) |
| `import` | XML/CSV importy |
| `auth` | Přihlášení, registrace, reset hesla |
| `api` | API endpointy |
| `error` | Chyby aplikace |
| `cron` | Cron joby |
| `db` | Databázové operace |

---

## 🔧 POKROČILÉ FUNKCE:

### Smazat všechny logy:
```php
Logger::clearAll();
```

### Získat seznam log souborů:
```php
// Všechny logy
$logs = Logger::getLogFiles();

// Jen import logy
$logs = Logger::getLogFiles('import');

// Vrací:
[
    [
        'file' => 'import-2026-02-15.log',
        'path' => '/srv/app/storage/logs/import-2026-02-15.log',
        'size' => 1024567,
        'size_mb' => 0.98,
        'modified' => 1739737200,
        'modified_date' => '2026-02-15 18:30:00'
    ],
    ...
]
```

### Přečíst log soubor:
```php
// Poslední 100 řádků
$lines = Logger::readLog('import-2026-02-15.log');

// Poslední 500 řádků
$lines = Logger::readLog('import-2026-02-15.log', 500);
```

### Manuální čištění:
```php
// Smaž logy starší než 30 dní
Logger::cleanOldLogs();

// Smaž logy starší než 7 dní
Logger::cleanOldLogs(7);
```

---

## 💡 PŘÍKLADY POUŽITÍ:

### Import produktů:
```php
Logger::info('Starting product import', [
    'feed_id' => $feedId,
    'url' => $url
], 'import');

Logger::info('Products imported', [
    'count' => 1247,
    'duration' => 45
], 'import');
```

### Přihlášení uživatele:
```php
Logger::info('User login attempt', [
    'email' => $email,
    'ip' => $_SERVER['REMOTE_ADDR']
], 'auth');

Logger::info('User logged in', [
    'user_id' => $userId
], 'auth');
```

### Chyby:
```php
Logger::error('Database connection failed', [
    'host' => $host,
    'error' => $e->getMessage()
], 'db');

Logger::error('Payment failed', [
    'order_id' => $orderId,
    'amount' => $amount,
    'error' => $e->getMessage()
], 'error');
```

### Cron job:
```php
Logger::info('Cron: Daily import started', [], 'cron');

Logger::info('Cron: Imported 5 feeds', [
    'feeds' => [1, 2, 3, 4, 5],
    'duration' => 300
], 'cron');
```

---

## 🎯 VÝHODY:

✅ **Přehledné** - každá kategorie vlastní soubor  
✅ **Rychlé** - najdeš co potřebuješ okamžitě  
✅ **Úsporné** - automatická rotace a čištění  
✅ **Škálovatelné** - max 10 MB per soubor  
✅ **Flexibilní** - vlastní kategorie  

---

## 📊 VIEWER (budoucnost):

Můžeš vytvořit UI pro prohlížení logů:

```php
// /app/logs/viewer.php
$logs = Logger::getLogFiles();

foreach ($logs as $log) {
    echo "{$log['file']} - {$log['size_mb']} MB<br>";
    
    $lines = Logger::readLog($log['file'], 50);
    foreach ($lines as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
}
```

---

## 🔒 BEZPEČNOST:

- Logy jsou **MIMO** web root
- Přístup jen přes PHP (ne přímo)
- Automatické čištění starých dat
- Rotace velkých souborů

---

**Šťastné logování!** 📝✨
