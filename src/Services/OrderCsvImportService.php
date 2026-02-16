<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Order;
use App\Models\ShippingCost;
use App\Models\BillingCost;

/**
 * OrderCsvImportService - STREAMOVÝ import objednávek z CSV
 */
class OrderCsvImportService
{
    private $db;
    private $orderModel;
    private $shippingModel;
    private $billingModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
        $this->shippingModel = new ShippingCost();
        $this->billingModel = new BillingCost();
    }

    /**
     * Importuje objednávky z CSV URL - STREAMOVĚ
     */
    public function importFromUrl(int $userId, string $url, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        try {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            Logger::info('Starting order CSV import', ['user_id' => $userId, 'url' => $url]);
            
            // STREAMOVÉ zpracování - nestahuje celý soubor do paměti
            $result = $this->parseStreamCsv($userId, $url, $httpAuthUser, $httpAuthPass);
            
            $duration = round(microtime(true) - $startTime, 2);
            $memoryPeak = round((memory_get_peak_usage() - $startMemory) / 1024 / 1024, 2);
            
            Logger::info('Orders imported', [
                'user_id' => $userId,
                'orders' => $result['orders_imported'],
                'items' => $result['items_imported'],
                'duration' => $duration,
                'memory_mb' => $memoryPeak
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * STREAMOVÉ parsování CSV pomocí cURL
     */
    private function parseStreamCsv(int $userId, string $url, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; OrderImporter/1.0)',
        ]);
        
        // HTTP autentizace
        if ($httpAuthUser && $httpAuthPass) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$httpAuthUser:$httpAuthPass");
        }
        
        $buffer = '';
        $header = null;
        $ordersProcessed = [];
        $itemsImported = 0;
        $currentOrderData = [];
        $lineCount = 0;
        $batchSize = 50;
        
        $self = $this; // Reference na $this pro použití v closure
        
        // Write callback - zpracovává data po částech
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$buffer, &$header, &$currentOrderData, &$ordersProcessed, &$itemsImported, &$lineCount, $userId, $batchSize, $self) {
            $buffer .= $data;
            
            // Zpracuj kompletní řádky
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                
                $lineCount++;
                
                // První řádek = hlavička
                if ($header === null) {
                    $header = str_getcsv($line, ';', '"');
                    // Odstranění BOM
                    $header[0] = str_replace("\xEF\xBB\xBF", '', $header[0]);
                    continue;
                }
                
                if (empty(trim($line))) {
                    continue;
                }
                
                $row = str_getcsv($line, ';', '"');
                
                if (count($row) !== count($header)) {
                    Logger::warning('CSV line mismatch', ['line' => $lineCount, 'expected' => count($header), 'got' => count($row)]);
                    continue;
                }
                
                $rowData = array_combine($header, $row);
                $orderCode = $rowData['code'] ?? '';
                
                if (empty($orderCode)) {
                    continue;
                }
                
                // Nová objednávka?
                if (!isset($currentOrderData[$orderCode])) {
                    $currentOrderData[$orderCode] = [
                        'order' => [
                            'user_id' => $userId,
                            'order_code' => $rowData['code'],
                            'order_date' => $rowData['date'],
                            'status' => $rowData['statusName'],
                            'currency' => 'CZK',
                            'exchange_rate' => !empty($rowData['currencyExchangeRate']) ? (float) str_replace(',', '.', $rowData['currencyExchangeRate']) : 1.0,
                            'source' => $rowData['sourceName'] ?? null,
                            'customer_group' => $rowData['customerGroupName'] ?? null,
                        ],
                        'items' => []
                    ];
                }
                
                // Přidej položku
                $type = $rowData['orderItemType'];
                
                if (in_array($type, ['product', 'shipping', 'billing', 'discount'])) {
                    $unitPriceSale = (float) str_replace([' ', ','], ['', '.'], $rowData['orderItemUnitDiscountPriceWithVat'] ?? '0');
                    $unitPriceCost = (float) str_replace([' ', ','], ['', '.'], $rowData['orderItemUnitPurchasePriceWithVat'] ?? '0');
                    $amount = (int) ($rowData['orderItemAmount'] ?? 1);
                    
                    $item = [
                        'item_type' => $type,
                        'item_name' => $rowData['orderItemName'],
                        'item_code' => $rowData['orderItemCode'] ?? null,
                        'variant_name' => $rowData['orderItemVariantName'] ?? null,
                        'manufacturer' => $rowData['orderItemManufacturer'] ?? null,
                        'supplier' => $rowData['orderItemSupplier'] ?? null,
                        'amount' => $amount,
                        'unit_price_sale' => $unitPriceSale,
                        'unit_price_cost' => $unitPriceCost,
                        'total_revenue' => 0,
                        'total_cost' => 0,
                        'total_profit' => 0
                    ];
                    
                    $currentOrderData[$orderCode]['items'][] = $item;
                }
                
                // BATCH ZPRACOVÁNÍ
                if (count($currentOrderData) >= $batchSize) {
                    foreach ($currentOrderData as $orderCode => $orderDataItem) {
                        try {
                            $orderId = $self->saveOrder($userId, $orderDataItem);
                            if ($orderId) {
                                $ordersProcessed[$orderCode] = $orderId;
                                $itemsImported += count($orderDataItem['items']);
                            }
                        } catch (\Exception $e) {
                            Logger::error('Failed to save order in batch', [
                                'order_code' => $orderCode,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    $currentOrderData = [];
                }
            }
            
            return strlen($data);
        });
        
        $success = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (!$success) {
            throw new \Exception("cURL error: $error");
        }
        
        if ($httpCode >= 400) {
            throw new \Exception("HTTP error: $httpCode");
        }
        
        // Ulož zbývající data
        if (!empty($currentOrderData)) {
            foreach ($currentOrderData as $orderCode => $orderDataItem) {
                try {
                    $orderId = $this->saveOrder($userId, $orderDataItem);
                    if ($orderId) {
                        $ordersProcessed[$orderCode] = $orderId;
                        $itemsImported += count($orderDataItem['items']);
                    }
                } catch (\Exception $e) {
                    Logger::error('Failed to save order', [
                        'order_code' => $orderCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return [
            'orders_imported' => count($ordersProcessed),
            'items_imported' => $itemsImported
        ];
    }

    /**
     * Parsuje CSV řádek (respektuje uvozovky a středníky)
     */
    private function parseCsvLine(string $line): array
    {
        return str_getcsv($line, ';', '"');
    }

    /**
     * Ulož batch objednávek
     */
    private function saveBatch(int $userId, array &$orderData, array &$ordersProcessed, int &$itemsImported): void
    {
        foreach ($orderData as $orderCode => $data) {
            try {
                $orderId = $this->saveOrder($userId, $data);
                
                if ($orderId) {
                    $ordersProcessed[$orderCode] = $orderId;
                    $itemsImported += count($data['items']);
                }
            } catch (\Exception $e) {
                Logger::error('Failed to save order in batch', [
                    'order_code' => $orderCode,
                    'error' => $e->getMessage()
                ]);
            }
        }
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
