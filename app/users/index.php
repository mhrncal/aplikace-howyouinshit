<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireSuperAdmin();

use App\Models\User;

$userModel = new User();
$page = (int) get('page', 1);

// CRUD akce
if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/users/');
    }
    
    $action = post('action');
    $userId = (int) post('user_id');
    
    switch ($action) {
        case 'toggle_active':
            if ($userModel->toggleActive($userId, $auth->userId())) {
                flash('success', 'Status změněn');
            } else {
                flash('error', 'Nelze změnit vlastní status');
            }
            break;
            
        case 'delete':
            if ($userModel->delete($userId, $auth->userId())) {
                flash('success', 'Uživatel smazán');
            } else {
                flash('error', 'Nelze smazat uživatele');
            }
            break;
    }
    
    redirect('/app/users/');
}

$data = $userModel->getAll($page);
$users = $data['users'];
$pagination = $data['pagination'];

$title = 'Uživatelé';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Uživatelé</h2>
        <p class="text-muted mb-0">Celkem <?= number_format($pagination['total']) ?> uživatelů</p>
    </div>
    <a href="/app/users/create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Přidat uživatele
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <p class="mb-0">Žádní uživatelé</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Jméno</th>
                            <th>Email</th>
                            <th>Firma</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registrace</th>
                            <th width="150">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= e($user['name']) ?></strong>
                            </td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <?php if ($user['company_name']): ?>
                                    <?= e($user['company_name']) ?>
                                    <?php if ($user['ico']): ?>
                                        <br><small class="text-muted">IČO: <?= e($user['ico']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_super_admin']): ?>
                                    <span class="badge bg-danger">Super Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Uživatel</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <?= csrf() ?>
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-link p-0" 
                                            <?= $user['id'] == $auth->userId() ? 'disabled' : '' ?>>
                                        <?php if ($user['is_active']): ?>
                                            <span class="badge bg-success">Aktivní</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Neaktivní</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td><small><?= formatDate($user['created_at']) ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/app/users/edit.php?id=<?= $user['id'] ?>" 
                                       class="btn btn-outline-primary" title="Upravit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($user['id'] != $auth->userId()): ?>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Opravdu smazat tohoto uživatele?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Smazat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
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
