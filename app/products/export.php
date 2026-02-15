<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\Products\Services\ProductExporter;

$userId = $auth->userId();
$format = get('format', 'csv'); // csv nebo xlsx

$exporter = new ProductExporter();

try {
    if ($format === 'xlsx') {
        $content = $exporter->exportToXlsx($userId);
        $filename = 'produkty_' . date('Y-m-d') . '.xlsx';
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    } else {
        $content = $exporter->exportToCsv($userId);
        $filename = 'produkty_' . date('Y-m-d') . '.csv';
        $contentType = 'text/csv; charset=utf-8';
    }
    
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    
    echo $content;
    
} catch (\Exception $e) {
    flash('error', 'Chyba exportu: ' . $e->getMessage());
    redirect('/app/products/');
}
