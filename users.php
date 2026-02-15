<?php
require_once __DIR__ . '/bootstrap.php';

$auth->requireSuperAdmin();

use App\Models\User;

$userModel = new User();
$page = (int) get('page', 1);

// Zpracování akcí
if (isPost()) {
    $action = post('action');
    
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/users.php');
    }
    
    switch ($action) {
        case 'delete':
            $userId = (int) post('user_id');
            if ($userModel->delete($userId)) {
                flash('success', 'Uživatel byl smazán');
            } else {
                flash('error', getErrors('general') ?? 'Nepodařilo se smazat uživatele');
            }
            redirect('/users.php');
            break;
            
        case 'toggle_active':
            $userId = (int) post('user_id');
            if ($userModel->toggleActive($userId)) {
                flash('success', 'Status uživatele byl změněn');
            } else {
                flash('error', 'Nepodařilo se změnit status');
            }
            redirect('/users.php');
            break;
    }
}

$data = $userModel->getAll($page, 20);
$users = $data['users'];
$pagination = $data['pagination'];

$title = 'Správa uživatelů';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Uživatelé</h2>
        <p class="text-muted mb-0">Celkem <?= number_format($pagination['total']) ?> uživatelů</p>
    </div>
    <a href="/user-create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>
        Přidat uživatele
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Jméno</th>
                        <th>Email</th>
                        <th>Firma</th>
                        <th>IČO</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registrován</th>
                        <th width="150">Akce</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">Žádní uživatelé</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= e($user['name']) ?></strong>
                            </td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['company_name'] ?? '-') ?></td>
                            <td><?= e($user['ico'] ?? '-') ?></td>
                            <td>
                                <?php if ($user['is_super_admin']): ?>
                                    <span class="badge bg-danger">Super Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success">Aktivní</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Neaktivní</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= formatDate($user['created_at']) ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/user-edit.php?id=<?= $user['id'] ?>" 
                                       class="btn btn-outline-primary"
                                       title="Upravit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete změnit status?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="toggle_active">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" 
                                                class="btn btn-outline-warning"
                                                title="Změnit status">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    
                                    <?php if ($user['id'] !== $auth->userId()): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Opravdu chcete smazat tohoto uživatele?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" 
                                                class="btn btn-outline-danger"
                                                title="Smazat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
clearErrors();
require __DIR__ . '/views/layouts/main.php';
