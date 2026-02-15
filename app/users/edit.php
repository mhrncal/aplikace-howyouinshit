<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireSuperAdmin();

use App\Models\User;

$userModel = new User();
$userId = (int) get('id');

if (!$userId) {
    flash('error', 'Neplatné ID uživatele');
    redirect('/app/users/');
}

$user = $userModel->findById($userId);

if (!$user) {
    flash('error', 'Uživatel nenalezen');
    redirect('/app/users/');
}

$errors = [];

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/users/');
    }
    
    $data = [
        'name' => post('name'),
        'email' => post('email'),
        'is_super_admin' => post('is_super_admin') === '1',
        'is_active' => post('is_active') === '1',
        'company_name' => post('company_name'),
        'ico' => post('ico'),
        'dic' => post('dic'),
        'phone' => post('phone'),
        'address' => post('address'),
        'city' => post('city'),
        'zip' => post('zip'),
        'country' => post('country', 'Česká republika'),
    ];
    
    // Heslo je volitelné při editaci
    if (!empty(post('password'))) {
        $data['password'] = post('password');
    }
    
    if ($userModel->update($userId, $data)) {
        flash('success', 'Uživatel byl úspěšně aktualizován');
        redirect('/app/users/');
    } else {
        $errors = getErrors();
        saveOldInput();
    }
}

$title = 'Upravit uživatele';
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil me-2"></i>Úprava uživatele #<?= $userId ?></span>
                <a href="/app/users/" class="btn btn-sm btn-outline-secondary">
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
                    
                    <h6 class="border-bottom pb-2 mb-3">Přihlašovací údaje</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jméno a příjmení *</label>
                            <input type="text" class="form-control" name="name" value="<?= e(old('name', $user['name'])) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?= e(old('email', $user['email'])) ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nové heslo</label>
                        <input type="password" class="form-control" name="password" placeholder="Ponechte prázdné pro zachování">
                        <small class="text-muted">Min. 8 znaků, velké a malé písmeno, číslo</small>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Firemní údaje</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Název firmy</label>
                            <input type="text" class="form-control" name="company_name" value="<?= e(old('company_name', $user['company_name'])) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">IČO</label>
                            <input type="text" class="form-control" name="ico" value="<?= e(old('ico', $user['ico'])) ?>" maxlength="8">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">DIČ</label>
                            <input type="text" class="form-control" name="dic" value="<?= e(old('dic', $user['dic'])) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" class="form-control" name="phone" value="<?= e(old('phone', $user['phone'])) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ulice a č.p.</label>
                        <input type="text" class="form-control" name="address" value="<?= e(old('address', $user['address'])) ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Město</label>
                            <input type="text" class="form-control" name="city" value="<?= e(old('city', $user['city'])) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">PSČ</label>
                            <input type="text" class="form-control" name="zip" value="<?= e(old('zip', $user['zip'])) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Země</label>
                            <input type="text" class="form-control" name="country" value="<?= e(old('country', $user['country'] ?? 'Česká republika')) ?>">
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Oprávnění</h6>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="is_super_admin" name="is_super_admin" value="1" 
                               <?= old('is_super_admin', $user['is_super_admin']) ? 'checked' : '' ?>
                               <?= $user['id'] == $auth->userId() ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="is_super_admin">
                            <strong>Super Admin</strong>
                            <?= $user['id'] == $auth->userId() ? '<small class="text-muted">(nelze změnit sám sobě)</small>' : '' ?>
                        </label>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                               <?= old('is_active', $user['is_active']) ? 'checked' : '' ?>
                               <?= $user['id'] == $auth->userId() ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Aktivní účet
                            <?= $user['id'] == $auth->userId() ? '<small class="text-muted">(nelze deaktivovat sám sebe)</small>' : '' ?>
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Uložit změny
                        </button>
                        <a href="/app/users/" class="btn btn-outline-secondary">
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
                <p><strong>Registrace:</strong><br>
                <small class="text-muted"><?= formatDate($user['created_at']) ?></small></p>
                
                <p><strong>Poslední aktualizace:</strong><br>
                <small class="text-muted"><?= formatDate($user['updated_at']) ?></small></p>
                
                <?php if ($user['last_login_at']): ?>
                <p class="mb-0"><strong>Poslední přihlášení:</strong><br>
                <small class="text-muted"><?= formatDate($user['last_login_at']) ?></small></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-check me-2"></i>Nápověda
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Heslo:</strong> Ponechte prázdné pro zachování současného hesla.</p>
                <p class="small mb-0"><strong>Ochrana:</strong> Nelze změnit vlastní oprávnění nebo deaktivovat vlastní účet.</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearOldInput();
clearErrors();
require __DIR__ . '/../../views/layouts/main.php';
