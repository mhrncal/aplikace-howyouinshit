<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireSuperAdmin();

use App\Models\User;

$userModel = new User();
$errors = [];

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/pages/admin/users.php');
    }
    
    $data = [
        'name' => post('name'),
        'email' => post('email'),
        'password' => post('password'),
        'is_super_admin' => post('is_super_admin') === '1',
        'is_active' => post('is_active', '1') === '1',
        'company_name' => post('company_name'),
        'ico' => post('ico'),
        'dic' => post('dic'),
        'phone' => post('phone'),
        'address' => post('address'),
        'city' => post('city'),
        'zip' => post('zip'),
        'country' => post('country', 'Česká republika'),
    ];
    
    $userId = $userModel->create($data);
    
    if ($userId) {
        flash('success', 'Uživatel byl úspěšně vytvořen');
        redirect('/pages/admin/users.php');
    } else {
        $errors = getErrors();
        saveOldInput();
    }
}

$title = 'Nový uživatel';
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-plus me-2"></i>Vytvoření nového uživatele
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
                    
                    <h5 class="border-bottom pb-2 mb-3">Přihlašovací údaje</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Jméno a příjmení *</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= e(old('name')) ?>" 
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
                                   value="<?= e(old('email')) ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Heslo *</label>
                        <input type="password" 
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               id="password" 
                               name="password" 
                               required>
                        <small class="text-muted">Min. 8 znaků, velké a malé písmeno, číslo</small>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Firemní údaje</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">Název firmy</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="company_name" 
                                   name="company_name" 
                                   value="<?= e(old('company_name')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="ico" class="form-label">IČO</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['ico']) ? 'is-invalid' : '' ?>" 
                                   id="ico" 
                                   name="ico" 
                                   value="<?= e(old('ico')) ?>" 
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
                                   value="<?= e(old('dic')) ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               value="<?= e(old('phone')) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Ulice a č.p.</label>
                        <input type="text" 
                               class="form-control" 
                               id="address" 
                               name="address" 
                               value="<?= e(old('address')) ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="city" class="form-label">Město</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="city" 
                                   name="city" 
                                   value="<?= e(old('city')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="zip" class="form-label">PSČ</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="zip" 
                                   name="zip" 
                                   value="<?= e(old('zip')) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="country" class="form-label">Země</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="country" 
                                   name="country" 
                                   value="<?= e(old('country', 'Česká republika')) ?>">
                        </div>
                    </div>
                    
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Oprávnění</h5>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="is_super_admin" 
                               name="is_super_admin" 
                               value="1" 
                               <?= old('is_super_admin') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_super_admin">
                            <strong>Super Admin</strong> - Přístup ke všem datům a funkcím
                        </label>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="is_active" 
                               name="is_active" 
                               value="1" 
                               <?= old('is_active', '1') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Aktivní účet
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Vytvořit uživatele
                        </button>
                        <a href="/pages/admin/users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Zrušit
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Nápověda
            </div>
            <div class="card-body">
                <h6>Povinná pole:</h6>
                <ul>
                    <li>Jméno a příjmení</li>
                    <li>Email (bude sloužit k přihlášení)</li>
                    <li>Heslo (min. 8 znaků)</li>
                </ul>
                
                <h6 class="mt-3">Super Admin:</h6>
                <p class="small">
                    Má přístup ke všem datům všech uživatelů a může spravovat uživatele.
                </p>
                
                <h6 class="mt-3">IČO validace:</h6>
                <p class="small">
                    Systém automaticky kontroluje platnost českého IČO.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearOldInput();
clearErrors();
require __DIR__ . '/../../views/layouts/main.php';
