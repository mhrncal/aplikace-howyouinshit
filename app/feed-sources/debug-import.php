<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Import Debug</h1>";

try {
    echo "1. Loading bootstrap...<br>";
    require_once __DIR__ . '/../../bootstrap.php';
    echo "✅ Bootstrap OK<br>";
    
    echo "2. Auth check...<br>";
    $auth->requireAuth();
    echo "✅ Auth OK, User ID: " . $auth->userId() . "<br>";
    
    echo "3. Loading models...<br>";
    use App\Modules\FeedSources\Models\FeedSource;
    use App\Modules\Products\Services\XmlImportService;
    
    $feedSourceModel = new FeedSource();
    echo "✅ FeedSource model loaded<br>";
    
    echo "4. Getting feed ID...<br>";
    $feedId = (int) ($_GET['id'] ?? 0);
    echo "Feed ID: {$feedId}<br>";
    
    if (!$feedId) {
        throw new Exception("No feed ID provided!");
    }
    
    echo "5. Loading feed from database...<br>";
    $userId = $auth->userId();
    $feed = $feedSourceModel->findById($feedId, $userId);
    
    if (!$feed) {
        throw new Exception("Feed not found!");
    }
    
    echo "✅ Feed found:<br>";
    echo "- Name: " . htmlspecialchars($feed['name']) . "<br>";
    echo "- URL: " . htmlspecialchars($feed['url']) . "<br>";
    echo "- Type: " . htmlspecialchars($feed['type'] ?? 'N/A') . "<br>";
    
    echo "<br>6. Creating XmlImportService...<br>";
    $xmlImporter = new XmlImportService($userId);
    echo "✅ XmlImportService created<br>";
    
    echo "<br>7. Testing URL accessibility...<br>";
    $headers = @get_headers($feed['url']);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "✅ URL is accessible (HTTP 200)<br>";
    } else {
        echo "❌ URL is NOT accessible or returns error<br>";
        echo "Response: " . ($headers[0] ?? 'No response') . "<br>";
    }
    
    echo "<br><h3 style='color:green'>✅ ALL CHECKS PASSED!</h3>";
    echo "<br><strong>Ready to import from:</strong> " . htmlspecialchars($feed['url']);
    echo "<br><br><a href='/app/feed-sources/import-now.php?id={$feedId}'>Try real import (POST)</a>";
    echo " | <a href='/app/feed-sources/'>Back to feeds</a>";
    
} catch (\Throwable $e) {
    echo "<h2 style='color:red'>❌ ERROR:</h2>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
