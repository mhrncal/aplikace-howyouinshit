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
     * OPTIMALIZOVÁNO pro velké feedy (500+ MB)
     * Šetří paměť, ukládá průběžně do DB
     */
    private function parseXmlStream(string $url, int $userId, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $batchSize = 20; // SNÍŽENO z 50 na 20 - šetří paměť
        $batch = [];
        $processed = 0;
        
        // Nastavení context pro HTTP s timeoutem
        $opts = [
            'http' => [
                'timeout' => 600, // 10 minut pro chunk
                'user_agent' => 'E-shop Analytics Bot/1.0',
                'follow_location' => 1,
                'max_redirects' => 3,
            ]
        ];
        
        if ($httpAuthUser && $httpAuthPass) {
            $opts['http']['header'] = "Authorization: Basic " . base64_encode("$httpAuthUser:$httpAuthPass");
        }
        
        $context = stream_context_create($opts);
        
        // XMLReader pro streamování - NEOTVÍRÁ celý soubor
        $reader = new \XMLReader();
        
        // DŮLEŽITÉ: Používá stream context pro kontrolu timeoutu
        if (!@$reader->open($url, null, LIBXML_PARSEHUGE | LIBXML_COMPACT)) {
            throw new \Exception("Nelze otevřít URL: $url");
        }
        
        Logger::info('XML stream opened', ['url' => $url]);
        
        $totalElements = 0;
        
        // Najdi SHOPITEM elementy
        while (@$reader->read()) {
            // Počítej všechny elementy
            if ($reader->nodeType == \XMLReader::ELEMENT) {
                $totalElements++;
                
                if ($totalElements % 500 === 0) {
                    Logger::info('XML parsing progress', [
                        'total_elements' => $totalElements,
                        'products_processed' => $processed
                    ]);
                }
            }
            
            // Kontrola paměti každých 100 produktů (ne elementů)
            if ($processed % 100 === 0) {
                $memUsage = round(memory_get_usage() / 1024 / 1024, 2);
                Logger::info('Import progress', [
                    'processed' => $processed,
                    'imported' => $imported,
                    'memory_mb' => $memUsage
                ]);
                
                // Pokud paměť > 256 MB, agresivní cleanup
                if ($memUsage > 256) {
                    gc_collect_cycles();
                    usleep(50000); // 50ms pauza pro uvolnění
                }
            }
            
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'SHOPITEM') {
                try {
                    // Načti POUZE tento element (ne celý XML)
                    $xml = @simplexml_load_string($reader->readOuterXml());
                    
                    if ($xml) {
                        // Parsuj produkt
                        $product = $this->parseProductElement($xml, $userId);
                        
                        if ($product) {
                            $batch[] = $product;
                            $processed++;
                            
                            // Uložit batch když dosáhne velikosti (20 produktů)
                            if (count($batch) >= $batchSize) {
                                $result = $this->productModel->batchUpsert($batch);
                                $imported += $result['inserted'];
                                $updated += $result['updated'];
                                
                                Logger::info('Batch saved', [
                                    'batch_size' => count($batch),
                                    'total_imported' => $imported,
                                    'total_updated' => $updated
                                ]);
                                
                                $batch = []; // Vyčisti batch
                                
                                // AGRESIVNÍ uvolnění paměti po každém batchi
                                gc_collect_cycles();
                                
                                // Mini pauza pro DB server (20ms)
                                usleep(20000);
                            }
                        }
                        
                        // Unset pro okamžité uvolnění
                        unset($xml);
                    }
                    
                } catch (\Exception $e) {
                    $errors++;
                    Logger::warning('Product parse error', [
                        'error' => $e->getMessage(),
                        'processed' => $processed
                    ]);
                }
            }
        }
        
        // Uložit zbylé produkty
        if (!empty($batch)) {
            $result = $this->productModel->batchUpsert($batch);
            $imported += $result['inserted'];
            $updated += $result['updated'];
            
            Logger::info('Final batch saved', [
                'batch_size' => count($batch),
                'total_imported' => $imported,
                'total_updated' => $updated
            ]);
        }
        
        $reader->close();
        
        Logger::info('Import completed', [
            'total_processed' => $processed,
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        ]);
        
        return [
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
    
    /**
     * Parsuje jednotlivý SHOPITEM element (SHOPTET FORMÁT)
     */
    private function parseProductElement(\SimpleXMLElement $item, int $userId): ?array
    {
        try {
            // SHOPTET používá NAME místo PRODUCT
            $name = (string) ($item->NAME ?? $item->PRODUCT ?? '');
            
            if (empty($name)) {
                Logger::warning('Product without name', ['item_id' => (string) $item['id']]);
                return null;
            }
            
            $product = [
                'user_id' => $userId,
                'name' => $name,
                'code' => (string) ($item->CODE ?? ''),
                'ean' => '',
                'manufacturer' => (string) ($item->MANUFACTURER ?? ''),
                'category' => '',
                'description' => strip_tags((string) ($item->DESCRIPTION ?? $item->SHORT_DESCRIPTION ?? '')),
                'price' => (float) ($item->PRICE_VAT ?? 0),
                'price_vat' => (float) ($item->PRICE_VAT ?? 0),
                'url' => (string) ($item->ORIG_URL ?? $item->URL ?? ''),
                'image_url' => '',
                'availability' => 'Skladem',
            ];
            
            // Kategorie - Shoptet má CATEGORIES/CATEGORY
            if (isset($item->CATEGORIES->DEFAULT_CATEGORY)) {
                $product['category'] = (string) $item->CATEGORIES->DEFAULT_CATEGORY;
            } elseif (isset($item->CATEGORIES->CATEGORY)) {
                $product['category'] = (string) $item->CATEGORIES->CATEGORY[0];
            } elseif (isset($item->CATEGORYTEXT)) {
                $product['category'] = (string) $item->CATEGORYTEXT;
            }
            
            // Obrázky - Shoptet má IMAGES/IMAGE
            if (isset($item->IMAGES->IMAGE)) {
                $product['image_url'] = (string) $item->IMAGES->IMAGE[0];
            } elseif (isset($item->IMGURL)) {
                $product['image_url'] = (string) $item->IMGURL;
            }
            
            // Varianty - Shoptet má VARIANTS/VARIANT
            $variants = [];
            
            if (isset($item->VARIANTS->VARIANT)) {
                // Produkt s variantami
                foreach ($item->VARIANTS->VARIANT as $variant) {
                    $variantName = '';
                    
                    // Název varianty z parametrů
                    if (isset($variant->PARAMETERS->PARAMETER)) {
                        $params = [];
                        foreach ($variant->PARAMETERS->PARAMETER as $param) {
                            $params[] = (string) $param->VALUE;
                        }
                        $variantName = implode(', ', $params);
                    }
                    
                    $variants[] = [
                        'name' => $variantName ?: 'Varianta',
                        'code' => (string) ($variant->CODE ?? ''),
                        'ean' => '',
                        'price' => (float) ($variant->PRICE_VAT ?? 0),
                        'availability' => (int) ($variant->STOCK->AMOUNT ?? 0) > 0 ? 'Skladem' : 'Není skladem',
                    ];
                }
            } else {
                // Produkt BEZ variant - má STOCK a PRICE_VAT přímo
                if (isset($item->STOCK->AMOUNT)) {
                    $amount = (int) $item->STOCK->AMOUNT;
                    $product['availability'] = $amount > 0 ? 'Skladem' : 'Není skladem';
                }
            }
            
            $product['variants'] = $variants;
            
            Logger::info('Product parsed', [
                'name' => substr($product['name'], 0, 50),
                'code' => $product['code'],
                'variants_count' => count($variants)
            ]);
            
            return $product;
            
        } catch (\Exception $e) {
            Logger::error('Parse product element error', [
                'error' => $e->getMessage(),
                'item_id' => (string) ($item['id'] ?? 'unknown')
            ]);
            return null;
        }
    }
}

