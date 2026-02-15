<?php
require_once __DIR__ . '/../bootstrap.php';

if ($auth->check()) {
    redirect('/dashboard.php');
}

$token = get('token');
$error = null;
$success = false;

if (empty($token)) {
    flash('error', 'Neplatný nebo chybějící token');
    redirect('/login.php');
}

// Ověření tokenu
$userId = $auth->verifyPasswordResetToken($token);

if (!$userId) {
    flash('error', 'Neplatný nebo expirovaný token. Požádejte o nový.');
    redirect('/forgot-password.php');
}

if (isPost()) {
    $password = post('password');
    $passwordConfirm = post('password_confirm');
    
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        $error = 'Neplatný požadavek';
    } elseif (empty($password) || empty($passwordConfirm)) {
        $error = 'Vyplňte všechna pole';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Hesla se neshodují';
    } else {
        $passwordErrors = App\Core\Security::validatePassword($password);
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } elseif ($auth->resetPassword($token, $password)) {
            flash('success', 'Heslo bylo úspěšně změněno. Nyní se můžete přihlásit.');
            redirect('/login.php');
        } else {
            $error = 'Nepodařilo se změnit heslo. Zkuste to znovu.';
        }
    }
}

$title = 'Nové heslo';
ob_start();
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg border-0" style="border-radius: 16px;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock" style="font-size: 3rem; color: #6366f1;"></i>
                            </div>
                            <h2 class="fw-bold mb-2">Nové heslo</h2>
                            <p class="text-muted">Zadejte své nové heslo</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <?= csrf() ?>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Nové heslo</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0" 
                                           id="password" 
                                           name="password" 
                                           placeholder="••••••••"
                                           required 
                                           autofocus>
                                </div>
                                <small class="text-muted">
                                    Min. 8 znaků, velké a malé písmeno, číslo
                                </small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirm" class="form-label">Potvrzení hesla</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0" 
                                           id="password_confirm" 
                                           name="password_confirm" 
                                           placeholder="••••••••"
                                           required>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Změnit heslo
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="/login.php" class="text-decoration-none">
                                    <small><i class="bi bi-arrow-left me-1"></i>Zpět na přihlášení</small>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../views/layouts/main.php';
