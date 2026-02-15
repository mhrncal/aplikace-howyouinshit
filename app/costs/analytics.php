<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\Cost;

$costModel = new Cost();
$userId = $auth->userId();

// Rok pro zobrazení
$year = (int) get('year', date('Y'));

// Data
$yearlyData = $costModel->getYearlyOverview($userId, $year);
$currentMonthData = $costModel->getMonthlyBreakdown($userId, date('Y'), date('n'));

$title = 'Analýza nákladů';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Analýza nákladů <?= $year ?></h2>
    <div class="d-flex gap-2">
        <div class="btn-group">
            <a href="?year=<?= $year - 1 ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> <?= $year - 1 ?>
            </a>
            <a href="?year=<?= date('Y') ?>" class="btn btn-primary">Aktuální rok</a>
            <a href="?year=<?= $year + 1 ?>" class="btn btn-outline-secondary">
                <?= $year + 1 ?> <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        <a href="/app/costs/quarterly.php" class="btn btn-info">
            <i class="bi bi-calendar3 me-1"></i>Kvartální analýza
        </a>
    </div>
</div>

<!-- Roční přehled -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-cash-stack" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($yearlyData['total_year']) ?></h3>
            <p>Celkem za rok</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <i class="bi bi-graph-up" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($yearlyData['avg_month']) ?></h3>
            <p>Průměr / měsíc</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
            <i class="bi bi-arrow-up-circle" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($yearlyData['fixed_total']) ?></h3>
            <p>Fixní náklady</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-arrow-down-circle" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($yearlyData['variable_total']) ?></h3>
            <p>Variabilní náklady</p>
        </div>
    </div>
</div>

<!-- Měsíční breakdown -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-calendar3 me-2"></i>Měsíční přehled <?= $year ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Měsíc</th>
                        <th>Celkem</th>
                        <th>Fixní</th>
                        <th>Variabilní</th>
                        <th>% z ročního</th>
                        <th>Graf</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($yearlyData['months'] as $month => $data): ?>
                    <tr>
                        <td><strong><?= $data['month_name'] ?></strong></td>
                        <td><strong><?= formatPrice($data['total']) ?></strong></td>
                        <td><?= formatPrice($data['fixed']) ?></td>
                        <td><?= formatPrice($data['variable']) ?></td>
                        <td><?= $yearlyData['total_year'] > 0 ? number_format(($data['total'] / $yearlyData['total_year']) * 100, 1) : 0 ?>%</td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-primary" style="width: <?= $yearlyData['total_year'] > 0 ? ($data['fixed'] / $yearlyData['total_year']) * 100 : 0 ?>%">
                                    <?php if ($data['fixed'] > 0): ?>
                                        <small><?= formatPrice($data['fixed'], 0) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="progress-bar bg-warning" style="width: <?= $yearlyData['total_year'] > 0 ? ($data['variable'] / $yearlyData['total_year']) * 100 : 0 ?>%">
                                    <?php if ($data['variable'] > 0): ?>
                                        <small><?= formatPrice($data['variable'], 0) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th>CELKEM</th>
                        <th><?= formatPrice($yearlyData['total_year']) ?></th>
                        <th><?= formatPrice($yearlyData['fixed_total']) ?></th>
                        <th><?= formatPrice($yearlyData['variable_total']) ?></th>
                        <th>100%</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Breakdown kategorií (aktuální měsíc) -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i>Rozložení podle kategorií (tento měsíc)
            </div>
            <div class="card-body">
                <?php if (empty($currentMonthData['by_category'])): ?>
                    <p class="text-muted mb-0">Zatím žádné náklady</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Kategorie</th>
                                <th>Částka</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentMonthData['by_category'] as $category => $amount): ?>
                            <tr>
                                <td><?= e($category) ?></td>
                                <td><?= formatPrice($amount) ?></td>
                                <td>
                                    <?= $currentMonthData['total'] > 0 ? number_format(($amount / $currentMonthData['total']) * 100, 1) : 0 ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th>CELKEM</th>
                                <th><?= formatPrice($currentMonthData['total']) ?></th>
                                <th>100%</th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Breakdown podle frekvence (tento měsíc)
            </div>
            <div class="card-body">
                <?php if (empty($currentMonthData['by_frequency'])): ?>
                    <p class="text-muted mb-0">Zatím žádné náklady</p>
                <?php else: ?>
                    <?php
                    $freqLabels = [
                        'daily' => 'Denně',
                        'weekly' => 'Týdně',
                        'monthly' => 'Měsíčně',
                        'quarterly' => 'Kvartálně',
                        'yearly' => 'Ročně',
                        'once' => 'Jednorázově'
                    ];
                    ?>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Frekvence</th>
                                <th>Částka (přepočteno)</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentMonthData['by_frequency'] as $frequency => $amount): ?>
                            <tr>
                                <td><?= $freqLabels[$frequency] ?? $frequency ?></td>
                                <td><?= formatPrice($amount) ?></td>
                                <td>
                                    <?= $currentMonthData['total'] > 0 ? number_format(($amount / $currentMonthData['total']) * 100, 1) : 0 ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-active">
                                <th>CELKEM</th>
                                <th><?= formatPrice($currentMonthData['total']) ?></th>
                                <th>100%</th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- GRAFY -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i>Roční náklady - graf
            </div>
            <div class="card-body">
                <canvas id="yearlyChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i>Kategorie (aktuální měsíc)
            </div>
            <div class="card-body">
                <canvas id="categoryChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 text-center d-flex gap-2 justify-content-center">
    <a href="/app/costs/" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Zpět na seznam nákladů
    </a>
    <a href="/app/costs/export-pdf.php?type=yearly&year=<?= $year ?>" class="btn btn-danger" target="_blank">
        <i class="bi bi-file-pdf me-2"></i>Export do PDF (celý rok)
    </a>
    <a href="/app/costs/export-pdf.php?type=monthly&year=<?= date('Y') ?>&month=<?= date('n') ?>" class="btn btn-danger" target="_blank">
        <i class="bi bi-file-pdf me-2"></i>Export aktuálního měsíce
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// GRAF 1: Roční vývoj nákladů
const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
new Chart(yearlyCtx, {
    type: 'bar',
    data: {
        labels: [<?php 
            foreach ($yearlyData['months'] as $m) {
                echo "'" . $m['month_name'] . "',";
            }
        ?>],
        datasets: [
            {
                label: 'Fixní',
                data: [<?php 
                    foreach ($yearlyData['months'] as $m) {
                        echo $m['fixed'] . ",";
                    }
                ?>],
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            },
            {
                label: 'Variabilní',
                data: [<?php 
                    foreach ($yearlyData['months'] as $m) {
                        echo $m['variable'] . ",";
                    }
                ?>],
                backgroundColor: 'rgba(249, 115, 22, 0.8)',
                borderColor: 'rgb(249, 115, 22)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: { stacked: true },
            y: { 
                stacked: true,
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('cs-CZ') + ' Kč';
                    }
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Měsíční náklady <?= $year ?>'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + 
                               context.parsed.y.toLocaleString('cs-CZ') + ' Kč';
                    }
                }
            }
        }
    }
});

// GRAF 2: Kategorie (koláčový)
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'pie',
    data: {
        labels: [<?php 
            foreach ($currentMonthData['by_category'] as $cat => $amount) {
                echo "'" . addslashes($cat) . "',";
            }
        ?>],
        datasets: [{
            data: [<?php 
                foreach ($currentMonthData['by_category'] as $amount) {
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
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            title: {
                display: true,
                text: 'Náklady podle kategorií (<?= date('n') ?>. měsíc)'
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
            }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
