<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Core\Database;

$db = Database::getInstance();

echo "<h2>Test databáze orders</h2>";
echo "<pre>";

// Test 1: Existuje tabulka orders?
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'orders'");
    echo "✅ Tabulka 'orders' existuje: " . ($result ? "ANO" : "NE") . "\n";
} catch (\Exception $e) {
    echo "❌ Chyba při kontrole tabulky orders: " . $e->getMessage() . "\n";
}

// Test 2: Existuje tabulka order_items?
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'order_items'");
    echo "✅ Tabulka 'order_items' existuje: " . ($result ? "ANO" : "NE") . "\n";
} catch (\Exception $e) {
    echo "❌ Chyba při kontrole tabulky order_items: " . $e->getMessage() . "\n";
}

// Test 3: Existuje tabulka order_feed_sources?
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'order_feed_sources'");
    echo "✅ Tabulka 'order_feed_sources' existuje: " . ($result ? "ANO" : "NE") . "\n";
} catch (\Exception $e) {
    echo "❌ Chyba při kontrole tabulky order_feed_sources: " . $e->getMessage() . "\n";
}

// Test 4: Existuje tabulka shipping_costs?
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'shipping_costs'");
    echo "✅ Tabulka 'shipping_costs' existuje: " . ($result ? "ANO" : "NE") . "\n";
} catch (\Exception $e) {
    echo "❌ Chyba při kontrole tabulky shipping_costs: " . $e->getMessage() . "\n";
}

// Test 5: Existuje tabulka billing_costs?
try {
    $result = $db->fetchOne("SHOW TABLES LIKE 'billing_costs'");
    echo "✅ Tabulka 'billing_costs' existuje: " . ($result ? "ANO" : "NE") . "\n";
} catch (\Exception $e) {
    echo "❌ Chyba při kontrole tabulky billing_costs: " . $e->getMessage() . "\n";
}

// Test 6: Může načíst OrderFeedSource model?
try {
    $model = new \App\Models\OrderFeedSource();
    echo "✅ OrderFeedSource model se načetl\n";
} catch (\Exception $e) {
    echo "❌ Chyba při načtení OrderFeedSource: " . $e->getMessage() . "\n";
}

// Test 7: Může načíst Order model?
try {
    $model = new \App\Models\Order();
    echo "✅ Order model se načetl\n";
} catch (\Exception $e) {
    echo "❌ Chyba při načtení Order: " . $e->getMessage() . "\n";
}

echo "</pre>";

echo "<h3>Nápověda:</h3>";
echo "<p>Pokud některá tabulka NEEXISTUJE, spusť migraci:</p>";
echo "<code>mysql -u USER -p DATABASE < database/CREATE_ORDERS_ANALYTICS.sql</code>";
