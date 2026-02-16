<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Models\BillingCost;

$model = new BillingCost();
$userId = $auth->userId();

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/orders/billing-mapping.php');
    }
    
    $action = post('action');
    $id = (int) post('id');
    
    if ($action === 'update') {
        $data = [
            'billing_name' => post('name'),
            'cost_fixed' => (float) post('cost_fixed'),
            'cost_percent' => (float) post('cost_percent'),
            'is_positive' => post('is_positive') === '1' ? 1 : 0
        ];
        $model->update($id, $userId, $data);
        flash('success', 'Náklady aktualizovány');
    } elseif ($action === 'delete') {
        $model->delete($id, $userId);
        flash('success', 'Mapping smazán');
    }
    
    redirect('/app/orders/billing-mapping.php');
}

$mappings = $model->getAll($userId);
$title = 'Mapování nákladů plateb';
ob_start();
?>

<h2 class="mb-4">Mapování nákladů na platby</h2>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Nastavte náklady pro každý typ platby (fixní poplatek + % z objednávky).
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($mappings)): ?>
            <div class="empty-state">
                <i class="bi bi-credit-card"></i>
                <p class="mb-0">Žádné mapování plateb</p>
                <small class="text-muted">Spusťte import objednávek pro automatické vytvoření</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kód</th>
                            <th>Název</th>
                            <th width="120">Fixní (Kč)</th>
                            <th width="120">Procento (%)</th>
                            <th width="150">Typ</th>
                            <th width="100">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mappings as $m): ?>
                        <tr>
                            <form method="POST" class="d-inline">
                                <?= csrf() ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                
                                <td><code><?= e($m['billing_code']) ?></code></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="name" value="<?= e($m['billing_name']) ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="cost_fixed" value="<?= $m['cost_fixed'] ?>" step="0.01">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="cost_percent" value="<?= $m['cost_percent'] ?>" step="0.01">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="is_positive">
                                        <option value="1" <?= $m['is_positive'] ? 'selected' : '' ?>>✅ Pozitivní</option>
                                        <option value="0" <?= !$m['is_positive'] ? 'selected' : '' ?>>❌ Negativní</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="submit" class="btn btn-outline-success" title="Uložit">
                                            <i class="bi bi-check"></i>
                                        </button>
                            </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Smazat?')">
                                            <?= csrf() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
