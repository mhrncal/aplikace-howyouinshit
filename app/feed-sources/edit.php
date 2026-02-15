<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\FeedSources\Models\FeedSource;

$feedSourceModel = new FeedSource();
$userId = $auth->userId();
$feedId = (int) get('id');

if (!$feedId) {
    flash('error', 'Neplatné ID feed zdroje');
    redirect('/app/feed-sources/');
}

$feed = $feedSourceModel->findById($feedId, $userId);

if (!$feed) {
    flash('error', 'Feed zdroj nenalezen');
    redirect('/app/feed-sources/');
}

$errors = [];

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/feed-sources/');
    }
    
    $data = [
        'name' => post('name'),
        'description' => post('description'),
        'url' => post('url'),
        'feed_type' => post('feed_type', 'xml'),
        'is_active' => post('is_active', '0') === '1',
    ];
    
    // Validace
    if (empty($data['name'])) {
        $errors[] = 'Název je povinný';
    }
    if (empty($data['url'])) {
        $errors[] = 'URL je povinná';
    } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Neplatný formát URL';
    }
    
    if (empty($errors)) {
        // Mapování feed_type na správný formát
        $feedTypeMap = [
            'shoptet_products' => 'shoptet_products',
            'shoptet_orders' => 'shoptet_orders',
            'xml' => 'xml',
            'json' => 'json',
            'csv' => 'csv',
        ];
        
        $data['feed_type'] = $feedTypeMap[$data['feed_type']] ?? 'shoptet_products';
        
        if ($feedSourceModel->update($feedId, $userId, $data)) {
            flash('success', 'Feed zdroj byl úspěšně aktualizován');
            redirect('/app/feed-sources/');
        } else {
            $errors[] = 'Nepodařilo se aktualizovat feed zdroj';
        }
    }
    
    saveOldInput();
}

$title = 'Upravit feed zdroj';
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil me-2"></i>Úprava feed zdroje #<?= $feedId ?></span>
                <a href="/app/feed-sources/" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Zpět
                </a>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf() ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Chyba:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <h6 class="border-bottom pb-2 mb-3">Základní informace</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Název *</label>
                        <input type="text" class="form-control" name="name" value="<?= e(old('name', $feed['name'])) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Popis</label>
                        <textarea class="form-control" name="description" rows="2"><?= e(old('description', $feed['description'])) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL feedu *</label>
                        <input type="url" class="form-control" name="url" value="<?= e(old('url', $feed['url'])) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Typ feedu</label>
                        <select class="form-select" name="feed_type">
                            <option value="shoptet_products" <?= old('feed_type', $feed['feed_type']) === 'shoptet_products' ? 'selected' : '' ?>>Marketingový Shoptet feed (produkty)</option>
                            <option value="shoptet_orders" <?= old('feed_type', $feed['feed_type']) === 'shoptet_orders' ? 'selected' : '' ?>>Shoptet objednávky</option>
                            <option value="xml" <?= old('feed_type', $feed['feed_type']) === 'xml' ? 'selected' : '' ?>>Obecný XML</option>
                            <option value="json" <?= old('feed_type', $feed['feed_type']) === 'json' ? 'selected' : '' ?>>JSON</option>
                            <option value="csv" <?= old('feed_type', $feed['feed_type']) === 'csv' ? 'selected' : '' ?>>CSV</option>
                        </select>
                        <div class="form-text">
                            <strong>Shoptet produkty:</strong> Automatické mapování NAME, CODE, PRICE_VAT, atd.
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= old('is_active', $feed['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Aktivní (bude se automaticky importovat)
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Uložit změny
                        </button>
                        <a href="/app/feed-sources/" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Zrušit
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Informace
            </div>
            <div class="card-body">
                <p><strong>Vytvořeno:</strong><br>
                <small class="text-muted"><?= formatDate($feed['created_at']) ?></small></p>
                
                <?php if ($feed['last_import_at']): ?>
                <p><strong>Poslední import:</strong><br>
                <small class="text-muted"><?= formatDate($feed['last_import_at']) ?></small></p>
                <?php endif; ?>
                
                <p class="mb-0"><strong>Aktualizováno:</strong><br>
                <small class="text-muted"><?= formatDate($feed['updated_at']) ?></small></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-download me-2"></i>Akce
            </div>
            <div class="card-body">
                <a href="/app/feed-sources/import-now.php?id=<?= $feedId ?>" class="btn btn-success w-100">
                    <i class="bi bi-download me-2"></i>Importovat nyní
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearOldInput();
require __DIR__ . '/../../views/layouts/main.php';
