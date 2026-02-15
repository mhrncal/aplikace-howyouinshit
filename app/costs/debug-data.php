<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\Cost;

$costModel = new Cost();
$userId = $auth->userId();

echo "<h1>Debug Costs Data</h1>";

// Všechny náklady
$allCosts = $costModel->getAll($userId, 1, 100, []);
echo "<h2>Všechny náklady (raw data):</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Název</th><th>Kategorie</th><th>Částka</th><th>Typ</th><th>Frekvence</th><th>Aktivní</th><th>Start</th><th>End</th></tr>";

foreach ($allCosts['costs'] as $cost) {
    echo "<tr>";
    echo "<td>{$cost['id']}</td>";
    echo "<td>{$cost['name']}</td>";
    echo "<td><strong>{$cost['category']}</strong></td>";
    echo "<td><strong>{$cost['amount']}</strong></td>";
    echo "<td>{$cost['type']}</td>";
    echo "<td>{$cost['frequency']}</td>";
    echo "<td>" . ($cost['is_active'] ? 'ANO' : 'NE') . "</td>";
    echo "<td>{$cost['start_date']}</td>";
    echo "<td>" . ($cost['end_date'] ?? 'neomezeno') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Měsíční breakdown
$year = date('Y');
$month = date('n');
echo "<h2>Měsíční breakdown ($year-$month):</h2>";
$monthlyData = $costModel->getMonthlyBreakdown($userId, $year, $month);

echo "<h3>Celkové údaje:</h3>";
echo "Total: " . $monthlyData['total'] . "<br>";
echo "Fixed: " . $monthlyData['fixed'] . "<br>";
echo "Variable: " . $monthlyData['variable'] . "<br>";

echo "<h3>By Category:</h3>";
echo "<pre>";
print_r($monthlyData['by_category']);
echo "</pre>";

echo "<h3>By Frequency:</h3>";
echo "<pre>";
print_r($monthlyData['by_frequency']);
echo "</pre>";

echo "<h3>Items:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Název</th><th>Kategorie</th><th>Částka orig</th><th>Měsíční částka</th><th>Frekvence</th></tr>";

foreach ($monthlyData['items'] as $item) {
    echo "<tr>";
    echo "<td>{$item['name']}</td>";
    echo "<td><strong>{$item['category']}</strong></td>";
    echo "<td>{$item['amount']}</td>";
    echo "<td><strong>{$item['monthly_amount']}</strong></td>";
    echo "<td>{$item['frequency']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br><a href='/app/costs/'>Zpět na náklady</a>";
