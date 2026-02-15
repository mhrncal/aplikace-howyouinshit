<?php
/**
 * Test hashe hesla - Spusť tento soubor v prohlížeči
 * URL: https://vase-domena.cz/test-hash.php
 */

require_once __DIR__ . '/bootstrap.php';

use App\Core\Security;
use App\Core\Database;

echo "<h2>🔐 Test Hash Hesla</h2>";
echo "<hr>";

$password = 'Shopcode2024??';
$email = 'info@shopcode.cz';

// Vygeneruj nový hash
$newHash = Security::hashPassword($password);

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<strong>Heslo:</strong> <code>{$password}</code><br>";
echo "<strong>Email:</strong> <code>{$email}</code><br><br>";
echo "<strong>Nový hash:</strong><br>";
echo "<textarea style='width: 100%; height: 100px; font-family: monospace; font-size: 11px;'>{$newHash}</textarea>";
echo "</div>";

// Test verify
if (password_verify($password, $newHash)) {
    echo "<p style='color: green;'>✅ Nový hash funguje správně!</p>";
} else {
    echo "<p style='color: red;'>❌ Nový hash nefunguje!</p>";
}

echo "<hr>";

// Zkontroluj databázi
$db = Database::getInstance();
$user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);

if ($user) {
    echo "<h3>Uživatel v databázi:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>";
    echo "<strong>ID:</strong> {$user['id']}<br>";
    echo "<strong>Email:</strong> {$user['email']}<br>";
    echo "<strong>Jméno:</strong> {$user['name']}<br>";
    echo "<strong>Super Admin:</strong> " . ($user['is_super_admin'] ? 'Ano' : 'Ne') . "<br>";
    echo "<strong>Aktivní:</strong> " . ($user['is_active'] ? 'Ano' : 'Ne') . "<br><br>";
    echo "<strong>Aktuální hash v DB:</strong><br>";
    echo "<textarea style='width: 100%; height: 100px; font-family: monospace; font-size: 11px;'>{$user['password']}</textarea>";
    echo "</div>";
    
    // Test aktuálního hashe
    if (password_verify($password, $user['password'])) {
        echo "<p style='color: green;'>✅ Hash v databázi je správný! Přihlášení by mělo fungovat.</p>";
    } else {
        echo "<p style='color: red;'>❌ Hash v databázi je ŠPATNÝ! Je potřeba ho aktualizovat.</p>";
        
        echo "<h3>🔧 Oprava:</h3>";
        echo "<p>Chceš aktualizovat hash v databázi? <a href='?fix_hash=1' style='color: blue; font-weight: bold;'>ANO, OPRAVIT</a></p>";
        
        if (isset($_GET['fix_hash'])) {
            $db->update('users', [
                'password' => $newHash,
                'email' => $email
            ], 'id = ?', [$user['id']]);
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
            echo "<strong>✅ Hash byl úspěšně aktualizován!</strong><br><br>";
            echo "Přihlašovací údaje:<br>";
            echo "Email: <code>{$email}</code><br>";
            echo "Heslo: <code>{$password}</code><br><br>";
            echo "<a href='/login.php' style='color: #155724; font-weight: bold;'>→ Přejít na přihlášení</a>";
            echo "</div>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Uživatel s emailem <strong>{$email}</strong> nebyl v databázi nalezen!</p>";
    echo "<p>Chceš vytvořit Super Admin účet? <a href='?create_user=1' style='color: blue; font-weight: bold;'>ANO, VYTVOŘIT</a></p>";
    
    if (isset($_GET['create_user'])) {
        $userId = $db->insert('users', [
            'name' => 'Super Admin',
            'email' => $email,
            'password' => $newHash,
            'is_super_admin' => true,
            'is_active' => true,
            'company_name' => 'Shopcode'
        ]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
        echo "<strong>✅ Super Admin byl vytvořen!</strong><br><br>";
        echo "Přihlašovací údaje:<br>";
        echo "Email: <code>{$email}</code><br>";
        echo "Heslo: <code>{$password}</code><br><br>";
        echo "<a href='/login.php' style='color: #155724; font-weight: bold;'>→ Přejít na přihlášení</a>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><strong>⚠️ BEZPEČNOST:</strong> Po dokončení SMAŽ tento test-hash.php soubor!</p>";
echo "<p style='color: #666;'><small>E-shop Analytics v2.0</small></p>";
