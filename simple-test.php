<?php
/**
 * JEDNODUCHÝ TEST - Jen password_hash a password_verify
 * SMAŽ PO POUŽITÍ!
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Simple Test</title></head><body>";
echo "<h1>Simple Password Test</h1><hr>";

$password = 'Shopcode2024??';
$dbHash = '$argon2id$v=19$m=65536,t=4,p=2$VHJSbmZndFZxOUE0d2FLOA$qK4P8xqZ9ZwYxGvQqZ8hKZvQqZ8hKZvQqZ8hKZvQqZw';

echo "<h2>Test 1: password_verify() s DB hashem</h2>";
echo "Heslo: <code>{$password}</code><br>";
echo "Hash z DB: <code>{$dbHash}</code><br><br>";

$result = password_verify($password, $dbHash);

if ($result) {
    echo "<p style='color:green;font-size:20px;'><strong>✅ ÚSPĚCH! password_verify() funguje!</strong></p>";
    echo "<p>Hash v databázi odpovídá heslu <code>{$password}</code></p>";
    echo "<p><strong>To znamená, že přihlášení BY MĚLO fungovat!</strong></p>";
} else {
    echo "<p style='color:red;font-size:20px;'><strong>❌ SELHALO! password_verify() nefunguje!</strong></p>";
    echo "<p>Hash v databázi NEODPOVÍDÁ heslu <code>{$password}</code></p>";
}

echo "<hr>";

echo "<h2>Test 2: Generování nového hashe</h2>";

try {
    // Test BCRYPT
    echo "<p>Zkouším BCRYPT...</p>";
    $bcryptHash = password_hash($password, PASSWORD_BCRYPT);
    echo "✅ BCRYPT hash: <code style='font-size:10px;'>{$bcryptHash}</code><br><br>";
    
    $bcryptVerify = password_verify($password, $bcryptHash);
    echo "BCRYPT verify: " . ($bcryptVerify ? "✅ Funguje" : "❌ Nefunguje") . "<br><br>";
    
} catch (Exception $e) {
    echo "❌ BCRYPT error: " . $e->getMessage() . "<br><br>";
}

try {
    // Test ARGON2ID
    echo "<p>Zkouším ARGON2ID...</p>";
    $argonHash = password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 2
    ]);
    echo "✅ ARGON2ID hash: <code style='font-size:10px;'>{$argonHash}</code><br><br>";
    
    $argonVerify = password_verify($password, $argonHash);
    echo "ARGON2ID verify: " . ($argonVerify ? "✅ Funguje" : "❌ Nefunguje") . "<br><br>";
    
} catch (Exception $e) {
    echo "❌ ARGON2ID error: " . $e->getMessage() . "<br><br>";
}

echo "<hr>";

echo "<h2>Test 3: Co když aktualizujeme hash v DB?</h2>";

if (isset($_GET['update_db'])) {
    require_once __DIR__ . '/bootstrap.php';
    
    use App\Core\Database;
    
    $db = Database::getInstance();
    
    // Použij BCRYPT (funguje všude)
    $newHash = password_hash($password, PASSWORD_BCRYPT);
    
    $db->update('users', [
        'password' => $newHash
    ], 'email = ?', ['info@shopcode.cz']);
    
    echo "<p style='color:green;'><strong>✅ Hash v databázi aktualizován na BCRYPT!</strong></p>";
    echo "<p>Nový hash: <code style='font-size:10px;'>{$newHash}</code></p>";
    echo "<p><a href='/login.php' style='font-size:18px;font-weight:bold;'>→ Zkusit přihlášení</a></p>";
} else {
    echo "<p><a href='?update_db=1' style='font-size:18px;font-weight:bold;color:blue;'>→ AKTUALIZOVAT HASH V DATABÁZI</a></p>";
}

echo "<hr>";
echo "<p><strong>⚠️ SMAŽ tento simple-test.php po použití!</strong></p>";
echo "</body></html>";
