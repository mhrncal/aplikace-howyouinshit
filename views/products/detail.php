<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><?= e($product['name']) ?></h2>
    <a href="/app/products/" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Zpět na seznam
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Základní info -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Základní informace
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Název:</strong>
                    </div>
                    <div class="col-md-8">
                        <?= e($product['name']) ?>
                    </div>
                </div>
                
                <?php if (!empty($product['code'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Kód produktu:</strong>
                    </div>
                    <div class="col-md-8">
                        <code><?= e($product['code']) ?></code>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['ean'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>EAN:</strong>
                    </div>
                    <div class="col-md-8">
                        <code><?= e($product['ean']) ?></code>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['manufacturer'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Výrobce:</strong>
                    </div>
                    <div class="col-md-8">
                        <?= e($product['manufacturer']) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['supplier'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Dodavatel:</strong>
                    </div>
                    <div class="col-md-8">
                        <?= e($product['supplier']) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['category'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Výrobce:</strong>
                    </div>
                    <div class="col-md-8">
                        <?= e($product['manufacturer']) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['category'])): ?>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Kategorie:</strong>
                    </div>
                    <div class="col-md-8">
                        <?= e($product['category']) ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (false): ?> <!-- Popis skrytý -->
                <div class="row">
                    <div class="col-md-4">
                        <strong>Popis:</strong>
                    </div>
                    <div class="col-md-8">
                        <?= nl2br(e($product['description'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Varianty -->
        <?php if (!empty($variants)): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-diagram-3 me-2"></i>Varianty produktu (<?= count($variants) ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Název varianty</th>
                                <th>Kód</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variants as $variant): ?>
                            <tr>
                                <td>
                                    <strong><?= e($variant['name'] ?? '-') ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($variant['code'])): ?>
                                        <code><?= e($variant['code']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Tento produkt nemá žádné varianty.
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- Metadata -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Metadata
            </div>
            <div class="card-body">
                <p class="small mb-2">
                    <strong>Vytvořeno:</strong><br>
                    <?= formatDate($product['created_at']) ?>
                </p>
                <p class="small mb-0">
                    <strong>Aktualizováno:</strong><br>
                    <?= formatDate($product['updated_at']) ?>
                </p>
            </div>
        </div>
        
        <!-- URL -->
        <?php if (!empty($product['url'])): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-link-45deg me-2"></i>Odkazy
            </div>
            <div class="card-body">
                <a href="<?= e($product['url']) ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Zobrazit na e-shopu
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
