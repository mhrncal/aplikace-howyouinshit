<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\FieldMapping;

$productModel = new Product();
$mappingModel = new FieldMapping();
$userId = $auth->userId();

// Získej všechny custom fields pro tohoto uživatele
$customMappings = $mappingModel->getAllForUser($userId, null, 'product');
$customFields = array_filter($customMappings, fn($m) => $m['target_type'] === 'json');

// Filtry
$filters = [
    'search' => get('search'),
    'category' => get('category'),
    'price_from' => get('price_from'),
    'price_to' => get('price_to'),
];

// Custom field filtry
$customFilters = [];
foreach ($customFields as $field) {
    $value = get('custom_' . $field['db_column']);
    if ($value !== null && $value !== '') {
        $customFilters[$field['db_column']] = $value;
    }
}

// Vyhledej produkty
$sql = "SELECT * FROM products WHERE user_id = ?";
$params = [$userId];

if ($filters['search']) {
    $sql .= " AND (name LIKE ? OR code LIKE ?)";
    $params[] = '%' . $filters['search'] . '%';
    $params[] = '%' . $filters['search'] . '%';
}

if ($filters['category']) {
    $sql .= " AND category = ?";
    $params[] = $filters['category'];
}

if ($filters['price_from']) {
    $sql .= " AND price_vat >= ?";
    $params[] = $filters['price_from'];
}

if ($filters['price_to']) {
    $sql .= " AND price_vat <= ?";
    $params[] = $filters['price_to'];
}

// Custom field filtry
foreach ($customFilters as $field => $value) {
    $sql .= " AND JSON_EXTRACT(custom_data, '$." . $field . "') = ?";
    $params[] = $value;
}

$sql .= " ORDER BY name LIMIT 100";

$db = App\Core\Database::getInstance();
$products = $db->fetchAll($sql, $params);

// Dekóduj custom_data pro zobrazení
foreach ($products as &$product) {
    $product['custom_fields'] = json_decode($product['custom_data'] ?? '{}', true);
}

$title = 'Rozšířené vyhledávání';
ob_start();
?>

<div class="mb-4">
    <h2><i class="bi bi-search me-2"></i>Rozšířené vyhledávání</h2>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filtry</h5>
    </div>
    <div class="card-body">
        <form method="GET">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Hledat</label>
                        <input type="text" class="form-control" name="search" 
                               value="<?= e(get('search')) ?>" placeholder="Název nebo kód">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Kategorie</label>
                        <input type="text" class="form-control" name="category" 
                               value="<?= e(get('category')) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Cena od</label>
                        <input type="number" class="form-control" name="price_from" 
                               value="<?= e(get('price_from')) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="mb-3">
                        <label class="form-label">Cena do</label>
                        <input type="number" class="form-control" name="price_to" 
                               value="<?= e(get('price_to')) ?>">
                    </div>
                </div>
            </div>
            
            <?php if (!empty($customFields)): ?>
            <hr>
            <h6>Custom pole:</h6>
            <div class="row">
                <?php foreach ($customFields as $field): ?>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label"><?= e($field['db_column']) ?></label>
                        <input type="text" class="form-control" 
                               name="custom_<?= e($field['db_column']) ?>" 
                               value="<?= e(get('custom_' . $field['db_column'])) ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Vyhledat
                </button>
                <a href="/app/products/advanced-search.php" class="btn btn-secondary">
                    <i class="bi bi-x-circle me-2"></i>Vyčistit
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Výsledky (<?= count($products) ?>)</h5>
        <div class="btn-group">
            <a href="/app/products/export.php?format=csv&<?= http_build_query($filters) ?>" 
               class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
            </a>
            <a href="/app/products/export.php?format=xlsx&<?= http_build_query($filters) ?>" 
               class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel me-2"></i>XLSX
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="p-4 text-center text-muted">
                Žádné produkty nenalezeny
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>Kód</th>
                            <th>Cena</th>
                            <th>Kategorie</th>
                            <?php foreach ($customFields as $field): ?>
                            <th><?= e($field['db_column']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= e($product['name']) ?></td>
                            <td><code><?= e($product['code']) ?></code></td>
                            <td><?= number_format($product['price_vat'], 0, ',', ' ') ?> Kč</td>
                            <td><?= e($product['category']) ?></td>
                            <?php foreach ($customFields as $field): ?>
                            <td><?= e($product['custom_fields'][$field['db_column']] ?? '-') ?></td>
                            <?php endforeach; ?>
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
