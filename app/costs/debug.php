<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Costs Debug</h1>";

try {
    echo "1. Loading bootstrap...<br>";
    require_once __DIR__ . '/../../bootstrap.php';
    echo "✅ Bootstrap OK<br>";
    
    echo "2. Auth check...<br>";
    $auth->requireAuth();
    echo "✅ Auth OK<br>";
    echo "User ID: " . $auth->userId() . "<br>";
    
    echo "3. Loading Cost model...<br>";
    use App\Models\Cost;
    
    $costModel = new Cost();
    echo "✅ Cost model created<br>";
    
    echo "4. Checking database table...<br>";
    $db = App\Core\Database::getInstance();
    
    // Check if table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'costs'");
    if (empty($tables)) {
        echo "<strong style='color:red'>❌ TABLE 'costs' DOES NOT EXIST!</strong><br>";
        echo "<br>You need to run the migration:<br>";
        echo "<pre>database/costs_migration.sql</pre>";
        echo "<br><a href='/app/costs/create-table.php'>Create table automatically</a>";
        die();
    } else {
        echo "✅ Table 'costs' exists<br>";
    }
    
    echo "5. Testing getAll()...<br>";
    $userId = $auth->userId();
    $data = $costModel->getAll($userId);
    echo "✅ getAll() works<br>";
    echo "Costs count: " . count($data['costs']) . "<br>";
    
    echo "6. Testing getMonthlyBreakdown()...<br>";
    $monthlyData = $costModel->getMonthlyBreakdown($userId, date('Y'), date('n'));
    echo "✅ getMonthlyBreakdown() works<br>";
    echo "Total this month: " . $monthlyData['total'] . "<br>";
    
    echo "<br><h3 style='color:green'>✅ ALL TESTS PASSED!</h3>";
    echo "<br><a href='/app/costs/'>Go to costs page</a>";
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>❌ ERROR:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
