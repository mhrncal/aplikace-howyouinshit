<?php
require_once __DIR__ . '/bootstrap.php';

echo "<h1>Session Debug</h1>";

echo "<h2>1. After bootstrap</h2>";
echo "Session status: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "Session ID: '" . session_id() . "'<br>";

echo "<h2>2. Session configuration</h2>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";
echo "session.use_strict_mode: " . ini_get('session.use_strict_mode') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.name: " . session_name() . "<br>";

echo "<h2>3. Session data</h2>";
echo "SESSION array:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>4. Trying to set data</h2>";
$_SESSION['test'] = 'Hello World ' . time();
$_SESSION['timestamp'] = time();
echo "Set test data...<br>";
echo "SESSION after set:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>5. Cookie info</h2>";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "<br>";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "<br>";
echo "Cookies sent:<br>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>6. Session files check</h2>";
$sessionsDir = __DIR__ . '/storage/sessions';
echo "Sessions directory: {$sessionsDir}<br>";
if (is_dir($sessionsDir)) {
    echo "Directory exists: YES<br>";
    echo "Is writable: " . (is_writable($sessionsDir) ? 'YES' : 'NO') . "<br>";
    
    $files = glob($sessionsDir . '/sess_*');
    echo "Session files count: " . count($files) . "<br>";
    
    if (!empty($files)) {
        echo "<br>Recent session files:<br>";
        foreach (array_slice($files, -5) as $file) {
            $size = filesize($file);
            $time = date('Y-m-d H:i:s', filemtime($file));
            echo "- " . basename($file) . " ({$size} bytes, {$time})<br>";
        }
    }
} else {
    echo "Directory exists: NO<br>";
}

echo "<h2>7. Auth check</h2>";
if (isset($auth)) {
    echo "Auth object: YES<br>";
    echo "Is logged in: " . ($auth->check() ? 'YES ✅' : 'NO ❌') . "<br>";
    
    if ($auth->check()) {
        echo "User ID: " . $auth->userId() . "<br>";
        echo "User data:<br>";
        echo "<pre>";
        print_r($auth->user());
        echo "</pre>";
    }
} else {
    echo "Auth object: NO ❌<br>";
}

echo "<br><br><a href='/login.php'>Go to login</a> | ";
echo "<a href='/app/dashboard/'>Go to dashboard</a> | ";
echo "<a href='/app/auth/logout.php'>Logout</a>";

