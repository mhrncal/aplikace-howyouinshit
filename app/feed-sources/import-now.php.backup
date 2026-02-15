<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\FeedSources\Models\FeedSource;
use App\Modules\Products\Services\XmlImportService;

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

// Spustit import
$importing = false;
$result = null;

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/feed-sources/');
    }
    
    set_time_limit(600); // 10 minut pro velké feedy
    ini_set('memory_limit', '512M'); // Více paměti
    
    $importing = true;
    
    try {
        $xmlImporter = new XmlImportService();
        $result = $xmlImporter->importFromUrl(
            $feedId,
            $userId,
            $feed['url'],
            $feed['http_auth_username'] ?? null,
            $feed['http_auth_password'] ?? null
        );
        
        // Update last_import_at
        $feedSourceModel->update($feedId, $userId, [
            'last_import_at' => date('Y-m-d H:i:s')
        ]);
        
        flash('success', "Import dokončen! Importováno {$result['imported']} produktů, aktualizováno {$result['updated']}, chyb: {$result['errors']}");
        redirect('/app/feed-sources/');
        
    } catch (\Exception $e) {
        $result = [
            'error' => $e->getMessage(),
            'imported' => 0,
            'updated' => 0,
            'errors' => 1
        ];
    }
}

$title = 'Import - ' . $feed['name'];
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-download me-2"></i>Manuální import</span>
                <a href="/app/feed-sources/" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Zpět
                </a>
            </div>
            <div class="card-body">
                <?php if (!$importing): ?>
                    <h5><?= e($feed['name']) ?></h5>
                    <p class="text-muted"><?= e($feed['url']) ?></p>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Upozornění:</strong> Import může trvat několik minut v závislosti na velikosti feedu.
                    </div>
                    
                    <form method="POST">
                        <?= csrf() ?>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-download me-2"></i>Spustit import
                        </button>
                    </form>
                    
                <?php else: ?>
                    <h5>Probíhá import...</h5>
                    
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: 100%">
                            Importuji...
                        </div>
                    </div>
                    
                    <?php if ($result): ?>
                        <?php if (isset($result['error'])): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-x-circle me-2"></i>
                                <strong>Chyba při importu:</strong><br>
                                <?= e($result['error']) ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Import dokončen!</strong>
                            </div>
                            
                            <table class="table">
                                <tr>
                                    <th>Importováno nových:</th>
                                    <td><strong><?= number_format($result['imported']) ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Aktualizováno existujících:</th>
                                    <td><strong><?= number_format($result['updated']) ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Chyb:</th>
                                    <td><strong><?= number_format($result['errors']) ?></strong></td>
                                </tr>
                            </table>
                            
                            <a href="/app/products/" class="btn btn-primary me-2">
                                <i class="bi bi-box-seam me-2"></i>Zobrazit produkty
                            </a>
                            <a href="/app/feed-sources/" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Zpět na feed zdroje
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Informace o feedu
            </div>
            <div class="card-body">
                <p><strong>Název:</strong><br><?= e($feed['name']) ?></p>
                <p><strong>Typ:</strong><br><span class="badge bg-info"><?= e(strtoupper($feed['feed_type'])) ?></span></p>
                <?php if ($feed['last_import_at']): ?>
                <p class="mb-0"><strong>Poslední import:</strong><br>
                <small class="text-muted"><?= formatDate($feed['last_import_at']) ?></small></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightbulb me-2"></i>Co se stane
            </div>
            <div class="card-body">
                <ol class="small mb-0">
                    <li>Stažení XML feedu z URL</li>
                    <li>Zpracování produktů</li>
                    <li>Import nových produktů</li>
                    <li>Aktualizace existujících</li>
                    <li>Uložení variant</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
