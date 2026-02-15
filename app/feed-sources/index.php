<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\FeedSources\Models\FeedSource;

$feedSourceModel = new FeedSource();
$userId = $auth->userId();
$page = (int) get('page', 1);

// CRUD akce
if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/feed-sources/');
    }
    
    $action = post('action');
    $feedId = (int) post('feed_id');
    
    switch ($action) {
        case 'toggle_active':
            if ($feedSourceModel->toggleActive($feedId, $userId)) {
                flash('success', 'Status změněn');
            }
            break;
            
        case 'delete':
            if ($feedSourceModel->delete($feedId, $userId)) {
                flash('success', 'Feed zdroj smazán');
            }
            break;
    }
    
    redirect('/app/feed-sources/');
}

$data = $feedSourceModel->getAll($userId, $page);
$feedSources = $data['feed_sources'];
$pagination = $data['pagination'];

$title = 'Feed zdroje';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Feed zdroje</h2>
        <p class="text-muted mb-0">Celkem <?= number_format($pagination['total']) ?> zdrojů</p>
    </div>
    <a href="/app/feed-sources/create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Přidat feed zdroj
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($feedSources)): ?>
            <div class="empty-state">
                <i class="bi bi-link-45deg"></i>
                <p class="mb-0">Žádné feed zdroje</p>
                <small class="text-muted">Přidejte první feed zdroj pro automatický import produktů</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>URL</th>
                            <th>Typ</th>
                            <th>Poslední import</th>
                            <th>Status</th>
                            <th width="180">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feedSources as $feed): ?>
                        <tr>
                            <td>
                                <strong><?= e($feed['name']) ?></strong>
                                <?php if ($feed['description']): ?>
                                    <br><small class="text-muted"><?= e(truncate($feed['description'], 50)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= e($feed['url']) ?>" target="_blank" class="text-break">
                                    <small><?= e(truncate($feed['url'], 40)) ?></small>
                                    <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= e(strtoupper($feed['feed_type'])) ?></span>
                            </td>
                            <td>
                                <?php if ($feed['last_import_at']): ?>
                                    <small><?= formatDate($feed['last_import_at']) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Nikdy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link p-0">
                                        <?php if ($feed['is_active']): ?>
                                            <span class="badge bg-success">Aktivní</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Neaktivní</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/app/feed-sources/import-now.php?id=<?= $feed['id'] ?>" 
                                       class="btn btn-outline-success" 
                                       title="Importovat nyní">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <a href="/app/feed-sources/edit.php?id=<?= $feed['id'] ?>" 
                                       class="btn btn-outline-primary" 
                                       title="Upravit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Opravdu smazat tento feed zdroj?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Smazat">
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
    
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php if ($pagination['has_prev']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>">Předchozí</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i === $pagination['current_page']): ?>
                        <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                    <?php elseif ($i === 1 || $i === $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                    <?php elseif ($i === 2 || $i === $pagination['total_pages'] - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_more']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>">Další</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
