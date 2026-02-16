<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Models\Order;

$orderModel = new Order();
$userId = $auth->userId();

// Období
$year = (int) get('year', date('Y'));
$dateFrom = get('date_from', date('Y-01-01'));
$dateTo = get('date_to', date('Y-12-31'));

// Analytika
$analytics = $orderModel->getAnalytics($userId, $dateFrom, $dateTo);
$topProducts = $orderModel->getTopProducts($userId, 15, $dateFrom, $dateTo);
$monthlyTrends = $orderModel->getMonthlyTrends($userId, $year);

// Připrav data pro graf
$months = range(1, 12);
$monthNames = ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
$monthlyData = array_fill(1, 12, ['revenue' => 0, 'cost' => 0, 'profit' => 0]);

foreach ($monthlyTrends as $trend) {
    $monthlyData[$trend['month']] = [
        'revenue' => (float) $trend['revenue'],
        'cost' => (float) $trend['cost'],
        'profit' => (float) $trend['profit']
    ];
}

$title = 'Kompletní analytika výnosů';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Analytika výnosů a nákladů</h2>
    <div class="d-flex gap-2">
        <a href="/app/orders/shipping-mapping.php" class="btn btn-outline-info btn-sm">
            <i class="bi bi-truck me-1"></i>Doprava
        </a>
        <a href="/app/orders/billing-mapping.php" class="btn btn-outline-info btn-sm">
            <i class="bi bi-credit-card me-1"></i>Platby
        </a>
        <a href="/app/orders/" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Zpět
        </a>
    </div>
</div>

<!-- Filtry -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Rok pro graf</label>
                <select class="form-select" name="year">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Datum od (přehledy)</label>
                <input type="date" class="form-control" name="date_from" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Datum do (přehledy)</label>
                <input type="date" class="form-control" name="date_to" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Aktualizovat</button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="/app/orders/analytics.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-cart" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($analytics['totals']['order_count'] ?? 0) ?></h3>
            <p>Objednávek</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <i class="bi bi-cash-stack" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($analytics['totals']['total_revenue'] ?? 0) ?></h3>
            <p>Celkový obrat</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-receipt" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($analytics['totals']['total_cost'] ?? 0) ?></h3>
            <p>Celkové náklady</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="bi bi-graph-up-arrow" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($analytics['totals']['total_profit'] ?? 0) ?></h3>
            <p>Celkový zisk</p>
            <small>Marže: <?= number_format($analytics['totals']['avg_margin'] ?? 0, 1) ?>%</small>
        </div>
    </div>
</div>

<!-- Graf měsíčního vývoje -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-graph-up me-2"></i>Měsíční vývoj výnosů a nákladů (<?= $year ?>)
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="80"></canvas>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- TOP produkty -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-trophy me-2"></i>TOP 15 produktů podle zisku
            </div>
            <div class="card-body p-0">
                <?php if (empty($topProducts)): ?>
                    <div class="empty-state">
                        <i class="bi bi-box"></i>
                        <p class="mb-0">Žádné produkty</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Produkt</th>
                                    <th>Kód</th>
                                    <th class="text-end">Prodáno</th>
                                    <th class="text-end">Zisk</th>
                                    <th class="text-end">Marže</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; ?>
                                <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($rank <= 3): ?>
                                            <span class="badge bg-warning">🏆 <?= $rank ?></span>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= e(truncate($product['item_name'], 35)) ?></strong>
                                        <?php if ($product['manufacturer']): ?>
                                            <br><small class="text-muted"><?= e($product['manufacturer']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="small"><?= e($product['item_code'] ?? '-') ?></code></td>
                                    <td class="text-end"><?= $product['total_amount'] ?> ks</td>
                                    <td class="text-end">
                                        <strong class="<?= $product['total_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= formatPrice($product['total_profit']) ?>
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <small><?= number_format($product['avg_margin'] ?? 0, 1) ?>%</small>
                                    </td>
                                </tr>
                                <?php $rank++; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Podle statusu -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-clipboard-check me-2"></i>Podle statusu objednávky
            </div>
            <div class="card-body">
                <?php if (empty($analytics['by_status'])): ?>
                    <p class="text-muted mb-0">Žádná data</p>
                <?php else: ?>
                    <?php foreach ($analytics['by_status'] as $status): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?= e($status['status']) ?></strong>
                            <br><small class="text-muted"><?= $status['count'] ?> objednávek</small>
                        </div>
                        <div class="text-end">
                            <strong><?= formatPrice($status['revenue']) ?></strong>
                            <br><small class="<?= $status['profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                Zisk: <?= formatPrice($status['profit']) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Podle zdroje -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shop me-2"></i>Podle zdroje objednávky
            </div>
            <div class="card-body">
                <?php if (empty($analytics['by_source'])): ?>
                    <p class="text-muted mb-0">Žádná data</p>
                <?php else: ?>
                    <?php foreach ($analytics['by_source'] as $source): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?= e($source['source'] ?: 'Nezadáno') ?></strong>
                            <br><small class="text-muted"><?= $source['count'] ?> objednávek</small>
                        </div>
                        <div class="text-end">
                            <strong><?= formatPrice($source['revenue']) ?></strong>
                            <br><small class="<?= $source['profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                Zisk: <?= formatPrice($source['profit']) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Data pro graf
const monthlyData = <?= json_encode(array_values($monthlyData)) ?>;
const monthNames = <?= json_encode($monthNames) ?>;

// Vytvoření grafu
const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: monthNames,
        datasets: [
            {
                label: 'Obrat',
                data: monthlyData.map(m => m.revenue),
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            },
            {
                label: 'Náklady',
                data: monthlyData.map(m => m.cost),
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1
            },
            {
                label: 'Zisk',
                data: monthlyData.map(m => m.profit),
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + 
                               new Intl.NumberFormat('cs-CZ', {
                                   style: 'currency',
                                   currency: 'CZK',
                                   minimumFractionDigits: 0
                               }).format(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('cs-CZ', {
                            style: 'currency',
                            currency: 'CZK',
                            minimumFractionDigits: 0
                        }).format(value);
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
