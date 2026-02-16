<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Core\Database;
use App\Models\Order;
use App\Models\Cost;

$db = Database::getInstance();
$userId = $auth->userId();
$isSuperAdmin = $auth->isSuperAdmin();

// Statistiky produktů a feedů
$stats = [
    'products' => $db->fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE " . ($isSuperAdmin ? "1=1" : "user_id = ?"),
        $isSuperAdmin ? [] : [$userId]
    )['count'],
    'feed_sources' => $db->fetchOne(
        "SELECT COUNT(*) as count FROM feed_sources WHERE " . ($isSuperAdmin ? "1=1" : "user_id = ?"),
        $isSuperAdmin ? [] : [$userId]
    )['count'],
];

if ($isSuperAdmin) {
    $stats['users'] = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
    $stats['users_active'] = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
}

// Analytika výnosů (tento měsíc)
$orderModel = new Order();
$currentMonth = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$orderAnalytics = $orderModel->getAnalytics($userId, $currentMonth, $currentMonthEnd);

// Analytika nákladů (tento měsíc)
$costModel = new Cost();
$currentYear = date('Y');
$currentMonthNum = date('n');
$costData = $costModel->getMonthlyBreakdown($userId, $currentYear, $currentMonthNum);

// Poslední importy
$recentImports = $db->fetchAll(
    "SELECT il.*, u.name as user_name, fs.name as feed_name 
     FROM import_logs il
     LEFT JOIN users u ON il.user_id = u.id
     LEFT JOIN feed_sources fs ON il.feed_source_id = fs.id
     WHERE " . ($isSuperAdmin ? "1=1" : "il.user_id = ?") . "
     ORDER BY il.created_at DESC
     LIMIT 5",
    $isSuperAdmin ? [] : [$userId]
);

$title = 'Dashboard';
ob_start();
?>

<!-- Výnosy a náklady -->
<div class="row mb-4">
    <div class="col-md-12">
        <h5 class="mb-3">Finanční přehled (tento měsíc)</h5>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <i class="bi bi-cart-check" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($orderAnalytics['totals']['total_revenue'] ?? 0) ?></h3>
            <p>Obrat z objednávek</p>
            <small><?= number_format($orderAnalytics['totals']['order_count'] ?? 0) ?> objednávek</small>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="bi bi-graph-up-arrow" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($orderAnalytics['totals']['total_profit'] ?? 0) ?></h3>
            <p>Zisk z objednávek</p>
            <small>Marže: <?= number_format($orderAnalytics['totals']['avg_margin'] ?? 0, 1) ?>%</small>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-wallet2" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($costData['total'] ?? 0) ?></h3>
            <p>Provozní náklady</p>
            <small>Fixní: <?= formatPrice($costData['fixed'] ?? 0) ?></small>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
            <i class="bi bi-calculator" style="font-size: 2rem; opacity: 0.8;"></i>
            <?php 
            $totalRevenue = ($orderAnalytics['totals']['total_revenue'] ?? 0);
            $totalCosts = ($orderAnalytics['totals']['total_cost'] ?? 0) + ($costData['total'] ?? 0);
            $netProfit = $totalRevenue - $totalCosts;
            ?>
            <h3 class="<?= $netProfit >= 0 ? '' : 'text-warning' ?>"><?= formatPrice($netProfit) ?></h3>
            <p>Čistý zisk celkem</p>
            <small>Výnosy - všechny náklady</small>
        </div>
    </div>
</div>

<!-- Produkty a uživatelé -->
<div class="row mb-4">
    <div class="col-md-12">
        <h5 class="mb-3">Systémové statistiky</h5>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <i class="bi bi-box-seam" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($stats['products']) ?></h3>
            <p>Produktů v databázi</p>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="bi bi-link-45deg" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($stats['feed_sources']) ?></h3>
            <p>Feed zdrojů</p>
        </div>
    </div>
    
    <?php if ($isSuperAdmin): ?>
    <div class="col-md-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-people" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($stats['users_active']) ?> / <?= number_format($stats['users']) ?></h3>
            <p>Aktivní uživatelé</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Poslední importy
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentImports)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="mb-0">Zatím žádné importy</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Feed</th>
                                    <?php if ($isSuperAdmin): ?><th>Uživatel</th><?php endif; ?>
                                    <th>Status</th>
                                    <th>Záznamy</th>
                                    <th>Čas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentImports as $import): ?>
                                <tr>
                                    <td><?= e($import['feed_name'] ?? 'N/A') ?></td>
                                    <?php if ($isSuperAdmin): ?>
                                        <td><small><?= e($import['user_name']) ?></small></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php
                                        $badges = [
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'processing' => 'warning',
                                            'pending' => 'secondary'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badges[$import['status']] ?? 'secondary' ?>">
                                            <?= ucfirst($import['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($import['processed_records']) ?></td>
                                    <td><small><?= formatDate($import['created_at']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-lightning-charge me-2"></i>Rychlé akce
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <a href="/app/products/index.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-box-seam me-2"></i>Produkty
                </a>
            </div>
            <div class="col-md-3">
                <a href="/app/orders/" class="btn btn-outline-success w-100">
                    <i class="bi bi-cart-check me-2"></i>Objednávky
                </a>
            </div>
            <div class="col-md-3">
                <a href="/app/orders/analytics.php" class="btn btn-outline-info w-100">
                    <i class="bi bi-graph-up me-2"></i>Analytika výnosů
                </a>
            </div>
            <div class="col-md-3">
                <a href="/app/costs/" class="btn btn-outline-warning w-100">
                    <i class="bi bi-wallet2 me-2"></i>Provozní náklady
                </a>
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="col-md-3">
                <a href="/app/users/index.php" class="btn btn-outline-danger w-100">
                    <i class="bi bi-people me-2"></i>Uživatelé
                </a>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <a href="/app/settings/profile.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-person-circle me-2"></i>Můj profil
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
