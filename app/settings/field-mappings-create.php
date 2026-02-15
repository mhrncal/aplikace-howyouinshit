<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\Products\Models\FieldMapping;
use App\Core\Security;

$mappingModel = new FieldMapping();
$userId = $auth->userId();

// Zpracování formuláře
if (isPost()) {
    if (!Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/settings/field-mappings.php');
    }
    
    $data = [
        'db_column' => post('db_column'),
        'xml_path' => post('xml_path'),
        'xml_path_alt' => post('xml_path_alt'),
        'xml_path_alt2' => post('xml_path_alt2'),
        'transform_type' => post('transform_type', 'none'),
        'transform_custom' => post('transform_custom'),
        'default_value' => post('default_value'),
        'is_required' => post('is_required') === '1',
        'is_active' => post('is_active', '1') === '1',
        'field_type' => post('field_type', 'product'),
        'description' => post('description'),
        'feed_source_id' => post('feed_source_id') ?: null,
    ];
    
    if ($mappingModel->create($userId, $data)) {
        flash('success', 'Mapování vytvořeno');
        redirect('/app/settings/field-mappings.php');
    } else {
        flash('error', 'Chyba při vytváření mapování');
    }
}

// Dostupné sloupce
$availableColumns = $mappingModel->getAvailableColumns();

$title = 'Nové XML mapování';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-plus-circle me-2"></i>Přidat nové mapování
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf() ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">DB Sloupec *</label>
                                <select class="form-select" name="db_column" required>
                                    <option value="">-- Vyber sloupec --</option>
                                    <?php foreach ($availableColumns as $col): ?>
                                        <option value="<?= e($col['name']) ?>" <?= old('db_column') === $col['name'] ? 'selected' : '' ?>>
                                            <?= e($col['name']) ?> (<?= e($col['type']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">
                                    Sloupec v products tabulce kam se uloží data
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Typ pole</label>
                                <select class="form-select" name="field_type">
                                    <option value="product" <?= old('field_type', 'product') === 'product' ? 'selected' : '' ?>>Produkt</option>
                                    <option value="variant" <?= old('field_type') === 'variant' ? 'selected' : '' ?>>Varianta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">XML Cesta (XPath) *</label>
                        <input type="text" class="form-control" name="xml_path" 
                               value="<?= e(old('xml_path')) ?>" 
                               placeholder="např. WARRANTY nebo STOCK/AMOUNT nebo IMAGES/IMAGE[0]" required>
                        <div class="form-text">
                            Cesta k elementu v XML feedu. Příklady: <code>WARRANTY</code>, <code>STOCK/AMOUNT</code>, <code>IMAGES/IMAGE[0]</code>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alternativní cesta 1</label>
                                <input type="text" class="form-control" name="xml_path_alt" 
                                       value="<?= e(old('xml_path_alt')) ?>" 
                                       placeholder="např. MANUFACTURER">
                                <div class="form-text">
                                    Pokud hlavní cesta neexistuje, zkusí tuto
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alternativní cesta 2</label>
                                <input type="text" class="form-control" name="xml_path_alt2" 
                                       value="<?= e(old('xml_path_alt2')) ?>" 
                                       placeholder="např. BRAND">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Transformace</label>
                                <select class="form-select" name="transform_type">
                                    <option value="none" <?= old('transform_type', 'none') === 'none' ? 'selected' : '' ?>>Žádná</option>
                                    <option value="floatval" <?= old('transform_type') === 'floatval' ? 'selected' : '' ?>>floatval (převod na číslo s desetinou)</option>
                                    <option value="intval" <?= old('transform_type') === 'intval' ? 'selected' : '' ?>>intval (převod na celé číslo)</option>
                                    <option value="strip_tags" <?= old('transform_type') === 'strip_tags' ? 'selected' : '' ?>>strip_tags (odstranění HTML)</option>
                                    <option value="boolean" <?= old('transform_type') === 'boolean' ? 'selected' : '' ?>>boolean (1/0 → true/false)</option>
                                    <option value="custom" <?= old('transform_type') === 'custom' ? 'selected' : '' ?>>Vlastní</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Výchozí hodnota</label>
                                <input type="text" class="form-control" name="default_value" 
                                       value="<?= e(old('default_value')) ?>" 
                                       placeholder="např. 24 měsíců">
                                <div class="form-text">
                                    Použije se když XML element chybí
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Popis (poznámka)</label>
                        <textarea class="form-control" name="description" rows="2"><?= e(old('description')) ?></textarea>
                        <div class="form-text">
                            Volitelná poznámka pro admina
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_required" name="is_required" value="1" <?= old('is_required') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_required">
                                    Povinné pole
                                </label>
                                <div class="form-text">
                                    Import selže pokud toto pole chybí
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= old('is_active', '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Aktivní
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong><i class="bi bi-lightbulb me-2"></i>Příklady:</strong>
                        <ul class="mb-0 mt-2">
                            <li><code>warranty</code> → <code>WARRANTY</code> → Žádná transformace</li>
                            <li><code>weight</code> → <code>LOGISTIC/WEIGHT</code> → floatval</li>
                            <li><code>stock_amount</code> → <code>STOCK/AMOUNT</code> → intval</li>
                            <li><code>image_1</code> → <code>IMAGES/IMAGE[0]</code> → Žádná</li>
                        </ul>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Vytvořit mapování
                        </button>
                        <a href="/app/settings/field-mappings.php" class="btn btn-outline-secondary">
                            Zrušit
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- NÁPOVĚDA -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <i class="bi bi-question-circle me-2"></i>Nápověda - Jak najít XML cestu?
            </div>
            <div class="card-body">
                <ol>
                    <li>Otevři XML feed v prohlížeči</li>
                    <li>Najdi element který chceš (např. <code>&lt;WARRANTY&gt;24 měsíců&lt;/WARRANTY&gt;</code>)</li>
                    <li>Cesta je: <code>WARRANTY</code></li>
                    <li>Pokud je vnořený: <code>&lt;STOCK&gt;&lt;AMOUNT&gt;10&lt;/AMOUNT&gt;&lt;/STOCK&gt;</code> → <code>STOCK/AMOUNT</code></li>
                    <li>Array indexy: <code>&lt;IMAGES&gt;&lt;IMAGE&gt;url1&lt;/IMAGE&gt;&lt;IMAGE&gt;url2&lt;/IMAGE&gt;&lt;/IMAGES&gt;</code> → <code>IMAGES/IMAGE[0]</code></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
