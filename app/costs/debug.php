<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Costs</h1>";

try {
    echo "1. Loading bootstrap...<br>";
    require_once __DIR__ . '/../../bootstrap.php';
    echo "✅ Bootstrap loaded<br>";
    
    echo "2. Checking \$auth variable...<br>";
    if (isset($auth)) {
        echo "✅ \$auth exists<br>";
        echo "Class: " . get_class($auth) . "<br>";
    } else {
        echo "❌ \$auth NOT SET!<br>";
        die("Auth not initialized in bootstrap!");
    }
    
    echo "3. Calling requireAuth()...<br>";
    $auth->requireAuth();
    echo "✅ Auth OK<br>";
    
    echo "4. Loading Cost model...<br>";
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>ERROR at step above:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
    exit;
}

use App\Models\Cost;

try {
    $costModel = new Cost();
    echo "✅ Cost Model created<br>";
    
    echo "<br><strong>Model methods:</strong><br>";
    $methods = get_class_methods($costModel);
    foreach ($methods as $method) {
        echo "- {$method}<br>";
    }
    
    $userId = $auth->userId();
    echo "<br>User ID: {$userId}<br>";
    
    echo "5. Testing getAll()...<br>";
    $data = $costModel->getAll($userId);
    echo "✅ getAll() works<br>";
    echo "Costs count: " . count($data['costs']) . "<br>";
    
    echo "<br><h3>✅ ALL TESTS PASSED!</h3>";
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>ERROR in model/methods:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
