<?php
require_once __DIR__ . '/../../bootstrap.php';

if ($auth->check()) {
    redirect('/app/dashboard/');
}

$success = false;

if (isPost()) {
    $email = post('email');
    
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
    } elseif (empty($email)) {
        flash('error', 'Vyplňte email');
    } elseif (!App\Core\Security::validateEmail($email)) {
        flash('error', 'Neplatný formát emailu');
    } else {
        $token = $auth->createPasswordResetToken($email);
        $success = true;
        
        if ($token) {
            $resetLink = baseUrl("app/auth/reset-password.php?token={$token}");
            App\Core\Logger::info('Password reset requested', [
                'email' => $email,
                'reset_link' => $resetLink
            ]);
            
            // DEVELOPMENT MODE: Zobrazíme link
            $_SESSION['debug_reset_link'] = $resetLink;
        }
    }
}

$title = 'Zapomenuté heslo';
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
                                <i class="bi bi-key" style="font-size: 3rem; color: #6366f1;"></i>
                            </div>
                            <h2 class="fw-bold mb-2">Zapomenuté heslo</h2>
                            <p class="text-muted">Zadejte váš email</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Email odeslán!
                            </div>
                            
                            <?php if (isset($_SESSION['debug_reset_link'])): ?>
                                <div class="alert alert-info">
                                    <strong>DEV MODE:</strong><br>
                                    <a href="<?= e($_SESSION['debug_reset_link']) ?>" class="text-break">
                                        <?= e($_SESSION['debug_reset_link']) ?>
                                    </a>
                                </div>
                                <?php unset($_SESSION['debug_reset_link']); ?>
                            <?php endif; ?>
                            
                            <a href="/login.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-left me-2"></i>Zpět na přihlášení
                            </a>
                        <?php else: ?>
                            <?php if ($error = getFlash('error')): ?>
                                <div class="alert alert-danger"><?= e($error) ?></div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <?= csrf() ?>
                                <div class="mb-4">
                                    <input type="email" 
                                           class="form-control" 
                                           name="email" 
                                           placeholder="Email"
                                           required autofocus>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 btn-lg">
                                    Odeslat reset link
                                </button>
                            </form>
                            
                            <div class="text-center mt-3">
                                <a href="/login.php">Zpět na přihlášení</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
