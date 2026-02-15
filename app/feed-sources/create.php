<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\FeedSources\Models\FeedSource;

$feedSourceModel = new FeedSource();
$userId = $auth->userId();
$errors = [];

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/feed-sources/');
    }
    
    $data = [
        'user_id' => $userId,
        'name' => post('name'),
        'description' => post('description'),
        'url' => post('url'),
        'feed_type' => post('feed_type', 'xml'),
        'is_active' => post('is_active', '1') === '1',
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
        // Nastav správný feed_type podle výběru
        $feedTypeMap = [
            'shoptet_products' => 'shoptet_products',
            'xml' => 'shoptet_products', // Fallback
            'shoptet' => 'shoptet_products', // Fallback
        ];
        
        $data['feed_type'] = $feedTypeMap[$data['feed_type']] ?? 'shoptet_products';
        $data['type'] = 'products_xml'; // VŽDY pro produkty
        
        $feedId = $feedSourceModel->create($data);
        
        if ($feedId) {
            flash('success', 'Feed zdroj byl úspěšně vytvořen! Spusť první import pro vytvoření mappingů.');
            redirect('/app/feed-sources/');
        } else {
            $errors[] = 'Nepodařilo se vytvořit feed zdroj';
        }
    }
    
    saveOldInput();
}

$title = 'Nový feed zdroj';
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-plus-circle me-2"></i>Vytvoření nového feed zdroje</span>
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
                        <input type="text" class="form-control" name="name" value="<?= e(old('name')) ?>" required>
                        <small class="text-muted">Např. "Hlavní e-shop - Shoptet", "Produkty - WooCommerce"</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Popis</label>
                        <textarea class="form-control" name="description" rows="2"><?= e(old('description')) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">URL feedu *</label>
                        <input type="url" class="form-control" name="url" value="<?= e(old('url')) ?>" required>
                        <small class="text-muted">Kompletní URL k XML/JSON feedu</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Typ feedu</label>
                        <select class="form-select" name="feed_type">
                            <option value="shoptet_products" <?= old('feed_type', 'shoptet_products') === 'shoptet_products' ? 'selected' : '' ?>>Marketingový Shoptet feed (produkty)</option>
                            <option value="shoptet_orders" <?= old('feed_type') === 'shoptet_orders' ? 'selected' : '' ?>>Shoptet objednávky</option>
                            <option value="xml" <?= old('feed_type') === 'xml' ? 'selected' : '' ?>>Obecný XML</option>
                            <option value="json" <?= old('feed_type') === 'json' ? 'selected' : '' ?>>JSON</option>
                            <option value="csv" <?= old('feed_type') === 'csv' ? 'selected' : '' ?>>CSV</option>
                        </select>
                        <div class="form-text">
                            <strong>Shoptet produkty:</strong> Automaticky parsuje SHOPITEM s variantami
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= old('is_active', '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Aktivní (bude se automaticky importovat)
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Vytvořit feed zdroj
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
                <i class="bi bi-info-circle me-2"></i>Nápověda
            </div>
            <div class="card-body">
                <h6>Co je feed zdroj?</h6>
                <p class="small">Feed zdroj je URL adresa, ze které se automaticky stahují a aktualizují produkty do systému.</p>
                
                <h6 class="mt-3">Podporované formáty:</h6>
                <ul class="small mb-0">
                    <li><strong>XML</strong> - Shoptet, Shopify, vlastní</li>
                    <li><strong>JSON</strong> - REST API</li>
                    <li><strong>CSV</strong> - Tabulkové formáty</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightbulb me-2"></i>Příklad XML feedu
            </div>
            <div class="card-body">
                <small class="text-muted">
                    https://www.example.com/feed.xml<br>
                    https://shop.example.com/export/products.xml
                </small>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearOldInput();
require __DIR__ . '/../../views/layouts/main.php';
