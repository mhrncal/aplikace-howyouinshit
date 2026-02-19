<?php
require_once __DIR__ . '/../bootstrap.php';

$auth->requireAuth();

$storeId = (int) get('store_id');

if ($storeId) {
    switchStore($storeId);
}

// Redirect zpět odkud přišel nebo na dashboard
$referer = $_SERVER['HTTP_REFERER'] ?? '/app/dashboard/';
redirect($referer);
