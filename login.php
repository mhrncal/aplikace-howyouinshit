<?php
require_once __DIR__ . '/bootstrap.php';

// Pokud už je přihlášen, redirect na dashboard
if ($auth->check()) {
    redirect('/app/dashboard/');
}

$error = null;

// Zpracování přihlášení
if (isPost()) {
    $email = post('email');
    $password = post('password');
    
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        $error = 'Neplatný požadavek. Zkuste to znovu.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Vyplňte email a heslo.';
    } elseif ($auth->login($email, $password)) {
        // Úspěšné přihlášení
        $intendedUrl = $_SESSION['intended_url'] ?? '/app/dashboard/';
        unset($_SESSION['intended_url']);
        redirect($intendedUrl);
    } else {
        $error = 'Neplatný email nebo heslo.';
    }
}

$title = 'Přihlášení';
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
                                <i class="bi bi-graph-up-arrow" style="font-size: 3rem; color: #6366f1;"></i>
                            </div>
                            <h2 class="fw-bold mb-2">E-shop Analytics</h2>
                            <p class="text-muted">Přihlaste se do svého účtu</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?= e($error) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success = getFlash('success')): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <?= e($success) ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="/login.php">
                            <?= csrf() ?>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control border-start-0" 
                                           id="email" 
                                           name="email" 
                                           placeholder="vas@email.cz"
                                           value="<?= e(old('email')) ?>"
                                           required 
                                           autofocus>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Heslo</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0" 
                                           id="password" 
                                           name="password" 
                                           placeholder="••••••••"
                                           required>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Přihlásit se
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <a href="/forgot-password.php" class="text-decoration-none">
                                    <small>Zapomněli jste heslo?</small>
                                </a>
                            </div>
                        </form>
                        
                        <div class="mt-4 pt-4 border-top text-center">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Vaše data jsou v bezpečí
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-white">
                        &copy; <?= date('Y') ?> E-shop Analytics. Všechna práva vyhrazena.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearOldInput();
require __DIR__ . '/views/layouts/main.php';
