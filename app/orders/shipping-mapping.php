<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Models\ShippingCost;

$model = new ShippingCost();
$userId = $auth->userId();

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/orders/shipping-mapping.php');
    }
    
    $action = post('action');
    $id = (int) post('id');
    
    if ($action === 'update') {
        $data = [
            'shipping_name' => post('name'),
            'cost' => (float) post('cost'),
            'is_positive' => post('is_positive') === '1' ? 1 : 0
        ];
        $model->update($id, $userId, $data);
        flash('success', 'Náklady aktualizovány');
    } elseif ($action === 'delete') {
        $model->delete($id, $userId);
        flash('success', 'Mapping smazán');
    }
    
    redirect('/app/orders/shipping-mapping.php');
}

$mappings = $model->getAll($userId);
$title = 'Mapování nákladů dopravy';
ob_start();
?>

<h2 class="mb-4">Mapování nákladů na dopravu</h2>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Nastavte náklady pro každý typ dopravy. Mapování se vytváří automaticky při prvním importu objednávek.
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($mappings)): ?>
            <div class="empty-state">
                <i class="bi bi-truck"></i>
                <p class="mb-0">Žádné mapování dopravy</p>
                <small class="text-muted">Spusťte import objednávek pro automatické vytvoření</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kód</th>
                            <th>Název</th>
                            <th width="150">Náklady (Kč)</th>
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
                                
                                <td><code><?= e($m['shipping_code']) ?></code></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="name" value="<?= e($m['shipping_name']) ?>">
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" 
                                           name="cost" value="<?= $m['cost'] ?>" step="0.01">
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
