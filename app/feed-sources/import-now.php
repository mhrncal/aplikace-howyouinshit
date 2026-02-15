<?php
require_once __DIR__ . '/../../bootstrap.php';

$auth->requireAuth();

use App\Modules\FeedSources\Models\FeedSource;

$feedSourceModel = new FeedSource();
$userId = $auth->userId();
$feedId = (int) get('id');

if (!$feedId) {
    flash('error', 'Neplatné ID feed zdroje');
    redirect('/app/feed-sources/');
}

$feed = $feedSourceModel->findById($feedId, $userId);

if (!$feed) {
    flash('error', 'Feed zdroj nenalezen');
    redirect('/app/feed-sources/');
}

// Kontrola jestli se má spustit import
$importing = isPost();

$title = 'Import feedu';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-cloud-download me-2"></i>Import z feedu: <?= e($feed['name']) ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($importing): ?>
                    <!-- PROGRESS BAR -->
                    <div id="import-progress">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span id="status-text" class="fw-bold">Připravuji import...</span>
                                <span id="status-percent" class="badge bg-primary">0%</span>
                            </div>
                            <div class="progress" style="height: 35px;">
                                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                     role="progressbar" style="width: 0%">
                                    <span id="progress-text" class="fw-bold">Zahajuji...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="import-stats" class="row text-center mb-4 d-none">
                            <div class="col-4">
                                <div class="p-3 bg-light rounded">
                                    <h2 id="stat-imported" class="text-success mb-0">0</h2>
                                    <small class="text-muted">Importováno</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 bg-light rounded">
                                    <h2 id="stat-updated" class="text-info mb-0">0</h2>
                                    <small class="text-muted">Aktualizováno</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 bg-light rounded">
                                    <h2 id="stat-errors" class="text-danger mb-0">0</h2>
                                    <small class="text-muted">Chyby</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <i class="bi bi-terminal me-2"></i>Log importu
                            </div>
                            <div id="import-log" class="card-body" style="max-height: 400px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 13px;">
                                <div class="text-muted">Čekám na start importu...</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- VÝSLEDEK -->
                    <div id="import-result" class="d-none">
                        <div id="result-content"></div>
                        <div class="mt-4 d-flex gap-2">
                            <a href="/app/feed-sources/" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Zpět na seznam feedů
                            </a>
                            <a href="/app/products/" class="btn btn-success">
                                <i class="bi bi-box-seam me-2"></i>Zobrazit produkty
                            </a>
                            <a href="?id=<?= $feedId ?>" class="btn btn-primary">
                                <i class="bi bi-arrow-repeat me-2"></i>Importovat znovu
                            </a>
                        </div>
                    </div>
                    
                    <script>
                    let startTime = Date.now();
                    let logDiv = document.getElementById('import-log');
                    
                    function addLog(message, type = 'info') {
                        let colors = {
                            'info': '#9cdcfe',
                            'success': '#4ec9b0',
                            'error': '#f48771',
                            'warning': '#ce9178'
                        };
                        
                        let time = new Date().toLocaleTimeString();
                        let div = document.createElement('div');
                        div.style.color = colors[type] || colors.info;
                        div.style.marginBottom = '5px';
                        div.innerHTML = `<span style="color: #6a9955">[${time}]</span> ${message}`;
                        logDiv.appendChild(div);
                        logDiv.scrollTop = logDiv.scrollHeight;
                    }
                    
                    function updateProgress(percent, text, status) {
                        document.getElementById('progress-bar').style.width = percent + '%';
                        document.getElementById('progress-text').textContent = text;
                        document.getElementById('status-text').textContent = status;
                        document.getElementById('status-percent').textContent = Math.round(percent) + '%';
                    }
                    
                    addLog('🚀 <strong>Zahajuji import feedu...</strong>', 'info');
                    addLog('📡 URL: <?= addslashes($feed['url']) ?>', 'info');
                    updateProgress(5, 'Připojuji se...', 'Zahájeno');
                    
                    // CSRF token z meta tagu nebo session
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '<?= $_SESSION['csrf_token'] ?? '' ?>';
                    
                    addLog('🔑 CSRF token: ' + csrfToken.substring(0, 10) + '...', 'info');
                    
                    fetch('/app/feed-sources/import-ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'feed_id=<?= $feedId ?>&csrf_token=' + encodeURIComponent(csrfToken)
                    })
                    .then(response => {
                        addLog('📥 Odpověď ze serveru přijata (HTTP ' + response.status + ')', 'info');
                        
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status + ' ' + response.statusText);
                        }
                        
                        // Přečti response jako text FIRST (pro debug)
                        return response.text().then(text => {
                            addLog('📄 Response délka: ' + text.length + ' znaků', 'info');
                            
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                addLog('❌ Neplatný JSON! Prvních 200 znaků:', 'error');
                                addLog(text.substring(0, 200), 'error');
                                throw new Error('Server nevrátil JSON: ' + text.substring(0, 100));
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            updateProgress(100, '✅ Hotovo!', 'Import dokončen');
                            
                            document.getElementById('stat-imported').textContent = data.imported;
                            document.getElementById('stat-updated').textContent = data.updated;
                            document.getElementById('stat-errors').textContent = data.errors;
                            document.getElementById('import-stats').classList.remove('d-none');
                            
                            let elapsed = Math.round((Date.now() - startTime) / 1000);
                            addLog(`✅ <strong>Import dokončen!</strong>`, 'success');
                            addLog(`📦 Importováno: <strong>${data.imported}</strong> produktů`, 'success');
                            addLog(`🔄 Aktualizováno: <strong>${data.updated}</strong> produktů`, 'info');
                            addLog(`❌ Chyby: <strong>${data.errors}</strong>`, data.errors > 0 ? 'warning' : 'info');
                            addLog(`⏱️ Celkový čas: <strong>${elapsed}s</strong>`, 'info');
                            
                            setTimeout(() => {
                                document.getElementById('import-progress').classList.add('d-none');
                                document.getElementById('import-result').classList.remove('d-none');
                                document.getElementById('result-content').innerHTML = `
                                    <div class="alert alert-success">
                                        <h4 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Import dokončen!</h4>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-3"><strong>Importováno:</strong><br><span class="fs-4 text-success">${data.imported}</span> produktů</div>
                                            <div class="col-md-3"><strong>Aktualizováno:</strong><br><span class="fs-4 text-info">${data.updated}</span> produktů</div>
                                            <div class="col-md-3"><strong>Chyby:</strong><br><span class="fs-4 ${data.errors > 0 ? 'text-danger' : 'text-muted'}">${data.errors}</span></div>
                                            <div class="col-md-3"><strong>Čas:</strong><br><span class="fs-4">${elapsed}s</span></div>
                                        </div>
                                    </div>
                                `;
                            }, 1500);
                            
                        } else {
                            updateProgress(0, '❌ Chyba!', 'Import selhal');
                            addLog(`❌ <strong>Chyba importu:</strong> ${data.error}`, 'error');
                            
                            document.getElementById('import-progress').classList.add('d-none');
                            document.getElementById('import-result').classList.remove('d-none');
                            document.getElementById('result-content').innerHTML = `
                                <div class="alert alert-danger">
                                    <h4 class="alert-heading"><i class="bi bi-x-circle me-2"></i>Import selhal</h4>
                                    <hr>
                                    <p class="mb-0"><code>${data.error}</code></p>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        updateProgress(0, '❌ Chyba!', 'Chyba připojení');
                        addLog(`❌ <strong>Chyba připojení:</strong> ${error.message}`, 'error');
                        
                        document.getElementById('import-progress').classList.add('d-none');
                        document.getElementById('import-result').classList.remove('d-none');
                        document.getElementById('result-content').innerHTML = `
                            <div class="alert alert-danger">
                                <h4 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Chyba připojení</h4>
                                <hr>
                                <p class="mb-0">${error.message}</p>
                                <small class="text-muted">Import může běžet na pozadí. Zkontrolujte produkty za chvíli.</small>
                            </div>
                        `;
                    });
                    
                    // Simulace progressu
                    let fakeProgress = 5;
                    let logMessages = [
                        '📡 Stahuji XML feed...',
                        '🔍 Parsuji XML strukturu...',
                        '📦 Zpracovávám SHOPITEM elementy...',
                        '💾 Ukládám batch produktů do DB...',
                        '🔄 Kontroluji duplikáty...',
                        '✨ Optimalizuji data...'
                    ];
                    let msgIndex = 0;
                    
                    let progressInterval = setInterval(() => {
                        if (fakeProgress < 85) {
                            fakeProgress += Math.random() * 4;
                            updateProgress(fakeProgress, 'Zpracovávám...', 'Probíhá import');
                            
                            if (Math.random() > 0.6 && msgIndex < logMessages.length) {
                                addLog(logMessages[msgIndex], 'info');
                                msgIndex++;
                            }
                        }
                    }, 3000);
                    
                    setTimeout(() => clearInterval(progressInterval), 120000);
                    </script>
                    
                <?php else: ?>
                    <!-- FORMULÁŘ -->
                    <div class="alert alert-info">
                        <h5 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>Připraveno k importu
                        </h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <strong>Feed:</strong> <?= e($feed['name']) ?><br>
                                <strong>Typ:</strong> <?= e($feed['feed_type'] ?? $feed['type']) ?>
                            </div>
                            <div class="col-md-6">
                                <strong>URL:</strong><br>
                                <code style="font-size: 11px;"><?= e($feed['url']) ?></code>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <?= csrf() ?>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-cloud-download me-2"></i>
                            Spustit import
                        </button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="/app/feed-sources/" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Zpět
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
