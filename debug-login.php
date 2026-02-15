<?php
/**
 * DEBUG LOGIN - Diagnostika přihlášení
 * SMAŽ PO VYŘEŠENÍ PROBLÉMU!
 */

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\Security;

$db = Database::getInstance();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Login</title>";
echo "<style>body{font-family:sans-serif;padding:40px;max-width:900px;margin:0 auto;}";
echo ".box{background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;}";
echo ".success{background:#d4edda;color:#155724;}";
echo ".error{background:#f8d7da;color:#721c24;}";
echo ".warning{background:#fff3cd;color:#856404;}";
echo "code{background:#e9ecef;padding:2px 6px;border-radius:4px;font-family:monospace;}";
echo "pre{background:#e9ecef;padding:15px;border-radius:8px;overflow-x:auto;font-size:12px;}";
echo "</style></head><body>";

echo "<h1>🔍 Debug přihlášení</h1>";
echo "<hr>";

// Test 1: Připojení k databázi
echo "<h2>1️⃣ Test připojení k databázi</h2>";
try {
    $db->getConnection();
    echo "<div class='box success'>✅ Připojení k databázi funguje</div>";
} catch (\Exception $e) {
    echo "<div class='box error'>❌ Chyba připojení: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Test 2: Najdi uživatele
echo "<h2>2️⃣ Hledání uživatele v databázi</h2>";
$email = 'info@shopcode.cz';
$password = 'Shopcode2024??';

$user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

if ($user) {
    echo "<div class='box success'>✅ Uživatel nalezen</div>";
    echo "<div class='box'>";
    echo "<strong>Údaje z databáze:</strong><br>";
    echo "ID: <code>{$user['id']}</code><br>";
    echo "Email: <code>{$user['email']}</code><br>";
    echo "Jméno: <code>{$user['name']}</code><br>";
    echo "Super Admin: <code>" . ($user['is_super_admin'] ? 'ANO (1)' : 'NE (0)') . "</code><br>";
    echo "Aktivní: <code>" . ($user['is_active'] ? 'ANO (1)' : 'NE (0)') . "</code><br>";
    echo "Password hash:<br>";
    echo "<pre style='word-break:break-all;'>{$user['password']}</pre>";
    echo "</div>";
    
    if (!$user['is_active']) {
        echo "<div class='box error'>❌ PROBLÉM: Uživatel není aktivní! (is_active = 0)</div>";
    }
} else {
    echo "<div class='box error'>❌ Uživatel s emailem <code>{$email}</code> nebyl nalezen!</div>";
    echo "<p><a href='/test-hash.php?create_user=1'>→ Vytvořit uživatele</a></p>";
    exit;
}

// Test 3: Kontrola hashe
echo "<h2>3️⃣ Test hesla</h2>";

// Test podpory Argon2ID
echo "<div class='box'>";
echo "<strong>Server info:</strong><br>";
echo "PHP verze: <code>" . PHP_VERSION . "</code><br>";
echo "PASSWORD_ARGON2ID: <code>" . (defined('PASSWORD_ARGON2ID') ? 'Podporováno ✅' : 'NENÍ podporováno ❌') . "</code><br>";
echo "PASSWORD_BCRYPT: <code>Podporováno ✅</code>";
echo "</div>";

// Vygeneruj nový hash pro srovnání
try {
    $newHash = Security::hashPassword($password);
    
    echo "<div class='box'>";
    echo "<strong>Test heslo:</strong> <code>{$password}</code><br><br>";
    echo "<strong>Nově vygenerovaný hash (pro srovnání):</strong><br>";
    echo "<pre style='word-break:break-all;'>{$newHash}</pre>";
    echo "</div>";
} catch (\Exception $e) {
    echo "<div class='box error'>";
    echo "❌ <strong>CHYBA při generování hashe:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
    $newHash = null;
}

// Test password_verify s aktuálním hashem v DB
$verifyResult = password_verify($password, $user['password']);

if ($verifyResult) {
    echo "<div class='box success'>";
    echo "✅ <strong>password_verify() ÚSPĚŠNÝ!</strong><br>";
    echo "Hash v databázi odpovídá heslu <code>{$password}</code>";
    echo "</div>";
} else {
    echo "<div class='box error'>";
    echo "❌ <strong>password_verify() SELHAL!</strong><br>";
    echo "Hash v databázi NEODPOVÍDÁ heslu <code>{$password}</code><br><br>";
    echo "To znamená, že hash v databázi je špatný a musí se opravit.";
    echo "</div>";
    
    echo "<div class='box warning'>";
    echo "<strong>🔧 OPRAVA:</strong><br>";
    echo "<a href='?fix_hash=1' style='color:#856404;font-weight:bold;font-size:16px;'>→ KLIKNI SEM PRO OPRAVU HASHE</a>";
    echo "</div>";
}

// Oprava hashe
if (isset($_GET['fix_hash']) && $user && $newHash) {
    echo "<h2>4️⃣ Oprava hashe</h2>";
    
    $db->update('users', [
        'password' => $newHash,
        'is_active' => 1
    ], 'id = ?', [$user['id']]);
    
    echo "<div class='box success'>";
    echo "<strong>✅ Hash byl aktualizován!</strong><br><br>";
    echo "Nový hash uložen do databáze.<br>";
    echo "Uživatel nastaven jako aktivní (is_active = 1).<br><br>";
    echo "<strong>Přihlašovací údaje:</strong><br>";
    echo "Email: <code>{$email}</code><br>";
    echo "Heslo: <code>{$password}</code><br><br>";
    echo "<a href='/login.php' style='color:#155724;font-weight:bold;font-size:16px;'>→ PŘEJÍT NA PŘIHLÁŠENÍ</a>";
    echo "</div>";
    
    // Test po opravě
    $userAfter = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    $verifyAfter = password_verify($password, $userAfter['password']);
    
    echo "<div class='box'>";
    echo "<strong>Kontrola po opravě:</strong><br>";
    echo "password_verify(): " . ($verifyAfter ? "<span style='color:green;'>✅ FUNGUJE</span>" : "<span style='color:red;'>❌ NEFUNGUJE</span>") . "<br>";
    echo "is_active: " . ($userAfter['is_active'] ? "<span style='color:green;'>✅ ANO</span>" : "<span style='color:red;'>❌ NE</span>");
    echo "</div>";
} elseif (isset($_GET['fix_hash']) && !$newHash) {
    echo "<div class='box error'>";
    echo "❌ Nelze opravit hash - generování hashe selhalo. Kontaktujte administrátora serveru.";
    echo "</div>";
}

// Test 4: Simulace přihlášení
echo "<h2>4️⃣ Simulace přihlášení</h2>";

if ($verifyResult && $user['is_active']) {
    echo "<div class='box success'>";
    echo "<strong>✅ VŠE JE V POŘÁDKU!</strong><br><br>";
    echo "Hash funguje, uživatel je aktivní.<br>";
    echo "Přihlášení by mělo fungovat.<br><br>";
    echo "<strong>Přihlašovací údaje:</strong><br>";
    echo "Email: <code>{$email}</code><br>";
    echo "Heslo: <code>{$password}</code><br><br>";
    echo "<a href='/login.php' style='color:#155724;font-weight:bold;font-size:16px;'>→ ZKUSIT PŘIHLÁŠENÍ</a>";
    echo "</div>";
} else {
    $problems = [];
    if (!$verifyResult) $problems[] = "Hash hesla je špatný";
    if (!$user['is_active']) $problems[] = "Uživatel není aktivní";
    
    echo "<div class='box error'>";
    echo "<strong>❌ PROBLÉMY:</strong><br>";
    echo "<ul>";
    foreach ($problems as $problem) {
        echo "<li>{$problem}</li>";
    }
    echo "</ul>";
    echo "<a href='?fix_hash=1' style='color:#721c24;font-weight:bold;'>→ OPRAVIT VŠE</a>";
    echo "</div>";
}

// Test 5: Rate limiting
echo "<h2>5️⃣ Rate limiting kontrola</h2>";
$sessionKey = "rate_limit_login_{$email}";
if (isset($_SESSION[$sessionKey])) {
    $data = $_SESSION[$sessionKey];
    echo "<div class='box warning'>";
    echo "<strong>⚠️ Rate limit info:</strong><br>";
    echo "Počet pokusů: <code>{$data['attempts']}</code><br>";
    echo "Reset za: <code>" . ($data['reset_at'] - time()) . " sekund</code><br>";
    if ($data['attempts'] >= 5) {
        echo "<br><strong>❌ Příliš mnoho pokusů! Počkejte nebo:</strong><br>";
        echo "<a href='?clear_rate_limit=1'>→ Vynulovat počítadlo</a>";
    }
    echo "</div>";
} else {
    echo "<div class='box success'>✅ Žádné rate limiting problémy</div>";
}

if (isset($_GET['clear_rate_limit'])) {
    unset($_SESSION[$sessionKey]);
    echo "<div class='box success'>✅ Rate limit vynulován! <a href='?'>Obnovit stránku</a></div>";
}

echo "<hr>";
echo "<p><strong>⚠️ BEZPEČNOST:</strong> Po vyřešení problému <strong>SMAŽTE tento debug-login.php soubor!</strong></p>";
echo "<p style='color:#666;'><small>E-shop Analytics v2.0 - Debug Mode</small></p>";

echo "</body></html>";
