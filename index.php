<?php
/**
 * E-shop Analytics Platform v2.0
 * Main Entry Point
 */

require_once __DIR__ . '/bootstrap.php';

// Pokud je přihlášen, jdi na dashboard
if ($auth->check()) {
    redirect('/app/dashboard/index.php');
}

// Jinak na přihlášení
redirect('/login.php');
