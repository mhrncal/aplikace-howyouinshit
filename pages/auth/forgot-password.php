<?php
require_once __DIR__ . '/../../bootstrap.php';

if ($auth->check()) {
    redirect('/dashboard.php');
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
        
        // Security: Vždy zobrazíme success message, i když email neexistuje
        $success = true;
        
        if ($token) {
            // Generování reset linku
            $resetLink = baseUrl("reset-password.php?token={$token}");
            
            // V produkci by se poslal email přes SMTP
            // Pro development/testování vypíšeme link do logu
            App\Core\Logger::info('Password reset requested', [
                'email' => $email,
                'reset_link' => $resetLink
            ]);
            
            // DEVELOPMENT MODE: Zobrazíme link přímo uživateli
            // V produkci toto SMAZAT!
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
                            <p class="text-muted">Zadejte váš email a pošleme vám odkaz pro reset hesla</p>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Email odeslán!</strong><br>
                                Pokud je email registrován v systému, obdržíte odkaz pro reset hesla.
                            </div>
                            
                            <?php if (isset($_SESSION['debug_reset_link'])): ?>
                                <div class="alert alert-info" role="alert">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>DEVELOPMENT MODE:</strong><br>
                                    Email není nakonfigurován. Použijte tento link:<br>
                                    <a href="<?= e($_SESSION['debug_reset_link']) ?>" class="text-break">
                                        <?= e($_SESSION['debug_reset_link']) ?>
                                    </a>
                                </div>
                                <?php unset($_SESSION['debug_reset_link']); ?>
                            <?php endif; ?>
                            
                            <div class="text-center mt-4">
                                <a href="/login.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left me-2"></i>
                                    Zpět na přihlášení
                                </a>
                            </div>
                        <?php else: ?>
                            <?php if ($error = getFlash('error')): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-circle me-2"></i>
                                    <?= e($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="/forgot-password.php">
                                <?= csrf() ?>
                                
                                <div class="mb-4">
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
                                               required 
                                               autofocus>
                                    </div>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send me-2"></i>
                                        Odeslat reset link
                                    </button>
                                </div>
                                
                                <div class="text-center">
                                    <a href="/login.php" class="text-decoration-none">
                                        <small><i class="bi bi-arrow-left me-1"></i>Zpět na přihlášení</small>
                                    </a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/views/layouts/main.php';
