<?php

namespace App\Modules\Products\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Modules\Products\Models\Product;

/**
 * XML Import Service - Stahování a parsování XML feedů
 */
class XmlImportService
{
    private Database $db;
    private Product $productModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->productModel = new Product();
    }

    /**
     * Importuje produkty z XML URL - STREAMOVÉ ZPRACOVÁNÍ
     */
    public function importFromUrl(int $feedSourceId, int $userId, string $url, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        $logId = $this->createImportLog($userId, $feedSourceId);
        
        try {
            // Update log status
            $this->updateLogStatus($logId, 'processing');
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            // STREAMOVÉ zpracování - nestahuje celý soubor do paměti
            $result = $this->parseXmlStream($url, $userId, $httpAuthUser, $httpAuthPass);
            
            $duration = round(microtime(true) - $startTime);
            $memoryPeak = round((memory_get_peak_usage() - $startMemory) / 1024 / 1024, 2);
            
            // Update log
            $this->completeImportLog($logId, [
                'status' => 'completed',
                'total_records' => count($products),
                'processed_records' => count($products),
                'created_records' => $result['created'],
                'updated_records' => $result['updated'],
                'failed_records' => $result['failed'],
                'duration_seconds' => $duration,
                'file_size' => $fileSize,
                'memory_peak_mb' => $memoryPeak
            ]);
            
            // Update feed source stats
            $this->updateFeedSourceStats($feedSourceId, true, count($products), $duration);
            
            Logger::info('XML import completed', [
                'feed_source_id' => $feedSourceId,
                'user_id' => $userId,
                'records' => count($products),
                'created' => $result['created'],
                'updated' => $result['updated']
            ]);
            
            return [
                'success' => true,
                'total' => count($products),
                'created' => $result['created'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'duration' => $duration
            ];
            
        } catch (\Exception $e) {
            $this->failImportLog($logId, $e->getMessage());
            $this->updateFeedSourceStats($feedSourceId, false);
            
            Logger::error('XML import failed', [
                'feed_source_id' => $feedSourceId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Stáhne XML z URL
     */
    private function downloadXml(string $url, ?string $httpAuthUser, ?string $httpAuthPass): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 120,
                'user_agent' => 'E-shop Analytics/2.0',
            ]
        ]);
        
        // HTTP Auth pokud je zadáno
        if ($httpAuthUser && $httpAuthPass) {
            stream_context_set_option($context, 'http', 'header', 
                'Authorization: Basic ' . base64_encode("{$httpAuthUser}:{$httpAuthPass}")
            );
        }
        
        $xml = @file_get_contents($url, false, $context);
        
        if ($xml === false) {
            throw new \RuntimeException("Nepodařilo se stáhnout XML z URL: {$url}");
        }
        
        return $xml;
    }

    /**
     * Parsuje XML pomocí XMLReader (stream processing)
     */
    private function parseXml(string $xmlContent, int $userId): array
    {
        $products = [];
        
        $reader = new \XMLReader();
        $reader->xml($xmlContent);
        
        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'SHOPITEM') {
                $product = $this->parseShopItem($reader, $userId);
                if ($product) {
                    $products[] = $product;
                }
            }
        }
        
        $reader->close();
        
        return $products;
    }

    /**
     * Parsuje jeden SHOPITEM element (Shoptet XML formát)
     */
    private function parseShopItem(\XMLReader $reader, int $userId): ?array
    {
        $element = new \SimpleXMLElement($reader->readOuterXml());
        
        if (!isset($element->ITEM_ID)) {
            return null;
        }
        
        // Parsování variant
        $hasVariants = isset($element->VARIANTS) && count($element->VARIANTS->VARIANT) > 0;
        
        $product = [
            'user_id' => $userId,
            'external_id' => (string) $element->ITEM_ID,
            'guid' => (string) ($element->PRODUCT ?? $element->ITEM_ID),
            'code' => (string) ($element->CODE ?? ''),
            'ean' => (string) ($element->EAN ?? ''),
            'name' => (string) $element->PRODUCTNAME,
            'description' => (string) ($element->DESCRIPTION ?? ''),
            'short_description' => (string) ($element->DESCRIPTION_SHORT ?? ''),
            'category' => (string) ($element->CATEGORYTEXT ?? ''),
            'supplier' => (string) ($element->MANUFACTURER ?? ''),
            'manufacturer' => (string) ($element->MANUFACTURER ?? ''),
            'purchase_price' => $this->parsePrice($element->PRICE_VAT ?? null),
            'standard_price' => $this->parsePrice($element->PRICE_VAT ?? null),
            'action_price' => $this->parsePrice($element->PRICE_VAT ?? null), // TODO: Rozlišit akční cenu
            'vat_rate' => 21.00,
            'stock' => (int) ($element->STOCK_QUANTITY ?? 0),
            'availability_status' => (string) ($element->DELIVERY_DATE ?? ''),
            'weight' => $this->parseFloat($element->WEIGHT ?? null),
            'is_sale' => false,
            'is_new' => false,
            'is_top' => false,
            'has_variants' => $hasVariants,
            'images' => $this->parseImages($element),
            'parameters' => $this->parseParameters($element),
            'raw_data' => json_encode([
                'url' => (string) ($element->URL ?? ''),
                'imgurl' => (string) ($element->IMGURL ?? ''),
                'manufacturer' => (string) ($element->MANUFACTURER ?? '')
            ])
        ];
        
        return $product;
    }

    /**
     * Parsuje cenu z XML
     */
    private function parsePrice($priceNode): ?float
    {
        if (!$priceNode) {
            return null;
        }
        
        $price = (string) $priceNode;
        $price = str_replace([' ', ','], ['', '.'], $price);
        
        return $price !== '' ? (float) $price : null;
    }

    /**
     * Parsuje float hodnotu
     */
    private function parseFloat($node): ?float
    {
        if (!$node) {
            return null;
        }
        
        $value = (string) $node;
        $value = str_replace(',', '.', $value);
        
        return $value !== '' ? (float) $value : null;
    }

    /**
     * Parsuje obrázky z XML
     */
    private function parseImages($element): ?string
    {
        $images = [];
        
        if (isset($element->IMGURL)) {
            $images[] = (string) $element->IMGURL;
        }
        
        if (isset($element->IMAGES)) {
            foreach ($element->IMAGES->IMAGE as $img) {
                $images[] = (string) $img;
            }
        }
        
        return !empty($images) ? json_encode($images) : null;
    }

    /**
     * Parsuje parametry produktu
     */
    private function parseParameters($element): ?string
    {
        $parameters = [];
        
        if (isset($element->PARAMS)) {
            foreach ($element->PARAMS->PARAM as $param) {
                $parameters[(string) $param->PARAM_NAME] = (string) $param->VAL;
            }
        }
        
        return !empty($parameters) ? json_encode($parameters) : null;
    }

    /**
     * Vytvoří import log
     */
    private function createImportLog(int $userId, int $feedSourceId): int
    {
        return $this->db->insert('import_logs', [
            'user_id' => $userId,
            'feed_source_id' => $feedSourceId,
            'type' => 'products',
            'status' => 'pending',
            'started_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update log status
     */
    private function updateLogStatus(int $logId, string $status): void
    {
        $this->db->update('import_logs', ['status' => $status], 'id = ?', [$logId]);
    }

    /**
     * Dokončí import log
     */
    private function completeImportLog(int $logId, array $data): void
    {
        $data['completed_at'] = date('Y-m-d H:i:s');
        $this->db->update('import_logs', $data, 'id = ?', [$logId]);
    }

    /**
     * Označí import jako failed
     */
    private function failImportLog(int $logId, string $error): void
    {
        $this->db->update('import_logs', [
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$logId]);
    }

    /**
     * Update feed source statistiky
     */
    private function updateFeedSourceStats(int $feedSourceId, bool $success, int $records = 0, int $duration = 0): void
    {
        if ($success) {
            $this->db->query(
                "UPDATE feed_sources 
                 SET last_imported_at = NOW(), 
                     total_imports = total_imports + 1,
                     last_import_records = ?,
                     last_import_duration = ?
                 WHERE id = ?",
                [$records, $duration, $feedSourceId]
            );
        } else {
            $this->db->query(
                "UPDATE feed_sources 
                 SET failed_imports = failed_imports + 1
                 WHERE id = ?",
                [$feedSourceId]
            );
        }
    }
    
    /**
     * STREAMOVÉ parsování XML - zpracovává po jednotlivých produktech
     * Šetří paměť, ukládá průběžně do DB
     */
    private function parseXmlStream(string $url, int $userId, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $batchSize = 50; // Ukládá po 50 produktech
        $batch = [];
        
        // Nastavení context pro HTTP
        $opts = [
            'http' => [
                'timeout' => 300,
                'user_agent' => 'E-shop Analytics Bot/1.0',
            ]
        ];
        
        if ($httpAuthUser && $httpAuthPass) {
            $opts['http']['header'] = "Authorization: Basic " . base64_encode("$httpAuthUser:$httpAuthPass");
        }
        
        $context = stream_context_create($opts);
        
        // Otevři stream
        $stream = @fopen($url, 'r', false, $context);
        if (!$stream) {
            throw new \Exception("Nelze otevřít URL: $url");
        }
        
        // XMLReader pro streamování
        $reader = new \XMLReader();
        $reader->open($url, null, LIBXML_PARSEHUGE);
        
        // Najdi SHOPITEM elementy
        while ($reader->read()) {
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'SHOPITEM') {
                // Načti celý SHOPITEM element
                $xml = simplexml_load_string($reader->readOuterXml());
                
                if ($xml) {
                    try {
                        // Parsuj produkt
                        $product = $this->parseProductElement($xml, $userId);
                        
                        if ($product) {
                            $batch[] = $product;
                            
                            // Uložit batch když dosáhne velikosti
                            if (count($batch) >= $batchSize) {
                                $result = $this->productModel->batchUpsert($batch);
                                $imported += $result['inserted'];
                                $updated += $result['updated'];
                                $batch = []; // Vyčisti batch
                                
                                // Uvolni paměť
                                gc_collect_cycles();
                            }
                        }
                    } catch (\Exception $e) {
                        $errors++;
                        Logger::warning('Product parse error', ['error' => $e->getMessage()]);
                    }
                }
            }
        }
        
        // Uložit zbylé produkty
        if (!empty($batch)) {
            $result = $this->productModel->batchUpsert($batch);
            $imported += $result['inserted'];
            $updated += $result['updated'];
        }
        
        $reader->close();
        fclose($stream);
        
        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
    
    /**
     * Parsuje jednotlivý SHOPITEM element
     */
    private function parseProductElement(\SimpleXMLElement $item, int $userId): ?array
    {
        try {
            $product = [
                'user_id' => $userId,
                'name' => (string) $item->PRODUCT,
                'code' => (string) ($item->CODE ?? ''),
                'ean' => (string) ($item->EAN ?? ''),
                'manufacturer' => (string) ($item->MANUFACTURER ?? ''),
                'category' => (string) ($item->CATEGORYTEXT ?? ''),
                'description' => (string) ($item->DESCRIPTION ?? ''),
                'price' => (float) ($item->PRICE_VAT ?? 0),
                'price_vat' => (float) ($item->PRICE_VAT ?? 0),
                'url' => (string) ($item->URL ?? ''),
                'image_url' => (string) ($item->IMGURL ?? ''),
                'availability' => (string) ($item->DELIVERY_DATE ?? 'Skladem'),
            ];
            
            // Varianty
            $variants = [];
            if (isset($item->VARIANTS)) {
                foreach ($item->VARIANTS->VARIANT as $variant) {
                    $variants[] = [
                        'name' => (string) ($variant->VARIANT_NAME ?? ''),
                        'code' => (string) ($variant->CODE ?? ''),
                        'ean' => (string) ($variant->EAN ?? ''),
                        'price' => (float) ($variant->PRICE_VAT ?? 0),
                        'availability' => (string) ($variant->DELIVERY_DATE ?? 'Skladem'),
                    ];
                }
            }
            
            $product['variants'] = $variants;
            
            return $product;
            
        } catch (\Exception $e) {
            Logger::error('Parse product element error', ['error' => $e->getMessage()]);
            return null;
        }


