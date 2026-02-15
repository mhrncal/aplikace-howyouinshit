<?php
/**
 * Setup skript - Vytvoří Super Admin uživatele s platným heslem
 * Spustit pouze jednou po instalaci!
 */

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\Security;

$db = Database::getInstance();

$email = 'info@shopcode.cz';
$password = 'Shopcode2024??';

echo "<h2>🚀 E-shop Analytics - Setup</h2>";
echo "<hr>";

// Test připojení k DB
try {
    $db->getConnection();
    echo "<p style='color: green;'>✅ Připojení k databázi OK</p>";
} catch (\Exception $e) {
    echo "<p style='color: red;'>❌ Chyba připojení k databázi: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Kontrola, zda super admin už existuje
$existing = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

if ($existing) {
    echo "<p>ℹ️ Uživatel s emailem <strong>{$email}</strong> již existuje (ID: {$existing['id']})</p>";
    
    // Test aktuálního hesla
    if (Security::verifyPassword($password, $existing['password'])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
        echo "<strong>✅ Heslo je správné! Můžete se přihlásit.</strong><br><br>";
        echo "Email: <code>{$email}</code><br>";
        echo "Heslo: <code>{$password}</code><br><br>";
        echo "<a href='/login.php' style='color: #155724; font-weight: bold;'>→ Přejít na přihlášení</a>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ Hash hesla v databázi je neplatný nebo neodpovídá heslu <code>{$password}</code></p>";
        echo "<p>Chcete resetovat heslo? <a href='?reset_password=1' style='color: blue; font-weight: bold;'>ANO, RESETOVAT HESLO</a></p>";
    }
    
    if (isset($_GET['reset_password'])) {
        // Vygeneruj nový hash
        $newHash = Security::hashPassword($password);
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<strong>Resetuji heslo...</strong><br>";
        echo "Nový hash: <code style='font-size: 10px; word-break: break-all;'>{$newHash}</code>";
        echo "</div>";
        
        $db->update('users', [
            'password' => $newHash,
            'email' => $email
        ], 'id = ?', [$existing['id']]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
        echo "<strong>✅ Heslo bylo úspěšně resetováno!</strong><br><br>";
        echo "<strong>Přihlašovací údaje:</strong><br>";
        echo "Email: <code>{$email}</code><br>";
        echo "Heslo: <code>{$password}</code><br><br>";
        echo "<a href='/login.php' style='color: #155724; font-weight: bold;'>→ Přejít na přihlášení</a>";
        echo "</div>";
        
        echo "<p><strong>⚠️ BEZPEČNOST:</strong> Smažte tento setup.php soubor po dokončení!</p>";
    }
    
} else {
    echo "<p>📝 Uživatel neexistuje. Vytvářím Super Admin účet...</p>";
    
    // Vytvoření Super Admin uživatele
    $newHash = Security::hashPassword($password);
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<strong>Generuji hash hesla...</strong><br>";
    echo "Hash: <code style='font-size: 10px; word-break: break-all;'>{$newHash}</code>";
    echo "</div>";
    
    try {
        $userId = $db->insert('users', [
            'name' => 'Super Admin',
            'email' => $email,
            'password' => $newHash,
            'is_super_admin' => true,
            'is_active' => true,
            'company_name' => 'Shopcode',
        ]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
        echo "<strong>✅ Super Admin byl úspěšně vytvořen! (ID: {$userId})</strong><br><br>";
        echo "<strong>Přihlašovací údaje:</strong><br>";
        echo "Email: <code>{$email}</code><br>";
        echo "Heslo: <code>{$password}</code><br><br>";
        echo "<a href='/login.php' style='color: #155734; font-weight: bold;'>→ Přejít na přihlášení</a>";
        echo "</div>";
        
        echo "<p><strong>⚠️ BEZPEČNOST:</strong></p>";
        echo "<ul>";
        echo "<li>Po prvním přihlášení změňte heslo!</li>";
        echo "<li>Smažte tento setup.php soubor!</li>";
        echo "</ul>";
        
    } catch (\Exception $e) {
        echo "<p style='color: red;'>❌ Chyba při vytváření uživatele: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr>";
echo "<p style='color: #666;'><small>E-shop Analytics v2.0</small></p>";
