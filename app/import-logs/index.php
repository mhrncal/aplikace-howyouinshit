<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Core\Database;

$db = Database::getInstance();
$userId = $auth->userId();
$isSuperAdmin = $auth->isSuperAdmin();
$page = (int) get('page', 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Logy
$logs = $db->fetchAll(
    "SELECT il.*, u.name as user_name, fs.name as feed_name 
     FROM import_logs il
     LEFT JOIN users u ON il.user_id = u.id
     LEFT JOIN feed_sources fs ON il.feed_source_id = fs.id
     WHERE " . ($isSuperAdmin ? "1=1" : "il.user_id = ?") . "
     ORDER BY il.created_at DESC
     LIMIT ? OFFSET ?",
    $isSuperAdmin ? [$perPage, $offset] : [$userId, $perPage, $offset]
);

$total = $db->fetchOne(
    "SELECT COUNT(*) as count FROM import_logs WHERE " . ($isSuperAdmin ? "1=1" : "user_id = ?"),
    $isSuperAdmin ? [] : [$userId]
)['count'];

$pagination = paginate($total, $perPage, $page);

$title = 'Historie importů';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Historie importů</h2>
        <p class="text-muted mb-0">Celkem <?= number_format($pagination['total']) ?> záznamů</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <i class="bi bi-clock-history"></i>
                <p class="mb-0">Žádné importy</p>
                <small class="text-muted">Historie importů se bude zobrazovat zde</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Feed</th>
                            <?php if ($isSuperAdmin): ?><th>Uživatel</th><?php endif; ?>
                            <th>Status</th>
                            <th>Zpracováno</th>
                            <th>Úspěšných</th>
                            <th>Chyb</th>
                            <th>Čas</th>
                            <th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php if ($log['feed_name']): ?>
                                    <strong><?= e($log['feed_name']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isSuperAdmin): ?>
                            <td><small><?= e($log['user_name'] ?? '-') ?></small></td>
                            <?php endif; ?>
                            <td>
                                <?php
                                $badges = [
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'processing' => 'warning',
                                    'pending' => 'secondary'
                                ];
                                $statusLabels = [
                                    'completed' => 'Dokončeno',
                                    'failed' => 'Selhalo',
                                    'processing' => 'Zpracovává se',
                                    'pending' => 'Čeká'
                                ];
                                ?>
                                <span class="badge bg-<?= $badges[$log['status']] ?? 'secondary' ?>">
                                    <?= $statusLabels[$log['status']] ?? ucfirst($log['status']) ?>
                                </span>
                            </td>
                            <td><?= number_format($log['processed_records']) ?></td>
                            <td><span class="text-success"><?= number_format($log['successful_records']) ?></span></td>
                            <td>
                                <?php if ($log['failed_records'] > 0): ?>
                                    <span class="text-danger"><?= number_format($log['failed_records']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['processing_time']): ?>
                                    <small><?= number_format($log['processing_time'], 1) ?>s</small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= formatDate($log['created_at']) ?></small></td>
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
