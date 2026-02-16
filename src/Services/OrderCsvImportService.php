<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Order;
use App\Models\ShippingCost;
use App\Models\BillingCost;

/**
 * OrderCsvImportService - Import objednávek z CSV
 */
class OrderCsvImportService
{
    private Database $db;
    private Order $orderModel;
    private ShippingCost $shippingModel;
    private BillingCost $billingModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
        $this->shippingModel = new ShippingCost();
        $this->billingModel = new BillingCost();
    }

    /**
     * Importuje objednávky z CSV URL
     */
    public function importFromUrl(int $userId, string $url): array
    {
        try {
            $startTime = microtime(true);
            
            // Stažení CSV
            $csvContent = $this->downloadCsv($url);
            
            if (!$csvContent) {
                return ['success' => false, 'error' => 'Nepodařilo se stáhnout CSV'];
            }
            
            // Parsování
            $result = $this->parseCsv($userId, $csvContent);
            
            $duration = round(microtime(true) - $startTime, 2);
            
            Logger::info('Orders imported', [
                'user_id' => $userId,
                'orders' => $result['orders_imported'],
                'items' => $result['items_imported'],
                'duration' => $duration
            ]);
            
            return [
                'success' => true,
                'orders_imported' => $result['orders_imported'],
                'items_imported' => $result['items_imported'],
                'duration' => $duration
            ];
            
        } catch (\Exception $e) {
            Logger::error('Order import failed', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Stáhne CSV z URL
     */
    private function downloadCsv(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        return $content ?: null;
    }

    /**
     * Parsuje CSV a ukládá objednávky
     */
    private function parseCsv(int $userId, string $csvContent): array
    {
        $lines = explode("\n", $csvContent);
        
        if (empty($lines)) {
            throw new \Exception('CSV je prázdné');
        }
        
        // První řádek = hlavička
        $header = str_getcsv(array_shift($lines), ';');
        
        // Odstranění BOM
        $header[0] = str_replace("\xEF\xBB\xBF", '', $header[0]);
        
        $ordersProcessed = [];
        $itemsImported = 0;
        $currentOrderData = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $row = str_getcsv($line, ';');
            
            if (count($row) !== count($header)) {
                continue; // Přeskočit chybné řádky
            }
            
            $data = array_combine($header, $row);
            
            $orderCode = $data['code'];
            
            // Nová objednávka?
            if (!isset($currentOrderData[$orderCode])) {
                $currentOrderData[$orderCode] = [
                    'order' => $this->parseOrderData($userId, $data),
                    'items' => []
                ];
            }
            
            // Přidej položku
            $item = $this->parseOrderItem($data);
            if ($item) {
                $currentOrderData[$orderCode]['items'][] = $item;
            }
        }
        
        // Ulož všechny objednávky
        foreach ($currentOrderData as $orderCode => $orderData) {
            $orderId = $this->saveOrder($userId, $orderData);
            
            if ($orderId) {
                $ordersProcessed[$orderCode] = $orderId;
                $itemsImported += count($orderData['items']);
            }
        }
        
        return [
            'orders_imported' => count($ordersProcessed),
            'items_imported' => $itemsImported
        ];
    }

    /**
     * Parsuje základní data objednávky
     */
    private function parseOrderData(int $userId, array $row): array
    {
        return [
            'user_id' => $userId,
            'order_code' => $row['code'],
            'order_date' => $row['date'],
            'status' => $row['statusName'],
            'currency' => 'CZK',
            'exchange_rate' => !empty($row['currencyExchangeRate']) ? (float) str_replace(',', '.', $row['currencyExchangeRate']) : 1.0,
            'source' => $row['sourceName'] ?? null,
            'customer_group' => $row['customerGroupName'] ?? null,
        ];
    }

    /**
     * Parsuje položku objednávky
     */
    private function parseOrderItem(array $row): ?array
    {
        $type = $row['orderItemType'];
        
        if (!in_array($type, ['product', 'shipping', 'billing', 'discount'])) {
            return null;
        }
        
        // Čištění cen
        $unitPriceSale = $this->parsePrice($row['orderItemUnitDiscountPriceWithVat'] ?? '0');
        $unitPriceCost = $this->parsePrice($row['orderItemUnitPurchasePriceWithVat'] ?? '0');
        $amount = (int) ($row['orderItemAmount'] ?? 1);
        
        return [
            'item_type' => $type,
            'item_name' => $row['orderItemName'],
            'item_code' => $row['orderItemCode'] ?? null,
            'variant_name' => $row['orderItemVariantName'] ?? null,
            'manufacturer' => $row['orderItemManufacturer'] ?? null,
            'supplier' => $row['orderItemSupplier'] ?? null,
            'amount' => $amount,
            'unit_price_sale' => $unitPriceSale,
            'unit_price_cost' => $unitPriceCost,
            'total_revenue' => 0, // Vypočítá se v saveOrder
            'total_cost' => 0,
            'total_profit' => 0
        ];
    }

    /**
     * Parsuje cenu (odstraní mezery, nahradí čárku tečkou)
     */
    private function parsePrice(string $price): float
    {
        $price = str_replace([' ', ','], ['', '.'], $price);
        return (float) $price;
    }

    /**
     * Uloží objednávku a položky
     */
    private function saveOrder(int $userId, array $orderData): ?int
    {
        try {
            // Ulož hlavní objednávku
            $orderId = $this->orderModel->upsert($orderData['order']);
            
            // Smaž staré položky
            $this->db->query("DELETE FROM order_items WHERE order_id = ?", [$orderId]);
            
            // Najdi celkový obrat pro výpočet % nákladů na platbu
            $totalOrderRevenue = 0;
            foreach ($orderData['items'] as $item) {
                if ($item['item_type'] === 'product') {
                    $totalOrderRevenue += $item['unit_price_sale'] * $item['amount'];
                }
            }
            
            // Ulož položky s výpočtem zisků
            foreach ($orderData['items'] as $item) {
                $item = $this->calculateItemProfit($userId, $item, $totalOrderRevenue);
                $this->orderModel->addItem($orderId, $item);
            }
            
            // Přepočítej součty objednávky
            $this->orderModel->recalculateTotals($orderId);
            
            return $orderId;
            
        } catch (\Exception $e) {
            Logger::error('Failed to save order', [
                'order_code' => $orderData['order']['order_code'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vypočítá zisk/ztrátu pro položku
     */
    private function calculateItemProfit(int $userId, array $item, float $orderTotal): array
    {
        $type = $item['item_type'];
        $amount = $item['amount'];
        
        switch ($type) {
            case 'product':
                // Produkt: zisk = (prodejní - nákupní) × množství
                $revenue = $item['unit_price_sale'] * $amount;
                $cost = $item['unit_price_cost'] * $amount;
                $profit = $revenue - $cost;
                break;
                
            case 'shipping':
                // Doprava: revenue - náklady z mappingu
                // Automaticky vytvoř mapping pokud neexistuje
                $this->ensureShippingMapping($userId, $item['item_code'], $item['item_name']);
                
                $revenue = $item['unit_price_sale'];
                $cost = $this->shippingModel->getCost($userId, $item['item_code']);
                $profit = $revenue - $cost;
                break;
                
            case 'billing':
                // Platba: revenue - náklady (fixní + %)
                // Automaticky vytvoř mapping pokud neexistuje
                $this->ensureBillingMapping($userId, $item['item_code'], $item['item_name']);
                
                $revenue = $item['unit_price_sale'];
                $cost = $this->billingModel->calculateCost($userId, $item['item_code'], $orderTotal);
                $profit = $revenue - $cost;
                break;
                
            case 'discount':
                // Sleva: záporný vliv
                $revenue = $item['unit_price_sale']; // Už je záporné
                $cost = 0;
                $profit = $revenue; // Záporný zisk
                break;
                
            default:
                $revenue = 0;
                $cost = 0;
                $profit = 0;
        }
        
        $item['total_revenue'] = $revenue;
        $item['total_cost'] = $cost;
        $item['total_profit'] = $profit;
        
        return $item;
    }

    /**
     * Zajistí existenci shipping mappingu
     */
    private function ensureShippingMapping(int $userId, ?string $code, string $name): void
    {
        if (empty($code)) {
            return;
        }
        
        $existing = $this->shippingModel->findByCode($userId, $code);
        
        if (!$existing) {
            // Vytvoř s výchozími hodnotami
            $this->shippingModel->upsert($userId, $code, $name, 0, true);
        }
    }

    /**
     * Zajistí existenci billing mappingu
     */
    private function ensureBillingMapping(int $userId, ?string $code, string $name): void
    {
        if (empty($code)) {
            return;
        }
        
        $existing = $this->billingModel->findByCode($userId, $code);
        
        if (!$existing) {
            // Vytvoř s výchozími hodnotami
            $this->billingModel->upsert($userId, $code, $name, 0, 0, true);
        }
    }
}
