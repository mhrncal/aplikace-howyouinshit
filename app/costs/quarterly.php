<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\Cost;

$costModel = new Cost();
$userId = $auth->userId();

// Rok a kvartál
$year = (int) get('year', date('Y'));
$quarter = (int) get('quarter', ceil(date('n') / 3));

// Validace kvartálu
if ($quarter < 1 || $quarter > 4) {
    $quarter = ceil(date('n') / 3);
}

// Data
$quarterlyData = $costModel->getQuarterlyBreakdown($userId, $year, $quarter);

$title = "Kvartální analýza Q$quarter/$year";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Kvartální analýza - Q<?= $quarter ?> / <?= $year ?></h2>
    <div class="btn-group">
        <a href="?year=<?= $year ?>&quarter=<?= $quarter > 1 ? $quarter - 1 : 1 ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Předchozí
        </a>
        <a href="?year=<?= date('Y') ?>&quarter=<?= ceil(date('n') / 3) ?>" class="btn btn-primary">Aktuální</a>
        <a href="?year=<?= $year ?>&quarter=<?= $quarter < 4 ? $quarter + 1 : 4 ?>" class="btn btn-outline-secondary">
            Další <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Statistiky -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-cash-stack" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($quarterlyData['total']) ?></h3>
            <p>Celkem za kvartál</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <i class="bi bi-graph-up" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($quarterlyData['total'] / 3) ?></h3>
            <p>Průměr / měsíc</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="bi bi-check-circle" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($quarterlyData['fixed']) ?></h3>
            <p>Fixní</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-activity" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($quarterlyData['variable']) ?></h3>
            <p>Variabilní</p>
        </div>
    </div>
</div>

<!-- Měsíční breakdown -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-calendar3 me-2"></i>Breakdown po měsících
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Měsíc</th>
                        <th>Celkem</th>
                        <th>Fixní</th>
                        <th>Variabilní</th>
                        <th width="200">Graf</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quarterlyData['by_month'] as $monthData): ?>
                    <tr>
                        <td><strong><?= $monthData['month_name'] ?></strong></td>
                        <td><?= formatPrice($monthData['total']) ?></td>
                        <td><?= formatPrice($monthData['fixed']) ?></td>
                        <td><?= formatPrice($monthData['variable']) ?></td>
                        <td>
                            <div class="progress" style="height: 25px;">
                                <?php 
                                $fixedPct = $monthData['total'] > 0 ? ($monthData['fixed'] / $monthData['total']) * 100 : 0;
                                $varPct = $monthData['total'] > 0 ? ($monthData['variable'] / $monthData['total']) * 100 : 0;
                                ?>
                                <div class="progress-bar bg-primary" style="width: <?= $fixedPct ?>%"><?= number_format($fixedPct, 0) ?>%</div>
                                <div class="progress-bar bg-warning" style="width: <?= $varPct ?>%"><?= number_format($varPct, 0) ?>%</div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-active">
                    <tr>
                        <th>CELKEM</th>
                        <th><?= formatPrice($quarterlyData['total']) ?></th>
                        <th><?= formatPrice($quarterlyData['fixed']) ?></th>
                        <th><?= formatPrice($quarterlyData['variable']) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Kategorie -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-pie-chart me-2"></i>Náklady podle kategorií
    </div>
    <div class="card-body">
        <canvas id="categoryChart" height="250"></canvas>
    </div>
</div>

<div class="text-center mt-4 d-flex gap-2 justify-content-center">
    <a href="/app/costs/analytics.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Zpět na roční analytiku
    </a>
    <a href="/app/costs/export-pdf.php?type=quarterly&year=<?= $year ?>&quarter=<?= $quarter ?>" class="btn btn-danger" target="_blank">
        <i class="bi bi-file-pdf me-2"></i>Export do PDF
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('categoryChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: [<?php 
            foreach ($quarterlyData['by_category'] as $cat => $amount) {
                echo "'" . addslashes($cat) . "',";
            }
        ?>],
        datasets: [{
            data: [<?php 
                foreach ($quarterlyData['by_category'] as $amount) {
                    echo $amount . ",";
                }
            ?>],
            backgroundColor: [
                'rgba(59, 130, 246, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(236, 72, 153, 0.8)',
                'rgba(14, 165, 233, 0.8)',
                'rgba(251, 146, 60, 0.8)',
                'rgba(132, 204, 22, 0.8)',
            ],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Náklady Q<?= $quarter ?>/<?= $year ?> podle kategorií',
                font: { size: 16 }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value.toLocaleString('cs-CZ') + ' Kč (' + percentage + '%)';
                    }
                }
            },
            legend: {
                position: 'right'
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
