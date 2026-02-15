<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing import-ajax.php<br><br>";

require_once __DIR__ . '/../../bootstrap.php';

echo "✅ Bootstrap loaded<br>";

// Simuluj POST
$_POST['feed_id'] = 2;
$_POST['csrf_token'] = $_SESSION['csrf_token'] ?? '';

$userId = 1;
$feedId = 2;

echo "User ID: {$userId}<br>";
echo "Feed ID: {$feedId}<br><br>";

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Services\XmlImportService;

$feedSourceModel = new FeedSource();

echo "Loading feed...<br>";
$feed = $feedSourceModel->findById($feedId, $userId);

if (!$feed) {
    die("❌ Feed not found!");
}

echo "✅ Feed loaded:<br>";
echo "<pre>";
print_r($feed);
echo "</pre>";

echo "<br>Testing CURL download...<br>";

$url = $feed['url'];
echo "URL: {$url}<br><br>";

// Test CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_NOBODY, true); // Jen hlavičky
curl_setopt($ch, CURLOPT_HEADER, true);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: {$httpCode}<br>";
echo "Content-Type: {$contentType}<br>";
echo "Content-Length: " . round($contentLength / 1024 / 1024, 2) . " MB<br>";
echo "Error: " . ($error ?: 'none') . "<br><br>";

if ($httpCode !== 200) {
    die("❌ Cannot download XML! HTTP {$httpCode}");
}

echo "✅ URL is accessible!<br><br>";

echo "<strong>Ready to import!</strong><br>";
echo "<a href='/app/feed-sources/import-now.php?id=2'>Try real import</a>";
