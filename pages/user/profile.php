<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\User;
use App\Core\Security;

$userModel = new User();
$userId = $auth->userId();
$user = $userModel->findById($userId);

if (!$user) {
    flash('error', 'Uživatel nenalezen');
    redirect('/pages/user/dashboard.php');
}

$errors = [];
$success = false;

if (isPost()) {
    if (!Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/pages/user/profile.php');
    }
    
    $action = post('action');
    
    if ($action === 'update_profile') {
        // Aktualizace profilu (bez hesla)
        $data = [
            'name' => post('name'),
            'email' => post('email'),
            'company_name' => post('company_name'),
            'ico' => post('ico'),
            'dic' => post('dic'),
            'phone' => post('phone'),
            'address' => post('address'),
            'city' => post('city'),
            'zip' => post('zip'),
            'country' => post('country', 'Česká republika'),
        ];
        
        if ($userModel->update($userId, $data)) {
            // Update session info pokud se změnil email nebo jméno
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['user_name'] = $data['name'];
            
            flash('success', 'Profil byl úspěšně aktualizován');
            redirect('/pages/user/profile.php');
        } else {
            $errors = getErrors();
        }
        
    } elseif ($action === 'change_password') {
        // Změna hesla
        $currentPassword = post('current_password');
        $newPassword = post('new_password');
        $newPasswordConfirm = post('new_password_confirm');
        
        if (empty($currentPassword) || empty($newPassword) || empty($newPasswordConfirm)) {
            $errors['password'] = 'Vyplňte všechna pole';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $errors['password'] = 'Nová hesla se neshodují';
        } elseif ($auth->changePassword($userId, $currentPassword, $newPassword)) {
            flash('success', 'Heslo bylo úspěšně změněno');
            redirect('/pages/user/profile.php');
        } else {
            $errors['password'] = 'Neplatné současné heslo nebo nové heslo nesplňuje požadavky';
        }
    }
}

$title = 'Můj profil';
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <!-- Profil -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-person-circle me-2"></i>Osobní údaje
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger">
                            <?= e($errors['general']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h6 class="border-bottom pb-2 mb-3">Přihlašovací údaje</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Jméno a příjmení *</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= e($user['name']) ?>" 
                                   required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" 
                                   class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?= e($user['email']) ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Firemní údaje</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">Název firmy</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="company_name" 
                                   name="company_name" 
                                   value="<?= e($user['company_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="ico" class="form-label">IČO</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['ico']) ? 'is-invalid' : '' ?>" 
                                   id="ico" 
                                   name="ico" 
                                   value="<?= e($user['ico'] ?? '') ?>" 
                                   maxlength="8">
                            <?php if (isset($errors['ico'])): ?>
                                <div class="invalid-feedback"><?= e($errors['ico']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label for="dic" class="form-label">DIČ</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="dic" 
                                   name="dic" 
                                   value="<?= e($user['dic'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               value="<?= e($user['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Ulice a č.p.</label>
                        <input type="text" 
                               class="form-control" 
                               id="address" 
                               name="address" 
                               value="<?= e($user['address'] ?? '') ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">Město</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="city" 
                                   name="city" 
                                   value="<?= e($user['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="zip" class="form-label">PSČ</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="zip" 
                                   name="zip" 
                                   value="<?= e($user['zip'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="country" class="form-label">Země</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="country" 
                                   name="country" 
                                   value="<?= e($user['country'] ?? 'Česká republika') ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Uložit změny
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Změna hesla -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-lock me-2"></i>Změna hesla
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf() ?>
                    <input type="hidden" name="action" value="change_password">
                    
                    <?php if (isset($errors['password'])): ?>
                        <div class="alert alert-danger">
                            <?= e($errors['password']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Současné heslo</label>
                        <input type="password" 
                               class="form-control" 
                               id="current_password" 
                               name="current_password" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nové heslo</label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password" 
                               name="new_password" 
                               required>
                        <small class="text-muted">Min. 8 znaků, velké a malé písmeno, číslo</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password_confirm" class="form-label">Potvrzení nového hesla</label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password_confirm" 
                               name="new_password_confirm" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Změnit heslo
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Informace o účtu
            </div>
            <div class="card-body">
                <p><strong>Role:</strong><br>
                <?php if ($user['is_super_admin']): ?>
                    <span class="badge bg-danger">Super Admin</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Uživatel</span>
                <?php endif; ?>
                </p>
                
                <p><strong>Status:</strong><br>
                <?php if ($user['is_active']): ?>
                    <span class="badge bg-success">Aktivní</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Neaktivní</span>
                <?php endif; ?>
                </p>
                
                <p class="mb-0">
                    <strong>Registrace:</strong><br>
                    <small class="text-muted"><?= formatDate($user['created_at']) ?></small>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-check me-2"></i>Bezpečnost
            </div>
            <div class="card-body">
                <h6>Doporučení:</h6>
                <ul class="small">
                    <li>Používejte silné heslo</li>
                    <li>Změňte heslo pravidelně</li>
                    <li>Nesdílejte přihlašovací údaje</li>
                </ul>
                
                <h6 class="mt-3">Požadavky na heslo:</h6>
                <ul class="small">
                    <li>Minimálně 8 znaků</li>
                    <li>Velké i malé písmeno</li>
                    <li>Alespoň jedna číslice</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearErrors();
require __DIR__ . '/../../views/layouts/main.php';
