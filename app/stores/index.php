<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Models\Store;

$storeModel = new Store();
$userId = $auth->userId();

// CRUD
if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/stores/');
    }
    
    $action = post('action');
    
    try {
        switch ($action) {
            case 'create':
                $data = [
                    'user_id' => $userId,
                    'name' => post('name'),
                    'code' => post('code'),
                    'currency' => post('currency', 'CZK'),
                    'cost_sharing_mode' => post('cost_sharing_mode', 'own'),
                    'global_cost_allocation_percent' => (float) post('global_cost_allocation_percent', 0),
                    'is_active' => 1
                ];
                
                $storeModel->create($data);
                flash('success', 'E-shop vytvořen');
                break;
                
            case 'update':
                $id = (int) post('store_id');
                $data = [
                    'name' => post('name'),
                    'currency' => post('currency'),
                    'cost_sharing_mode' => post('cost_sharing_mode'),
                    'global_cost_allocation_percent' => (float) post('global_cost_allocation_percent')
                ];
                
                if ($storeModel->update($id, $userId, $data)) {
                    flash('success', 'E-shop aktualizován');
                }
                break;
                
            case 'toggle_active':
                $id = (int) post('store_id');
                if ($storeModel->toggleActive($id, $userId)) {
                    flash('success', 'Status změněn');
                }
                break;
                
            case 'delete':
                $id = (int) post('store_id');
                $storeModel->delete($id, $userId);
                flash('success', 'E-shop smazán');
                break;
        }
    } catch (\Exception $e) {
        flash('error', $e->getMessage());
    }
    
    redirect('/app/stores/');
}

$stores = $storeModel->getAllForUser($userId);

$title = 'Správa e-shopů';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>Moje E-shopy</h2>
        <p class="text-muted mb-0">Spravujte své e-shopy a nastavení nákladů</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-2"></i>Nový e-shop
    </button>
</div>

<div class="row">
    <?php foreach ($stores as $store): ?>
        <?php $stats = $storeModel->getStats($store['id']); ?>
        <div class="col-md-6 mb-4">
            <div class="card h-100 <?= !$store['is_active'] ? 'opacity-50' : '' ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            🏪 <?= e($store['name']) ?>
                            <?php if (!$store['is_active']): ?>
                                <span class="badge bg-secondary ms-2">Neaktivní</span>
                            <?php endif; ?>
                            <?php if ($store['id'] == currentStoreId()): ?>
                                <span class="badge bg-success ms-2">Aktivní</span>
                            <?php endif; ?>
                        </h5>
                        <small class="text-muted">Kód: <?= e($store['code']) ?></small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick='editStore(<?= json_encode($store) ?>)'>
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-4 text-center">
                            <div class="fs-4 fw-bold text-primary"><?= $stats['products'] ?></div>
                            <small class="text-muted">Produktů</small>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fs-4 fw-bold text-success"><?= $stats['orders'] ?></div>
                            <small class="text-muted">Objednávek</small>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fs-4 fw-bold text-warning"><?= $stats['costs'] ?></div>
                            <small class="text-muted">Nákladů</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-2">
                        <strong>Měna:</strong> <?= e($store['currency']) ?>
                    </div>
                    
                    <div class="mb-2">
                        <strong>Režim nákladů:</strong>
                        <?php
                        $modes = [
                            'own' => '📋 Vlastní náklady',
                            'shared' => '🌍 Globální náklady',
                            'combined' => '🔄 Kombinované'
                        ];
                        ?>
                        <span class="badge bg-info"><?= $modes[$store['cost_sharing_mode']] ?></span>
                    </div>
                    
                    <?php if ($store['cost_sharing_mode'] !== 'own'): ?>
                    <div class="mb-2">
                        <strong>Alokace globálních nákladů:</strong>
                        <span class="badge bg-secondary"><?= $store['global_cost_allocation_percent'] ?>%</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="btn-group btn-group-sm w-100">
                        <?php if ($store['id'] != currentStoreId()): ?>
                        <a href="/app/switch-store.php?store_id=<?= $store['id'] ?>" 
                           class="btn btn-outline-success">
                            <i class="bi bi-arrow-right-circle me-1"></i>Přepnout
                        </a>
                        <?php endif; ?>
                        
                        <form method="POST" class="flex-fill">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="store_id" value="<?= $store['id'] ?>">
                            <button type="submit" class="btn btn-outline-warning w-100">
                                <i class="bi bi-power me-1"></i>
                                <?= $store['is_active'] ? 'Deaktivovat' : 'Aktivovat' ?>
                            </button>
                        </form>
                        
                        <?php if ($stats['products'] == 0 && $stats['orders'] == 0): ?>
                        <form method="POST" class="flex-fill" 
                              onsubmit="return confirm('Opravdu smazat tento e-shop?')">
                            <?= csrf() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="store_id" value="<?= $store['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrf() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Nový e-shop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Název e-shopu *</label>
                        <input type="text" class="form-control" name="name" required 
                               placeholder="např. LasiLueta CZ">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kód (unikátní) *</label>
                        <input type="text" class="form-control" name="code" required 
                               placeholder="např. lasilueta-cz">
                        <small class="text-muted">Použijte pouze malá písmena, čísla a pomlčky</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Měna</label>
                        <select class="form-select" name="currency">
                            <option value="CZK">CZK</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Režim nákladů</label>
                        <select class="form-select" name="cost_sharing_mode" id="create_cost_mode">
                            <option value="own">Vlastní náklady</option>
                            <option value="shared">Globální náklady</option>
                            <option value="combined">Kombinované</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="create_allocation_field" style="display: none;">
                        <label class="form-label">Alokace globálních nákladů (%)</label>
                        <input type="number" class="form-control" name="global_cost_allocation_percent" 
                               min="0" max="100" value="0" step="0.01">
                        <small class="text-muted">Jaká část globálních nákladů připadá na tento shop</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">Vytvořit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= csrf() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="store_id" id="edit_store_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Upravit e-shop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Název e-shopu *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Měna</label>
                        <select class="form-select" name="currency" id="edit_currency">
                            <option value="CZK">CZK</option>
                            <option value="EUR">EUR</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Režim nákladů</label>
                        <select class="form-select" name="cost_sharing_mode" id="edit_cost_mode">
                            <option value="own">Vlastní náklady</option>
                            <option value="shared">Globální náklady</option>
                            <option value="combined">Kombinované</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="edit_allocation_field">
                        <label class="form-label">Alokace globálních nákladů (%)</label>
                        <input type="number" class="form-control" name="global_cost_allocation_percent" 
                               id="edit_allocation" min="0" max="100" step="0.01">
                        <small class="text-muted">Jaká část globálních nákladů připadá na tento shop</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">Uložit změny</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Zobraz/skryj alokaci podle režimu
document.getElementById('create_cost_mode').addEventListener('change', function() {
    const field = document.getElementById('create_allocation_field');
    field.style.display = (this.value === 'own') ? 'none' : 'block';
});

// Edit shop
function editStore(store) {
    document.getElementById('edit_store_id').value = store.id;
    document.getElementById('edit_name').value = store.name;
    document.getElementById('edit_currency').value = store.currency;
    document.getElementById('edit_cost_mode').value = store.cost_sharing_mode;
    document.getElementById('edit_allocation').value = store.global_cost_allocation_percent;
    
    const field = document.getElementById('edit_allocation_field');
    field.style.display = (store.cost_sharing_mode === 'own') ? 'none' : 'block';
    
    document.getElementById('edit_cost_mode').addEventListener('change', function() {
        field.style.display = (this.value === 'own') ? 'none' : 'block';
    });
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
