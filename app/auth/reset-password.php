<?php
require_once __DIR__ . '/../../bootstrap.php';

if ($auth->check()) {
    redirect('/app/dashboard/index.php');
}

$token = get('token');
$error = null;

if (empty($token)) {
    flash('error', 'Neplatný token');
    redirect('/login.php');
}

$userId = $auth->verifyPasswordResetToken($token);

if (!$userId) {
    flash('error', 'Token je neplatný nebo vypršel');
    redirect('/app/auth/forgot-password.php');
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
            flash('success', 'Heslo bylo změněno');
            redirect('/login.php');
        } else {
            $error = 'Nepodařilo se změnit heslo';
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
                        <h2 class="text-center mb-4">Nové heslo</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <?= csrf() ?>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Nové heslo" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password_confirm" placeholder="Potvrzení hesla" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg">Změnit heslo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
