<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\Products\Models\FieldMapping;

$mappingModel = new FieldMapping();
$userId = $auth->userId();

// POST: Uložení mappingu
if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/products/field-mapping.php');
    }
    
    $action = post('action');
    
    if ($action === 'save_mapping') {
        $dbColumn = post('db_column');
        $xmlPath = post('xml_path');
        $dataType = post('data_type', 'string');
        $defaultValue = post('default_value');
        $entityType = post('field_type', 'product');
        
        $data = [
            'user_id' => $userId,
            'db_column' => $dbColumn,
            'xml_path' => $xmlPath,
            'data_type' => $dataType,
            'default_value' => $defaultValue,
            'field_type' => $entityType,
            'is_active' => 1
        ];
        
        if ($mappingModel->create($data)) {
            flash('success', 'Mapování přidáno');
        } else {
            flash('error', 'Chyba při ukládání');
        }
        
        redirect('/app/products/field-mapping.php');
    }
    
    if ($action === 'delete') {
        $id = (int) post('id');
        if ($mappingModel->delete($id, $userId)) {
            flash('success', 'Mapování smazáno');
        }
        redirect('/app/products/field-mapping.php');
    }
    
    if ($action === 'toggle_active') {
        $id = (int) post('id');
        $mapping = $mappingModel->findById($id, $userId);
        
        if ($mapping) {
            $mappingModel->update($id, $userId, [
                'is_active' => $mapping['is_active'] ? 0 : 1
            ]);
            flash('success', 'Stav změněn');
        }
        
        redirect('/app/products/field-mapping.php');
    }
}

// Načti existující mappingy
$productMappings = $mappingModel->getAllForUser($userId, null, 'product');
$variantMappings = $mappingModel->getAllForUser($userId, null, 'variant');

$title = 'Mapování XML polí';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-diagram-3 me-2"></i>Mapování XML polí</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMappingModal" data-entity="product">
            <i class="bi bi-plus-circle me-2"></i>Nové mapování produktu
        </button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMappingModal" data-entity="variant">
            <i class="bi bi-plus-circle me-2"></i>Nové mapování varianty
        </button>
    </div>
</div>

<!-- Info box -->
<div class="alert alert-info">
    <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Co je to mapování polí?</h5>
    <p class="mb-0">
        Mapování určuje, které XML elementy z feedu se uloží do kterých sloupců v databázi. 
        Například XML element <code>&lt;NAME&gt;</code> se mapuje na sloupec <code>name</code>.
        <br><br>
        <strong>Pokud změníš strukturu databáze</strong> (přidáš nový sloupec), můžeš zde přidat mapování a produkt se začne automaticky importovat do nového sloupce.
    </p>
</div>

<!-- PRODUKTY -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Mapování produktů (<?= count($productMappings) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($productMappings)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Žádná mapování produktů.<br>Používají se výchozí nastavení.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>DB Sloupec</th>
                            <th>XML Cesta</th>
                            <th>Typ dat</th>
                            <th>Výchozí hodnota</th>
                            <th>Status</th>
                            <th width="150">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productMappings as $mapping): ?>
                        <tr>
                            <td><code><?= e($mapping['db_column']) ?></code></td>
                            <td><code><?= e($mapping['xml_path']) ?></code></td>
                            <td>
                                <span class="badge bg-secondary"><?= e($mapping['data_type']) ?></span>
                            </td>
                            <td>
                                <?php if ($mapping['default_value']): ?>
                                    <code><?= e($mapping['default_value']) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?= $mapping['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $mapping['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                        <?= $mapping['is_active'] ? 'Aktivní' : 'Neaktivní' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu smazat?')">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $mapping['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- VARIANTY -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-grid-3x3 me-2"></i>Mapování variant (<?= count($variantMappings) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($variantMappings)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">Žádná mapování variant.<br>Používají se výchozí nastavení.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>DB Sloupec</th>
                            <th>XML Cesta</th>
                            <th>Typ dat</th>
                            <th>Výchozí hodnota</th>
                            <th>Status</th>
                            <th width="150">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variantMappings as $mapping): ?>
                        <tr>
                            <td><code><?= e($mapping['db_column']) ?></code></td>
                            <td><code><?= e($mapping['xml_path']) ?></code></td>
                            <td>
                                <span class="badge bg-secondary"><?= e($mapping['data_type']) ?></span>
                            </td>
                            <td>
                                <?php if ($mapping['default_value']): ?>
                                    <code><?= e($mapping['default_value']) ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="id" value="<?= $mapping['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $mapping['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                        <?= $mapping['is_active'] ? 'Aktivní' : 'Neaktivní' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu smazat?')">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $mapping['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: Přidat mapování -->
<div class="modal fade" id="addMappingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= csrf() ?>
                <input type="hidden" name="action" value="save_mapping">
                <input type="hidden" name="field_type" id="modal_field_type" value="product">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle me-2"></i>
                        <span id="modal_title">Nové mapování produktu</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>⚠️ Pozor:</strong> Před přidáním mapování musíš nejdřív přidat nový sloupec do databáze!
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">DB Sloupec <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="db_column" required
                               placeholder="Např: custom_field, supplier_code">
                        <div class="form-text">
                            Název sloupce v tabulce <code>products</code> nebo <code>product_variants</code>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">XML Cesta <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="xml_path" required
                               placeholder="Např: CUSTOM_FIELD, SUPPLIER/CODE">
                        <div class="form-text">
                            XPath k elementu v XML. Použij <code>/</code> pro vnořené elementy.<br>
                            Příklady: <code>NAME</code>, <code>PRICE_VAT</code>, <code>IMAGES/IMAGE</code>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kam uložit? <span class="text-danger">*</span></label>
                                <select class="form-select" name="target_type" id="target_type" required>
                                    <option value="column">📦 Standardní sloupec (rychlé vyhledávání)</option>
                                    <option value="json">🔧 Custom pole (flexibilní)</option>
                                </select>
                                <div class="form-text">
                                    <strong>Standardní sloupec:</strong> Rychlejší, ale omezený počet<br>
                                    <strong>Custom pole:</strong> Neomezené, trochu pomalejší
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transformer</label>
                                <select class="form-select" name="transformer">
                                    <option value="">Žádný</option>
                                    <option value="strip_tags">strip_tags (odstraní HTML)</option>
                                    <option value="trim">trim (odstraní mezery)</option>
                                    <option value="strtoupper">VELKÁ PÍSMENA</option>
                                    <option value="strtolower">malá písmena</option>
                                    <option value="ucfirst">První velké</option>
                                    <option value="ucwords">Každé Slovo Velké</option>
                                </select>
                                <div class="form-text">
                                    Úprava hodnoty před uložením
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Typ dat</label>
                                <select class="form-select" name="data_type">
                                    <option value="string">String (text)</option>
                                    <option value="int">Integer (celé číslo)</option>
                                    <option value="float">Float (desetinné číslo)</option>
                                    <option value="bool">Boolean (ano/ne)</option>
                                    <option value="date">Date (datum)</option>
                                    <option value="json">JSON (pole, objekt)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Výchozí hodnota</label>
                                <input type="text" class="form-control" name="default_value"
                                       placeholder="Nepovinné">
                                <div class="form-text">
                                    Pokud XML element neexistuje
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">💡 Příklady mapování:</h6>
                            <ul class="mb-0 small">
                                <li><code>db_column</code>: <strong>supplier_name</strong>, <code>xml_path</code>: <strong>MANUFACTURER</strong></li>
                                <li><code>db_column</code>: <strong>weight</strong>, <code>xml_path</code>: <strong>LOGISTIC/WEIGHT</strong>, <code>data_type</code>: <strong>float</strong></li>
                                <li><code>db_column</code>: <strong>is_new</strong>, <code>xml_path</code>: <strong>FLAGS/FLAG[@CODE='new']/ACTIVE</strong>, <code>data_type</code>: <strong>bool</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušit</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Přidat mapování
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Změna typu entity v modalu
document.querySelectorAll('[data-bs-target="#addMappingModal"]').forEach(btn => {
    btn.addEventListener('click', function() {
        const entityType = this.getAttribute('data-entity');
        document.getElementById('modal_field_type').value = entityType;
        document.getElementById('modal_title').textContent = 
            entityType === 'product' ? 'Nové mapování produktu' : 'Nové mapování varianty';
    });
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
