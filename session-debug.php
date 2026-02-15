<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Debug</h1>";

echo "<h2>1. Before session_start()</h2>";
echo "Session status: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "Session ID: '" . session_id() . "'<br>";

echo "<h2>2. Session configuration</h2>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.name: " . ini_get('session.name') . "<br>";

echo "<h2>3. Trying to start session...</h2>";

if (session_status() === PHP_SESSION_NONE) {
    echo "Session is NONE, starting...<br>";
    
    ini_set('session.cookie_httponly', '1');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        echo "HTTPS detected, setting cookie_secure<br>";
        ini_set('session.cookie_secure', '1');
    } else {
        echo "NO HTTPS, skipping cookie_secure<br>";
    }
    ini_set('session.use_strict_mode', '1');
    session_name('ESHOP_ANALYTICS_SESSION');
    
    echo "Calling session_start()...<br>";
    $result = session_start();
    echo "session_start() returned: " . ($result ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "Session already started (status: " . session_status() . ")<br>";
}

echo "<h2>4. After session_start()</h2>";
echo "Session status: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "Session ID: '" . session_id() . "'<br>";
echo "Session name: " . session_name() . "<br>";

echo "<h2>5. Session data</h2>";
echo "SESSION array:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>6. Trying to set data</h2>";
$_SESSION['test'] = 'Hello World';
$_SESSION['timestamp'] = time();
echo "Set test data...<br>";
echo "SESSION after set:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>7. Cookie info</h2>";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "<br>";
echo "Cookies sent:<br>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>8. Save path check</h2>";
$savePath = session_save_path();
echo "Save path: {$savePath}<br>";
if (is_dir($savePath)) {
    echo "Directory exists: YES<br>";
    echo "Is writable: " . (is_writable($savePath) ? 'YES' : 'NO') . "<br>";
} else {
    echo "Directory exists: NO<br>";
}
