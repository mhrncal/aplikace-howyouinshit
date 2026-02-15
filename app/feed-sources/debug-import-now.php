<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Import Now Debug</h1>";

try {
    echo "1. Loading bootstrap...<br>";
    require_once __DIR__ . '/../../bootstrap.php';
    echo "✅ Bootstrap loaded<br>";
    
    echo "2. Auth check...<br>";
    $auth->requireAuth();
    echo "✅ Auth OK, User ID: " . $auth->userId() . "<br>";
    
    echo "3. Loading classes...<br>";
    use App\Modules\FeedSources\Models\FeedSource;
    use App\Modules\Products\Services\XmlImportService;
    echo "✅ Classes loaded<br>";
    
    echo "4. Getting feed ID...<br>";
    $feedId = (int) ($_GET['id'] ?? 0);
    echo "Feed ID: {$feedId}<br>";
    
    if (!$feedId) {
        throw new Exception("No feed ID provided!");
    }
    
    echo "5. Creating models...<br>";
    $feedSourceModel = new FeedSource();
    echo "✅ FeedSource model created<br>";
    
    echo "6. Loading feed from DB...<br>";
    $userId = $auth->userId();
    $feed = $feedSourceModel->findById($feedId, $userId);
    
    if (!$feed) {
        throw new Exception("Feed not found!");
    }
    
    echo "✅ Feed found:<br>";
    echo "<pre>";
    print_r($feed);
    echo "</pre>";
    
    echo "7. Testing XmlImportService creation...<br>";
    $xmlImporter = new XmlImportService();
    echo "✅ XmlImportService created<br>";
    
    echo "<br><h3 style='color:green'>✅ ALL CHECKS PASSED!</h3>";
    echo "<br>URL pro import: " . htmlspecialchars($feed['url']);
    
    if (isPost()) {
        echo "<hr><h2>POST REQUEST - Starting import...</h2>";
        
        set_time_limit(600);
        ini_set('memory_limit', '512M');
        
        echo "Calling importFromUrl()...<br>";
        flush();
        
        $result = $xmlImporter->importFromUrl(
            $feedId,
            $userId,
            $feed['url'],
            $feed['http_auth_username'] ?? null,
            $feed['http_auth_password'] ?? null
        );
        
        echo "<h3>✅ IMPORT COMPLETED!</h3>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "<hr>";
        echo "<form method='POST'>";
        echo csrf();
        echo "<button type='submit' style='padding: 10px 20px; font-size: 16px;'>🚀 Spustit import (POST)</button>";
        echo "</form>";
    }
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>❌ ERROR:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
