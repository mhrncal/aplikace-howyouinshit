<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\Products\Models\FieldMapping;

$mappingModel = new FieldMapping();
$userId = $auth->userId();

// Akce
if (isPost()) {
    $action = post('action');
    $mappingId = (int) post('mapping_id');
    
    if ($action === 'delete') {
        if ($mappingModel->delete($mappingId, $userId)) {
            flash('success', 'Mapování smazáno');
        }
        redirect('/app/settings/field-mappings.php');
    }
    
    if ($action === 'toggle') {
        $mapping = $mappingModel->findById($mappingId, $userId);
        if ($mapping) {
            $mappingModel->update($mappingId, $userId, [
                'is_active' => !$mapping['is_active']
            ]);
            flash('success', $mapping['is_active'] ? 'Mapování deaktivováno' : 'Mapování aktivováno');
        }
        redirect('/app/settings/field-mappings.php');
    }
}

// Načti mappingy
$mappings = $mappingModel->getAllForUser($userId, null, 'product');
$variantMappings = $mappingModel->getAllForUser($userId, null, 'variant');

$title = 'Správa XML mapování';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>XML Field Mapping</h2>
    <a href="/app/settings/field-mappings-create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Přidat nové mapování
    </a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Co je field mapping?</strong> 
    Určuje jak se XML elementy z feedu mapují na sloupce v databázi. 
    Můžeš přidávat nová pole bez programování!
</div>

<!-- PRODUKTY -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-box-seam me-2"></i>Mapování pro produkty
            <span class="badge bg-primary"><?= count($mappings) ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="150">DB Sloupec</th>
                        <th>XML Cesta</th>
                        <th>Alternativy</th>
                        <th width="120">Transformace</th>
                        <th width="100">Default</th>
                        <th width="80">Povinné</th>
                        <th width="80">Status</th>
                        <th width="150">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mappings)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Žádná mapování. <a href="/app/settings/field-mappings-create.php">Přidat první</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mappings as $mapping): ?>
                            <tr>
                                <td>
                                    <code class="text-primary"><?= e($mapping['db_column']) ?></code>
                                    <?php if ($mapping['is_required']): ?>
                                        <span class="badge bg-danger ms-1">!</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= e($mapping['xml_path']) ?></code>
                                </td>
                                <td>
                                    <?php if (!empty($mapping['xml_path_alt'])): ?>
                                        <small class="text-muted">
                                            <code><?= e($mapping['xml_path_alt']) ?></code>
                                            <?php if (!empty($mapping['xml_path_alt2'])): ?>
                                                <br><code><?= e($mapping['xml_path_alt2']) ?></code>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($mapping['transform_type'] !== 'none'): ?>
                                        <span class="badge bg-info"><?= e($mapping['transform_type']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= !empty($mapping['default_value']) ? e($mapping['default_value']) : '-' ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($mapping['is_required']): ?>
                                        <span class="badge bg-danger">Ano</span>
                                    <?php else: ?>
                                        <span class="text-muted">Ne</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($mapping['is_active']): ?>
                                        <span class="badge bg-success">Aktivní</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Neaktivní</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/app/settings/field-mappings-edit.php?id=<?= $mapping['id'] ?>" 
                                           class="btn btn-outline-primary" title="Upravit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Opravdu <?= $mapping['is_active'] ? 'deaktivovat' : 'aktivovat' ?>?')">
                                            <?= csrf() ?>
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="mapping_id" value="<?= $mapping['id'] ?>">
                                            <button type="submit" class="btn btn-outline-warning" title="Zapnout/Vypnout">
                                                <i class="bi bi-power"></i>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Opravdu smazat?')">
                                            <?= csrf() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="mapping_id" value="<?= $mapping['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Smazat">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VARIANTY -->
<?php if (!empty($variantMappings)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-diagram-3 me-2"></i>Mapování pro varianty
            <span class="badge bg-primary"><?= count($variantMappings) ?></span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>DB Sloupec</th>
                        <th>XML Cesta</th>
                        <th>Transformace</th>
                        <th>Status</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variantMappings as $mapping): ?>
                        <tr>
                            <td><code><?= e($mapping['db_column']) ?></code></td>
                            <td><code><?= e($mapping['xml_path']) ?></code></td>
                            <td>
                                <?php if ($mapping['transform_type'] !== 'none'): ?>
                                    <span class="badge bg-info"><?= e($mapping['transform_type']) ?></span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($mapping['is_active']): ?>
                                    <span class="badge bg-success">Aktivní</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Neaktivní</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/app/settings/field-mappings-edit.php?id=<?= $mapping['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mt-4">
    <a href="/app/settings/" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Zpět na nastavení
    </a>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
