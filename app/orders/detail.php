<?php
require_once __DIR__ . '/../../bootstrap.php';
$auth->requireAuth();

use App\Models\Order;

$orderModel = new Order();
$userId = $auth->userId();
$orderId = (int) get('id');

if (!$orderId) {
    flash('error', 'Neplatné ID objednávky');
    redirect('/app/orders/');
}

$order = $orderModel->findByIdWithItems($orderId, $userId);

if (!$order) {
    flash('error', 'Objednávka nenalezena');
    redirect('/app/orders/');
}

// Rozděl položky podle typu
$products = [];
$shipping = null;
$billing = null;
$discounts = [];

foreach ($order['items'] as $item) {
    switch ($item['item_type']) {
        case 'product':
            $products[] = $item;
            break;
        case 'shipping':
            $shipping = $item;
            break;
        case 'billing':
            $billing = $item;
            break;
        case 'discount':
            $discounts[] = $item;
            break;
    }
}

$title = 'Detail objednávky ' . $order['order_code'];
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Objednávka <?= e($order['order_code']) ?></h2>
    <a href="/app/orders/" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Zpět
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Produkty -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-box-seam me-2"></i>Produkty (<?= count($products) ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produkt</th>
                                <th>Kód</th>
                                <th>Varianta</th>
                                <th class="text-end">Množství</th>
                                <th class="text-end">Prodej</th>
                                <th class="text-end">Nákup</th>
                                <th class="text-end">Zisk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= e($item['item_name']) ?></strong>
                                    <?php if ($item['manufacturer']): ?>
                                        <br><small class="text-muted"><?= e($item['manufacturer']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= e($item['item_code'] ?? '-') ?></code></td>
                                <td><?= e($item['variant_name'] ?? '-') ?></td>
                                <td class="text-end"><?= $item['amount'] ?> ks</td>
                                <td class="text-end"><?= formatPrice($item['unit_price_sale']) ?></td>
                                <td class="text-end text-muted"><?= formatPrice($item['unit_price_cost']) ?></td>
                                <td class="text-end">
                                    <strong class="<?= $item['total_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= formatPrice($item['total_profit']) ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Doprava + Platba + Slevy -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-truck me-2"></i>Doprava, platba a slevy
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Typ</th>
                                <th>Název</th>
                                <th class="text-end">Cena</th>
                                <th class="text-end">Náklad</th>
                                <th class="text-end">Zisk/Ztráta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($shipping): ?>
                            <tr>
                                <td><span class="badge bg-info">Doprava</span></td>
                                <td><?= e($shipping['item_name']) ?></td>
                                <td class="text-end"><?= formatPrice($shipping['total_revenue']) ?></td>
                                <td class="text-end text-muted"><?= formatPrice($shipping['total_cost']) ?></td>
                                <td class="text-end">
                                    <strong class="<?= $shipping['total_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= formatPrice($shipping['total_profit']) ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if ($billing): ?>
                            <tr>
                                <td><span class="badge bg-warning">Platba</span></td>
                                <td><?= e($billing['item_name']) ?></td>
                                <td class="text-end"><?= formatPrice($billing['total_revenue']) ?></td>
                                <td class="text-end text-muted"><?= formatPrice($billing['total_cost']) ?></td>
                                <td class="text-end">
                                    <strong class="<?= $billing['total_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= formatPrice($billing['total_profit']) ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php foreach ($discounts as $discount): ?>
                            <tr>
                                <td><span class="badge bg-danger">Sleva</span></td>
                                <td><?= e($discount['item_name']) ?></td>
                                <td class="text-end text-danger"><?= formatPrice($discount['total_revenue']) ?></td>
                                <td class="text-end">-</td>
                                <td class="text-end">
                                    <strong class="text-danger"><?= formatPrice($discount['total_profit']) ?></strong>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Souhrn -->
        <div class="card mb-3" style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: white;">
            <div class="card-header border-0" style="background: transparent;">
                <strong>Finanční souhrn</strong>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Celkový obrat:</span>
                    <strong><?= formatPrice($order['total_revenue']) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Celkové náklady:</span>
                    <strong><?= formatPrice($order['total_cost']) ?></strong>
                </div>
                <hr style="border-color: rgba(255,255,255,0.3);">
                <div class="d-flex justify-content-between mb-2">
                    <span><strong>Celkový zisk:</strong></span>
                    <h4 class="mb-0"><?= formatPrice($order['total_profit']) ?></h4>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Marže:</span>
                    <strong><?= number_format($order['margin_percent'], 1) ?>%</strong>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Informace
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Datum:</strong><br>
                    <small><?= formatDate($order['order_date'], 'd.m.Y H:i') ?></small>
                </div>
                
                <div class="mb-3">
                    <strong>Status:</strong><br>
                    <?php if ($order['status'] === 'Vyřízena'): ?>
                        <span class="badge bg-success"><?= e($order['status']) ?></span>
                    <?php elseif ($order['status'] === 'Stornována'): ?>
                        <span class="badge bg-danger"><?= e($order['status']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><?= e($order['status']) ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($order['source']): ?>
                <div class="mb-3">
                    <strong>Zdroj:</strong><br>
                    <small><?= e($order['source']) ?></small>
                </div>
                <?php endif; ?>
                
                <?php if ($order['customer_group']): ?>
                <div class="mb-3">
                    <strong>Skupina zákazníka:</strong><br>
                    <small><?= e($order['customer_group']) ?></small>
                </div>
                <?php endif; ?>
                
                <div class="mb-0">
                    <strong>Měna:</strong><br>
                    <small><?= e($order['currency']) ?></small>
                    <?php if ($order['exchange_rate'] != 1): ?>
                        <br><small class="text-muted">Kurz: <?= $order['exchange_rate'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../views/layouts/main.php';
