<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Models\Order;

$orderModel = new Order();
$userId = $auth->userId();
$page = (int) get('page', 1);
$storeId = currentStoreId(); // Aktuální shop

// Filtry
$filters = [];
if (!empty(get('status'))) $filters['status'] = get('status');
if (!empty(get('date_from'))) $filters['date_from'] = get('date_from');
if (!empty(get('date_to'))) $filters['date_to'] = get('date_to');
if (!empty(get('source'))) $filters['source'] = get('source');

$data = $orderModel->getAll($userId, $page, 50, $filters, $storeId);
$orders = $data['orders'];
$pagination = $data['pagination'];

// Analytika
$analytics = $orderModel->getAnalytics($userId, $filters['date_from'] ?? null, $filters['date_to'] ?? null, $storeId);

$title = 'Objednávky a analytika';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Objednávky</h2>
    <div class="d-flex gap-2">
        <a href="/app/orders/feed-sources.php" class="btn btn-outline-primary">
            <i class="bi bi-rss me-2"></i>Feed zdroje
        </a>
        <a href="/app/orders/analytics.php" class="btn btn-primary">
            <i class="bi bi-graph-up me-2"></i>Kompletní analytika
        </a>
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

<!-- Filtry -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Datum od</label>
                <input type="date" class="form-control" name="date_from" value="<?= e(get('date_from')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Datum do</label>
                <input type="date" class="form-control" name="date_to" value="<?= e(get('date_to')) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Všechny</option>
                    <option value="Vyřízena" <?= get('status') === 'Vyřízena' ? 'selected' : '' ?>>Vyřízena</option>
                    <option value="Stornována" <?= get('status') === 'Stornována' ? 'selected' : '' ?>>Stornována</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrovat</button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <a href="/app/orders/" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="bi bi-cart-x"></i>
                <p class="mb-0">Žádné objednávky</p>
                <small class="text-muted">Nastavte feed zdroj a spusťte import</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kód</th>
                            <th>Datum</th>
                            <th>Status</th>
                            <th>Zdroj</th>
                            <th class="text-end">Obrat</th>
                            <th class="text-end">Náklady</th>
                            <th class="text-end">Zisk</th>
                            <th class="text-end">Marže</th>
                            <th width="80">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= e($order['order_code']) ?></strong></td>
                            <td><small><?= formatDate($order['order_date'], 'd.m.Y H:i') ?></small></td>
                            <td>
                                <?php if ($order['status'] === 'Vyřízena'): ?>
                                    <span class="badge bg-success">Vyřízena</span>
                                <?php elseif ($order['status'] === 'Stornována'): ?>
                                    <span class="badge bg-danger">Stornována</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= e($order['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= e($order['source'] ?? '-') ?></small></td>
                            <td class="text-end"><strong><?= formatPrice($order['total_revenue']) ?></strong></td>
                            <td class="text-end text-muted"><?= formatPrice($order['total_cost']) ?></td>
                            <td class="text-end">
                                <strong class="<?= $order['total_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= formatPrice($order['total_profit']) ?>
                                </strong>
                            </td>
                            <td class="text-end">
                                <small><?= number_format($order['margin_percent'], 1) ?>%</small>
                            </td>
                            <td>
                                <a href="/app/orders/detail.php?id=<?= $order['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php if ($pagination['has_prev']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>">Předchozí</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i === $pagination['current_page']): ?>
                        <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                    <?php elseif ($i === 1 || $i === $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                    <?php elseif ($i === 2 || $i === $pagination['total_pages'] - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_more']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>">Další</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
