<?php
require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Logger;

echo "<h1>Test Logger</h1>";
echo "<pre>";

// Test 1: Základní log
Logger::info("Test basic log", [], 'app');
echo "✅ Test 1: Basic log\n";

// Test 2: Import log BEZ context
Logger::info("Test import without context", [], 'import');
echo "✅ Test 2: Import without context\n";

// Test 3: Import log S context
Logger::info("Test import WITH context", [
    'user_id' => 1,
    'feed_source_id' => 5,
    'test' => 'value'
], 'import');
echo "✅ Test 3: Import with context\n";

// Zobraz co vzniklo
echo "\n=== SOUBORY V storage/logs ===\n";
system("find /srv/app/storage/logs -type f 2>/dev/null");

echo "\n=== OBSAH app logu ===\n";
$appLog = "/srv/app/storage/logs/app-" . date('Y-m-d') . ".log";
if (file_exists($appLog)) {
    echo file_get_contents($appLog);
} else {
    echo "App log neexistuje!\n";
}

echo "\n=== OBSAH import složky ===\n";
system("find /srv/app/storage/logs/imports -type f 2>/dev/null");

echo "</pre>";
