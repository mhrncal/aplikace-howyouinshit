<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\Cost;

$costModel = new Cost();
$userId = $auth->userId();
$page = (int) get('page', 1);

// Filtry
$filters = [];

if (!empty(get('type'))) {
    $filters['type'] = get('type');
}

if (!empty(get('frequency'))) {
    $filters['frequency'] = get('frequency');
}

if (!empty(get('category'))) {
    $filters['category'] = get('category');
}

if (get('active') !== null && get('active') !== '') {
    $filters['is_active'] = get('active') === '1';
}

// CRUD akce
if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/costs/');
    }
    
    $action = post('action');
    $costId = (int) post('cost_id');
    
    if ($action === 'delete') {
        if ($costModel->delete($costId, $userId)) {
            flash('success', 'Náklad smazán');
        }
    }
    
    redirect('/app/costs/');
}

// Data
$data = $costModel->getAll($userId, $page, 25, $filters);
$costs = $data['costs'];
$pagination = $data['pagination'];

// Analytika - aktuální měsíc
$currentYear = date('Y');
$currentMonth = date('n');
$monthlyData = $costModel->getMonthlyBreakdown($userId, $currentYear, $currentMonth);

// Kategorie pro filtr
$categories = $costModel->getCategories($userId);

$title = 'Náklady';
ob_start();
?>

<!-- Přehledy -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-wallet2" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($monthlyData['total']) ?></h3>
            <p>Celkem tento měsíc</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <i class="bi bi-graph-up-arrow" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($monthlyData['fixed']) ?></h3>
            <p>Fixní náklady</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <i class="bi bi-graph-down-arrow" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= formatPrice($monthlyData['variable']) ?></h3>
            <p>Variabilní náklady</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
            <i class="bi bi-pie-chart" style="font-size: 2rem; opacity: 0.8;"></i>
            <h3><?= count($costs) ?></h3>
            <p>Aktivních položek</p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i>Rozložení podle kategorií (tento měsíc)
            </div>
            <div class="card-body">
                <?php if (empty($monthlyData['by_category'])): ?>
                    <p class="text-muted mb-0">Zatím žádné náklady</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($monthlyData['by_category'] as $category => $amount): ?>
                        <div class="col-md-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><?= e($category) ?>:</strong>
                                <span class="badge bg-primary"><?= formatPrice($amount) ?></span>
                            </div>
                            <div class="progress mt-1" style="height: 8px;">
                                <div class="progress-bar" style="width: <?= ($amount / $monthlyData['total']) * 100 ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Hlavní tabulka -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Seznam nákladů</span>
        <div class="d-flex gap-2">
            <a href="/app/costs/analytics.php" class="btn btn-sm btn-outline-info">
                <i class="bi bi-graph-up me-1"></i>Analytika
            </a>
            <a href="/app/costs/create.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Přidat náklad
            </a>
        </div>
    </div>
    
    <!-- Filtry -->
    <div class="card-body border-bottom">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="type">
                    <option value="">Všechny typy</option>
                    <option value="fixed" <?= get('type') === 'fixed' ? 'selected' : '' ?>>Fixní</option>
                    <option value="variable" <?= get('type') === 'variable' ? 'selected' : '' ?>>Variabilní</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="frequency">
                    <option value="">Všechny frekvence</option>
                    <option value="daily" <?= get('frequency') === 'daily' ? 'selected' : '' ?>>Denní</option>
                    <option value="weekly" <?= get('frequency') === 'weekly' ? 'selected' : '' ?>>Týdenní</option>
                    <option value="monthly" <?= get('frequency') === 'monthly' ? 'selected' : '' ?>>Měsíční</option>
                    <option value="quarterly" <?= get('frequency') === 'quarterly' ? 'selected' : '' ?>>Kvartální</option>
                    <option value="yearly" <?= get('frequency') === 'yearly' ? 'selected' : '' ?>>Roční</option>
                    <option value="once" <?= get('frequency') === 'once' ? 'selected' : '' ?>>Jednorázově</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="category">
                    <option value="">Všechny kategorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['category']) ?>" <?= get('category') === $cat['category'] ? 'selected' : '' ?>>
                            <?= e($cat['category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="active">
                    <option value="">Všechny stavy</option>
                    <option value="1" <?= get('active') === '1' ? 'selected' : '' ?>>Aktivní</option>
                    <option value="0" <?= get('active') === '0' ? 'selected' : '' ?>>Neaktivní</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Filtrovat</button>
                <a href="/app/costs/" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    
    <div class="card-body p-0">
        <?php if (empty($costs)): ?>
            <div class="empty-state">
                <i class="bi bi-wallet2"></i>
                <p class="mb-0">Žádné náklady</p>
                <small class="text-muted">Přidejte první náklad pro sledování vašich výdajů</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>Typ</th>
                            <th>Frekvence</th>
                            <th>Kategorie</th>
                            <th>Částka</th>
                            <th>Měsíčně</th>
                            <th>Období</th>
                            <th>Status</th>
                            <th width="120">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($costs as $cost): ?>
                        <?php
                        $monthlyAmount = match($cost['frequency']) {
                            'daily' => $cost['amount'] * 30,
                            'weekly' => $cost['amount'] * 4.33,
                            'monthly' => $cost['amount'],
                            'quarterly' => $cost['amount'] / 3,
                            'yearly' => $cost['amount'] / 12,
                            'once' => 0,
                            default => $cost['amount']
                        };
                        $freqLabels = [
                            'daily' => 'Denně',
                            'weekly' => 'Týdně',
                            'monthly' => 'Měsíčně',
                            'quarterly' => 'Kvartálně',
                            'yearly' => 'Ročně',
                            'once' => 'Jednorázově'
                        ];
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($cost['name']) ?></strong>
                                <?php if ($cost['description']): ?>
                                    <br><small class="text-muted"><?= e(truncate($cost['description'], 50)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cost['type'] === 'fixed'): ?>
                                    <span class="badge bg-primary">Fixní</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Variabilní</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $freqLabels[$cost['frequency']] ?? $cost['frequency'] ?></td>
                            <td><?= e($cost['category'] ?? '-') ?></td>
                            <td><strong><?= formatPrice($cost['amount']) ?></strong></td>
                            <td class="text-muted"><?= formatPrice($monthlyAmount) ?></td>
                            <td>
                                <small>
                                    <?= formatDate($cost['start_date'], 'd.m.Y') ?>
                                    <?php if ($cost['end_date']): ?>
                                        - <?= formatDate($cost['end_date'], 'd.m.Y') ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($cost['is_active']): ?>
                                    <span class="badge bg-success">Aktivní</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Neaktivní</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/app/costs/edit.php?id=<?= $cost['id'] ?>" class="btn btn-outline-primary" title="Upravit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu smazat tento náklad?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cost_id" value="<?= $cost['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Smazat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
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
