<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Models\Cost;

$costModel = new Cost();
$userId = $auth->userId();
$costId = (int) get('id');

if (!$costId) {
    flash('error', 'Neplatné ID nákladu');
    redirect('/app/costs/');
}

$cost = $costModel->findById($costId, $userId);

if (!$cost) {
    flash('error', 'Náklad nenalezen');
    redirect('/app/costs/');
}

$errors = [];

if (isPost()) {
    if (!App\Core\Security::verifyCsrfToken(post('csrf_token'))) {
        flash('error', 'Neplatný požadavek');
        redirect('/app/costs/');
    }
    
    $data = [
        'name' => post('name'),
        'description' => post('description'),
        'amount' => (float) post('amount'),
        'type' => post('type'),
        'frequency' => post('frequency'),
        'category' => post('category'),
        'start_date' => post('start_date'),
        'end_date' => post('end_date') ?: null,
        'is_active' => post('is_active', '0') === '1',
    ];
    
    // Validace
    if (empty($data['name'])) {
        $errors[] = 'Název je povinný';
    }
    if ($data['amount'] <= 0) {
        $errors[] = 'Částka musí být větší než 0';
    }
    if (empty($data['start_date'])) {
        $errors[] = 'Datum začátku je povinné';
    }
    
    if (empty($errors)) {
        if ($costModel->update($costId, $userId, $data)) {
            flash('success', 'Náklad byl úspěšně aktualizován');
            redirect('/app/costs/');
        } else {
            $errors[] = 'Nepodařilo se aktualizovat náklad';
        }
    }
    
    saveOldInput();
}

$title = 'Upravit náklad';
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil me-2"></i>Úprava nákladu #<?= $costId ?></span>
                <a href="/app/costs/" class="btn btn-sm btn-outline-secondary">
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
                    
                    <h6 class="border-bottom pb-2 mb-3">Základní informace</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Název nákladu *</label>
                        <input type="text" class="form-control" name="name" value="<?= e(old('name', $cost['name'])) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Popis</label>
                        <textarea class="form-control" name="description" rows="2"><?= e(old('description', $cost['description'])) ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Částka *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="amount" value="<?= e(old('amount', $cost['amount'])) ?>" step="0.01" min="0" required>
                                <span class="input-group-text">Kč</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategorie</label>
                            <input type="text" class="form-control" name="category" value="<?= e(old('category', $cost['category'])) ?>" list="categories">
                            <datalist id="categories">
                                <option value="Mzdy">
                                <option value="Provoz">
                                <option value="Marketing">
                                <option value="IT">
                                <option value="Služby">
                                <option value="Energie">
                                <option value="Nájem">
                            </datalist>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Typ a frekvence</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Typ nákladu *</label>
                            <select class="form-select" name="type" required>
                                <option value="fixed" <?= old('type', $cost['type']) === 'fixed' ? 'selected' : '' ?>>Fixní</option>
                                <option value="variable" <?= old('type', $cost['type']) === 'variable' ? 'selected' : '' ?>>Variabilní</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Frekvence *</label>
                            <select class="form-select" name="frequency" required>
                                <option value="monthly" <?= old('frequency', $cost['frequency']) === 'monthly' ? 'selected' : '' ?>>Měsíčně</option>
                                <option value="daily" <?= old('frequency', $cost['frequency']) === 'daily' ? 'selected' : '' ?>>Denně</option>
                                <option value="weekly" <?= old('frequency', $cost['frequency']) === 'weekly' ? 'selected' : '' ?>>Týdně</option>
                                <option value="quarterly" <?= old('frequency', $cost['frequency']) === 'quarterly' ? 'selected' : '' ?>>Kvartálně (3 měsíce)</option>
                                <option value="yearly" <?= old('frequency', $cost['frequency']) === 'yearly' ? 'selected' : '' ?>>Ročně</option>
                                <option value="once" <?= old('frequency', $cost['frequency']) === 'once' ? 'selected' : '' ?>>Jednorázově</option>
                            </select>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Období platnosti</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Platí od *</label>
                            <input type="date" class="form-control" name="start_date" value="<?= e(old('start_date', $cost['start_date'])) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Platí do</label>
                            <input type="date" class="form-control" name="end_date" value="<?= e(old('end_date', $cost['end_date'])) ?>">
                            <small class="text-muted">Prázdné = neomezeno</small>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= old('is_active', $cost['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">
                            Aktivní (započítává se do celkových nákladů)
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Uložit změny
                        </button>
                        <a href="/app/costs/" class="btn btn-outline-secondary">
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
                <p><strong>Vytvořeno:</strong><br>
                <small class="text-muted"><?= formatDate($cost['created_at']) ?></small></p>
                
                <p class="mb-0"><strong>Aktualizováno:</strong><br>
                <small class="text-muted"><?= formatDate($cost['updated_at']) ?></small></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightbulb me-2"></i>Nápověda
            </div>
            <div class="card-body">
                <p class="small mb-0">Změna frekvence ovlivní měsíční přepočet v analytice. Systém automaticky přepočítá všechny náklady na měsíční částku.</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
clearOldInput();
require __DIR__ . '/../../views/layouts/main.php';
