<?php
/**
 * Setup skript - Vytvoří Super Admin uživatele s platným heslem
 * Spustit pouze jednou po instalaci!
 */

require_once __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\Security;

$db = Database::getInstance();

echo "<h2>🚀 E-shop Analytics - Setup</h2>";
echo "<hr>";

// Kontrola, zda super admin už existuje
$existing = $db->fetchOne("SELECT id FROM users WHERE email = 'info@shopcode.cz'");

if ($existing) {
    echo "<p>⚠️ Super Admin (info@shopcode.cz) již existuje!</p>";
    echo "<p>Chcete resetovat heslo? <a href='?reset_password=1'>Ano, resetovat heslo</a></p>";
    
    if (isset($_GET['reset_password'])) {
        // Heslo: Shopcode2024??
        $hashedPassword = Security::hashPassword('Shopcode2024??');
        
        $db->update('users', [
            'password' => $hashedPassword,
            'email' => 'info@shopcode.cz'
        ], 'id = ?', [$existing['id']]);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
        echo "<strong>✅ Heslo bylo úspěšně resetováno!</strong><br><br>";
        echo "<strong>Přihlašovací údaje:</strong><br>";
        echo "Email: <code>info@shopcode.cz</code><br>";
        echo "Heslo: <code>Shopcode2024??</code><br><br>";
        echo "<a href='/login.php' style='color: #155724; font-weight: bold;'>→ Přejít na přihlášení</a>";
        echo "</div>";
        
        echo "<p><strong>⚠️ BEZPEČNOST:</strong> Smažte tento setup.php soubor po dokončení!</p>";
    }
    
} else {
    echo "<p>📝 Vytvářím Super Admin účet...</p>";
    
    // Vytvoření Super Admin uživatele
    $hashedPassword = Security::hashPassword('Shopcode2024??');
    
    $userId = $db->insert('users', [
        'name' => 'Super Admin',
        'email' => 'info@shopcode.cz',
        'password' => $hashedPassword,
        'is_super_admin' => true,
        'is_active' => true,
        'company_name' => 'Shopcode',
    ]);
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
    echo "<strong>✅ Super Admin byl úspěšně vytvořen!</strong><br><br>";
    echo "<strong>Přihlašovací údaje:</strong><br>";
    echo "Email: <code>info@shopcode.cz</code><br>";
    echo "Heslo: <code>Shopcode2024??</code><br><br>";
    echo "<a href='/login.php' style='color: #155734; font-weight: bold;'>→ Přejít na přihlášení</a>";
    echo "</div>";
    
    echo "<p><strong>⚠️ BEZPEČNOST:</strong></p>";
    echo "<ul>";
    echo "<li>Po prvním přihlášení změňte heslo!</li>";
    echo "<li>Smažte tento setup.php soubor!</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p style='color: #666;'><small>E-shop Analytics v2.0</small></p>";
