<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\OrderFeedSource;

$feedModel = new OrderFeedSource();
$userId = $auth->userId();
$errors = [];

// CRUD akce
if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/orders/feed-sources.php');
    }
    
    $action = post('action');
    
    switch ($action) {
        case 'create':
            $data = [
                'user_id' => $userId,
                'name' => post('name'),
                'url' => post('url'),
                'schedule' => post('schedule', 'manual'),
                'is_active' => post('is_active') === '1' ? 1 : 0
            ];
            
            if ($feedModel->create($data)) {
                flash('success', 'Feed zdroj vytvořen');
            } else {
                flash('error', 'Nepodařilo se vytvořit feed zdroj');
            }
            redirect('/app/orders/feed-sources.php');
            break;
            
        case 'update':
            $id = (int) post('feed_id');
            $data = [
                'name' => post('name'),
                'url' => post('url'),
                'schedule' => post('schedule'),
                'is_active' => post('is_active') === '1' ? 1 : 0
            ];
            
            if ($feedModel->update($id, $userId, $data)) {
                flash('success', 'Feed zdroj aktualizován');
            }
            redirect('/app/orders/feed-sources.php');
            break;
            
        case 'delete':
            $id = (int) post('feed_id');
            if ($feedModel->delete($id, $userId)) {
                flash('success', 'Feed zdroj smazán');
            }
            redirect('/app/orders/feed-sources.php');
            break;
            
        case 'import':
            $id = (int) post('feed_id');
            $feed = $feedModel->findById($id, $userId);
            
            if ($feed) {
                $importService = new \App\Services\OrderCsvImportService();
                $result = $importService->importFromUrl($userId, $feed['url']);
                
                if ($result['success']) {
                    $feedModel->updateStats($id, $result['orders_imported']);
                    flash('success', "Import dokončen: {$result['orders_imported']} objednávek, {$result['items_imported']} položek");
                } else {
                    flash('error', 'Import selhal: ' . ($result['error'] ?? 'Neznámá chyba'));
                }
            }
            redirect('/app/orders/feed-sources.php');
            break;
    }
}

$feeds = $feedModel->getAll($userId);

$title = 'Zdroje objednávek';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Zdroje CSV feedů objednávek</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-2"></i>Nový feed zdroj
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($feeds)): ?>
            <div class="empty-state">
                <i class="bi bi-rss"></i>
                <p class="mb-0">Žádné feed zdroje</p>
                <small class="text-muted">Přidejte první feed zdroj pro import objednávek</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>URL</th>
                            <th>Plán</th>
                            <th>Poslední import</th>
                            <th>Stav</th>
                            <th width="150">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeds as $feed): ?>
                        <tr>
                            <td><strong><?= e($feed['name']) ?></strong></td>
                            <td>
                                <small class="text-muted"><?= e(substr($feed['url'], 0, 60)) ?>...</small>
                            </td>
                            <td>
                                <?php 
                                $scheduleLabels = [
                                    'hourly' => 'Každou hodinu',
                                    'daily' => 'Denně',
                                    'weekly' => 'Týdně',
                                    'manual' => 'Manuálně'
                                ];
                                ?>
                                <span class="badge bg-info"><?= $scheduleLabels[$feed['schedule']] ?></span>
                            </td>
                            <td>
                                <?php if ($feed['last_imported_at']): ?>
                                    <small><?= formatDate($feed['last_imported_at']) ?></small>
                                    <br><small class="text-muted"><?= $feed['last_import_records'] ?> záznamů</small>
                                <?php else: ?>
                                    <span class="text-muted">Nikdy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($feed['is_active']): ?>
                                    <span class="badge bg-success">Aktivní</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Neaktivní</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <form method="POST" class="d-inline">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="import">
                                        <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
                                        <button type="submit" class="btn btn-outline-success" title="Spustit import">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-outline-primary" 
                                            onclick='editFeed(<?= json_encode($feed) ?>)'
                                            title="Upravit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Opravdu smazat tento feed zdroj?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="feed_id" value="<?= $feed['id'] ?>">
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

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= csrf() ?>
                <input type="hidden" name="action" value="create">
                
                <div class="modal-header">
                    <h5 class="modal-title">Nový feed zdroj</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Název *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">CSV URL *</label>
                        <input type="url" class="form-control" name="url" required>
                        <small class="text-muted">Příklad: https://eshop.cz/export/orders.csv?hash=...</small>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plán importu</label>
                            <select class="form-select" name="schedule">
                                <option value="manual">Manuálně</option>
                                <option value="hourly">Každou hodinu</option>
                                <option value="daily" selected>Denně</option>
                                <option value="weekly">Týdně</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stav</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                                <label class="form-check-label">Aktivní</label>
                            </div>
                        </div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= csrf() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="feed_id" id="edit_feed_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Upravit feed zdroj</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Název *</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">CSV URL *</label>
                        <input type="url" class="form-control" name="url" id="edit_url" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plán importu</label>
                            <select class="form-select" name="schedule" id="edit_schedule">
                                <option value="manual">Manuálně</option>
                                <option value="hourly">Každou hodinu</option>
                                <option value="daily">Denně</option>
                                <option value="weekly">Týdně</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stav</label>
                            <div class="form-check form-switch mt-2">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" id="edit_is_active">
                                <label class="form-check-label">Aktivní</label>
                            </div>
                        </div>
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
function editFeed(feed) {
    document.getElementById('edit_feed_id').value = feed.id;
    document.getElementById('edit_name').value = feed.name;
    document.getElementById('edit_url').value = feed.url;
    document.getElementById('edit_schedule').value = feed.schedule;
    document.getElementById('edit_is_active').checked = feed.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
