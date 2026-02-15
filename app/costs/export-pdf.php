<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Services\CostsPdfExporter;

$userId = $auth->userId();
$type = get('type', 'monthly'); // monthly, yearly, quarterly

$exporter = new CostsPdfExporter();

try {
    switch ($type) {
        case 'monthly':
            $year = (int) get('year', date('Y'));
            $month = (int) get('month', date('n'));
            $exporter->exportMonthly($userId, $year, $month);
            break;
            
        case 'yearly':
            $year = (int) get('year', date('Y'));
            $exporter->exportYearly($userId, $year);
            break;
            
        case 'quarterly':
            $year = (int) get('year', date('Y'));
            $quarter = (int) get('quarter', ceil(date('n') / 3));
            $exporter->exportQuarterly($userId, $year, $quarter);
            break;
            
        default:
            flash('error', 'Neplatný typ exportu');
            redirect('/app/costs/analytics.php');
    }
} catch (\Throwable $e) {
    flash('error', 'Chyba při exportu: ' . $e->getMessage());
    redirect('/app/costs/analytics.php');
}
