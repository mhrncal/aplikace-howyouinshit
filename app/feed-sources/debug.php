<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Feed Sources Create</h1>";

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
    
    echo "4. Loading FeedSource model...<br>";
    
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

use App\Modules\FeedSources\Models\FeedSource;

try {
    $feedSourceModel = new FeedSource();
    echo "✅ FeedSource Model created<br>";
    
    echo "<br><strong>Model methods:</strong><br>";
    $methods = get_class_methods($feedSourceModel);
    foreach ($methods as $method) {
        echo "- {$method}<br>";
    }
    
    echo "<br><h3>✅ ALL TESTS PASSED!</h3>";
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>ERROR in model creation:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
