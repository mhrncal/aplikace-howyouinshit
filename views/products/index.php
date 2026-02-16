<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Produkty</h2>
        <?php if (isset($pagination)): ?>
            <p class="text-muted mb-0">Celkem <?= number_format($pagination['total']) ?> produktů</p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="/app/products/?action=export" class="btn btn-outline-success">
            <i class="bi bi-download me-2"></i>
            Export CSV
        </a>
    </div>
</div>

<!-- Search -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/app/products/" class="row g-3">
            <div class="col-md-10">
                <input type="text" 
                       class="form-control" 
                       name="search" 
                       placeholder="Hledat podle názvu, kódu..."
                       value="<?= e($search ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Hledat
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="bi bi-box-seam"></i>
                <p class="mb-0">Žádné produkty</p>
                <small class="text-muted">Přidejte feed zdroj a spusťte import</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Název</th>
                            <th>Kód / Varianty</th>
                            <th>Kategorie</th>
                            <th>Výrobce</th>
                            <th>Dodavatel</th>
                            <?php if ($auth->isSuperAdmin() && isset($products[0]['user_name'])): ?>
                                <th>Uživatel</th>
                            <?php endif; ?>
                            <th width="100">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <strong><?= e($product['name']) ?></strong>
                                <?php if ($product['variant_count'] > 0): ?>
                                    <br><small class="text-muted"><i class="bi bi-grid-3x3-gap me-1"></i><?= $product['variant_count'] ?> <?= $product['variant_count'] == 1 ? 'varianta' : ($product['variant_count'] < 5 ? 'varianty' : 'variant') ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['variant_count'] > 0): ?>
                                    <small class="text-muted d-block mb-1"><?= $product['variant_count'] ?> <?= $product['variant_count'] == 1 ? 'kód' : 'kódů' ?>:</small>
                                    <?php 
                                    $codes = array_filter(array_column($product['variants'], 'code'));
                                    ?>
                                    <?php if (!empty($codes)): ?>
                                        <?php foreach (array_slice($codes, 0, 2) as $code): ?>
                                            <div><code class="small"><?= e($code) ?></code></div>
                                        <?php endforeach; ?>
                                        <?php if (count($codes) > 2): ?>
                                            <small class="text-muted">... +<?= count($codes) - 2 ?> dalších</small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (!empty($product['code'])): ?>
                                        <div><code><?= e($product['code']) ?></code></div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($product['category'] ?? '-') ?></td>
                            <td><?= e($product['manufacturer'] ?? '-') ?></td>
                            <td><?= e($product['supplier'] ?? '-') ?></td>
                            <?php if ($auth->isSuperAdmin() && isset($product['user_name'])): ?>
                                <td>
                                    <small><?= e($product['user_name']) ?></small>
                                </td>
                            <?php endif; ?>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="/app/products/?action=detail&id=<?= $product['id'] ?>" 
                                       class="btn btn-outline-primary"
                                       title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!$auth->isSuperAdmin() || !isset($product['user_name'])): ?>
                                    <form method="POST" action="/products.php?action=delete" class="d-inline"
                                          onsubmit="return confirm('Opravdu smazat tento produkt?')">
                                        <?= csrf() ?>
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Smazat">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php if ($pagination['has_prev']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Předchozí</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <?php if ($i === $pagination['current_page']): ?>
                        <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                    <?php elseif ($i === 1 || $i === $pagination['total_pages'] || abs($i - $pagination['current_page']) <= 2): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a></li>
                    <?php elseif ($i === 2 || $i === $pagination['total_pages'] - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($pagination['has_more']): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Další</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
