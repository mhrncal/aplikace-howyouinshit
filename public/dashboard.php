<?php
require_once __DIR__ . '/../bootstrap.php';

$auth->requireAuth();

use App\Models\User;
use App\Core\Database;

$db = Database::getInstance();
$userModel = new User();

// Získání statistik
$userId = $auth->userId();
$isSuperAdmin = $auth->isSuperAdmin();

// Počet produktů
if ($isSuperAdmin) {
    $productsCount = $db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
    $usersCount = $userModel->count();
    $activeUsersCount = $userModel->countActive();
} else {
    $productsCount = $db->fetchOne("SELECT COUNT(*) as count FROM products WHERE user_id = ?", [$userId])['count'];
    $usersCount = null;
    $activeUsersCount = null;
}

// Počet feed sources
$feedSourcesCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM feed_sources WHERE " . ($isSuperAdmin ? "1=1" : "user_id = ?"),
    $isSuperAdmin ? [] : [$userId]
)['count'];

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

// Top produkty podle ceny
$topProducts = $db->fetchAll(
    "SELECT name, standard_price, category, stock 
     FROM products 
     WHERE " . ($isSuperAdmin ? "1=1" : "user_id = ?") . " AND standard_price > 0
     ORDER BY standard_price DESC
     LIMIT 5",
    $isSuperAdmin ? [] : [$userId]
);

$title = 'Dashboard';
ob_start();
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="stat-card">
            <i class="bi bi-box-seam" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($productsCount) ?></h3>
            <p>Celkem produktů</p>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="bi bi-link-45deg" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($feedSourcesCount) ?></h3>
            <p>Aktivní feed zdroje</p>
        </div>
    </div>
    
    <?php if ($isSuperAdmin): ?>
    <div class="col-md-4 mb-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-people" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= number_format($activeUsersCount) ?> / <?= number_format($usersCount) ?></h3>
            <p>Aktivní uživatelé</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Recent Imports -->
    <div class="col-md-7 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Poslední importy</span>
                <a href="/import-logs.php" class="btn btn-sm btn-outline-primary">Zobrazit vše</a>
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
                                    <th>Status</th>
                                    <th>Záznamy</th>
                                    <th>Čas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentImports as $import): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($import['feed_name'] ?? 'N/A') ?></strong>
                                        <?php if ($isSuperAdmin): ?>
                                            <br><small class="text-muted"><?= e($import['user_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'processing' => 'warning',
                                            'pending' => 'secondary'
                                        ];
                                        $statusLabels = [
                                            'completed' => 'Dokončeno',
                                            'failed' => 'Chyba',
                                            'processing' => 'Probíhá',
                                            'pending' => 'Čeká'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $statusColors[$import['status']] ?>">
                                            <?= $statusLabels[$import['status']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= number_format($import['processed_records']) ?> / <?= number_format($import['total_records']) ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= formatDate($import['created_at']) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="col-md-5 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-trophy me-2"></i>Top 5 produktů podle ceny
            </div>
            <div class="card-body p-0">
                <?php if (empty($topProducts)): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p class="mb-0">Zatím žádné produkty</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($topProducts as $i => $product): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-primary">#<?= $i + 1 ?></span>
                                        <strong><?= truncate(e($product['name']), 40) ?></strong>
                                    </div>
                                    <small class="text-muted">
                                        <?= e($product['category'] ?? 'Bez kategorie') ?> • 
                                        Sklad: <?= number_format($product['stock']) ?> ks
                                    </small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-primary"><?= formatPrice($product['standard_price']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-lightning-charge me-2"></i>Rychlé akce
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <a href="/products.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-box-seam me-2"></i>
                    Zobrazit produkty
                </a>
            </div>
            <div class="col-md-3">
                <a href="/feed-sources.php" class="btn btn-outline-success w-100">
                    <i class="bi bi-link-45deg me-2"></i>
                    Spravovat feedy
                </a>
            </div>
            <div class="col-md-3">
                <a href="/import-now.php" class="btn btn-outline-warning w-100">
                    <i class="bi bi-download me-2"></i>
                    Spustit import
                </a>
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="col-md-3">
                <a href="/users.php" class="btn btn-outline-danger w-100">
                    <i class="bi bi-people me-2"></i>
                    Spravovat uživatele
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layouts/main.php';
