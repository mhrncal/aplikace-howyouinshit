<?php
require_once __DIR__ . '/../../bootstrap.php';

use App\Modules\Products\Controllers\ProductController;

$controller = new ProductController();

// Routing podle akce
$action = get('action', 'index');

switch ($action) {
    case 'detail':
        $controller->detail();
        break;
    case 'export':
        $controller->export();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->index();
}
