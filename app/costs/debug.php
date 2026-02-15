<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Costs</h1>";

try {
    require_once __DIR__ . '/../../bootstrap.php';
    echo "✅ Bootstrap loaded<br>";
    
    $auth->requireAuth();
    echo "✅ Auth OK<br>";
    
    use App\Models\Cost;
    
    $costModel = new Cost();
    echo "✅ Cost Model created<br>";
    
    echo "<br><strong>Model methods:</strong><br>";
    $methods = get_class_methods($costModel);
    foreach ($methods as $method) {
        echo "- {$method}<br>";
    }
    
    $userId = $auth->userId();
    echo "<br>User ID: {$userId}<br>";
    
    // Test getAll
    echo "<br>Testing getAll()...<br>";
    $data = $costModel->getAll($userId);
    echo "✅ getAll() works<br>";
    echo "Costs count: " . count($data['costs']) . "<br>";
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>ERROR:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
