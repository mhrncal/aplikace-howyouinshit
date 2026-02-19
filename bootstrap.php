<?php

/**
 * Bootstrap aplikace
 * Inicializuje celý systém
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Timezone
date_default_timezone_set('Europe/Prague');

// Autoloader
require_once __DIR__ . '/src/Core/Autoloader.php';
App\Core\Autoloader::register();

// Session
if (session_status() === PHP_SESSION_NONE || session_status() === PHP_SESSION_DISABLED) {
    // Custom session handler pro případy kdy jsou sessions vypnuté
    $handler = new App\Core\FileSessionHandler();
    session_set_save_handler($handler, true);
    
    ini_set('session.cookie_httponly', '1');
    // HTTPS check - podporuje proxy (X-Forwarded-Proto)
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
    ini_set('session.use_strict_mode', '1');
    session_name('ESHOP_ANALYTICS_SESSION');
    
    @session_start();
}

// Helper funkce
require_once __DIR__ . '/src/helpers.php';

// Logger init
App\Core\Logger::init();

// Config
$config = require __DIR__ . '/config/app.php';

// Nastavení limitů pro import
ini_set('memory_limit', $config['import']['memory_limit']);
ini_set('max_execution_time', (string) $config['import']['max_execution_time']);

// Global instances
$auth = new App\Core\Auth();

// Store context - nastav aktuální shop
$currentStore = null;
if ($auth->check()) {
    $storeModel = new App\Models\Store();
    
    // Pokud není vybrán store, vyber první aktivní
    if (empty($_SESSION['current_store_id'])) {
        $defaultStore = $storeModel->getDefaultForUser($auth->userId());
        if ($defaultStore) {
            $_SESSION['current_store_id'] = $defaultStore['id'];
        }
    }
    
    // Načti aktuální store
    if (!empty($_SESSION['current_store_id'])) {
        $currentStore = $storeModel->findById($_SESSION['current_store_id'], $auth->userId());
        
        // Pokud store neexistuje nebo není uživatele, vynuluj
        if (!$currentStore) {
            unset($_SESSION['current_store_id']);
            $defaultStore = $storeModel->getDefaultForUser($auth->userId());
            if ($defaultStore) {
                $_SESSION['current_store_id'] = $defaultStore['id'];
                $currentStore = $defaultStore;
            }
        }
    }
}

// Cleanup starých logů (1x denně)
if (!isset($_SESSION['last_log_cleanup']) || $_SESSION['last_log_cleanup'] < strtotime('today')) {
    App\Core\Logger::cleanOldLogs(30);
    $_SESSION['last_log_cleanup'] = time();
}
