<?php

namespace App\Modules\Products\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\LogManager;
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
    
    private int $feedSourceId = 0;
    private int $userId = 0;
    
    /**
     * Helper pro logování importu (používá kategorii 'import')
     */
    private function logImport(string $level, string $message, array $context = []): void
    {
        // Přidej user_id a feed_source_id pro LogManager
        if ($this->userId > 0) {
            $context['user_id'] = $this->userId;
        }
        if ($this->feedSourceId > 0) {
            $context['feed_source_id'] = $this->feedSourceId;
        }
        
        $method = strtolower($level);
        Logger::$method($message, $context, 'import');
    }

    /**
     * Importuje produkty z XML URL - STREAMOVÉ ZPRACOVÁNÍ
     */
    public function importFromUrl(int $feedSourceId, int $userId, string $url, ?string $httpAuthUser = null, ?string $httpAuthPass = null): array
    {
        // Ulož pro logging
        $this->feedSourceId = $feedSourceId;
        $this->userId = $userId;
        
        // KONTROLA MAPPINGŮ - pokud neexistují, vytvoř výchozí
        $this->ensureDefaultMappings($feedSourceId, $userId);
        
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
                'total_records' => $result['imported'] + $result['updated'],
                'processed_records' => $result['imported'] + $result['updated'],
                'created_records' => $result['imported'],
                'updated_records' => $result['updated'],
                'failed_records' => $result['errors'],
                'duration_seconds' => $duration,
                'file_size' => 0, // Neznáme při streamování
                'memory_peak_mb' => $memoryPeak
            ]);
            
            // Update feed source stats
            $totalRecords = $result['imported'] + $result['updated'];
            $this->updateFeedSourceStats($feedSourceId, true, $totalRecords, $duration);
            
            Logger::info('XML import completed', [
                'feed_source_id' => $feedSourceId,
                'user_id' => $userId,
                'records' => $totalRecords,
                'imported' => $result['imported'],
                'updated' => $result['updated']
            ]);
            
            return [
                'success' => true,
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'errors' => $result['errors'] ?? 0,
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
     * Zajistí že existují výchozí mappingy (při prvním importu)
     */
    private function ensureDefaultMappings(int $feedSourceId, int $userId): void
    {
        $mappingModel = new \App\Modules\Products\Models\FieldMapping();
        $existing = $mappingModel->getAllForUser($userId, $feedSourceId, 'product');
        
        if (!empty($existing)) {
            $this->logImport('info', 'Mappings already exist', ['count' => count($existing)]);
            return;
        }
        
        $this->logImport('info', 'Creating default mappings (first import)', []);
        
        $defaultMappings = [
            ['db_column' => 'name', 'xml_path' => 'NAME', 'data_type' => 'string', 'target_type' => 'column', 'is_required' => 1],
            ['db_column' => 'code', 'xml_path' => 'CODE', 'data_type' => 'string', 'target_type' => 'column', 'is_required' => 1],
            ['db_column' => 'price_vat', 'xml_path' => 'PRICE_VAT', 'data_type' => 'float', 'target_type' => 'column', 'is_required' => 1],
            ['db_column' => 'category', 'xml_path' => 'CATEGORY', 'data_type' => 'string', 'target_type' => 'column'],
            ['db_column' => 'manufacturer', 'xml_path' => 'MANUFACTURER', 'data_type' => 'string', 'target_type' => 'column'],
            ['db_column' => 'url', 'xml_path' => 'ORIG_URL', 'data_type' => 'string', 'target_type' => 'column'],
            ['db_column' => 'image_url', 'xml_path' => 'IMAGE', 'data_type' => 'string', 'target_type' => 'column'],
            ['db_column' => 'description', 'xml_path' => 'DESCRIPTION', 'data_type' => 'string', 'target_type' => 'column', 'transformer' => 'strip_tags'],
            ['db_column' => 'ean', 'xml_path' => 'EAN', 'data_type' => 'string', 'target_type' => 'column'],
        ];
        
        $created = 0;
        foreach ($defaultMappings as $mapping) {
            $data = array_merge($mapping, [
                'user_id' => $userId,
                'feed_source_id' => $feedSourceId,
                'field_type' => 'product',
                'is_active' => 1,
            ]);
            
            try {
                if ($mappingModel->create($userId, $data)) {
                    $created++;
                }
            } catch (\Exception $e) {
                $this->logImport('warning', 'Failed to create mapping', [
                    'db_column' => $mapping['db_column'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logImport('info', 'Default mappings created', ['count' => $created]);
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
        
        // Vytvoř FlexibleXmlParser pro flexibilní parsing
        $flexibleParser = new \App\Modules\Products\Services\FlexibleXmlParser();
        
        // STÁHNOUT XML přes CURL do dočasného souboru (XMLReader neumí otevírat URL přímo)
        $tempFile = tempnam(sys_get_temp_dir(), 'xml_import_');
        
        Logger::info('Downloading XML via CURL', ['url' => $url]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Pro HTTPS
        curl_setopt($ch, CURLOPT_USERAGENT, 'E-shop Analytics Bot/1.0');
        
        if ($httpAuthUser && $httpAuthPass) {
            curl_setopt($ch, CURLOPT_USERPWD, "$httpAuthUser:$httpAuthPass");
        }
        
        // Stáhnout do souboru
        $fp = fopen($tempFile, 'w+');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        fclose($fp);
        
        if ($result === false || $httpCode !== 200) {
            unlink($tempFile);
            throw new \Exception("Chyba stahování: HTTP {$httpCode}, {$error}");
        }
        
        $fileSize = filesize($tempFile);
        Logger::info('XML downloaded', [
            'size_mb' => round($fileSize / 1024 / 1024, 2),
            'temp_file' => $tempFile
        ]);
        
        // XMLReader pro streamování ze souboru
        $reader = new \XMLReader();
        
        if (!@$reader->open($tempFile, null, LIBXML_PARSEHUGE | LIBXML_COMPACT)) {
            unlink($tempFile);
            throw new \Exception("Nelze otevřít dočasný XML soubor");
        }
        
        Logger::info('XML stream opened from temp file');
        
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
            if ($processed % 100 === 0 && $processed > 0) {
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
                        // Parsuj produkt - POUŽIJ FLEXIBLE PARSER
                        $product = $flexibleParser->parseProduct($xml, $userId);
                        
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
        
        // Smaž dočasný soubor
        unlink($tempFile);
        
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
     * POUŽÍVÁ DB MAPPINGY POKUD EXISTUJÍ, JINAK FALLBACK NA CONFIG!
     */
    private function parseProductElement(\SimpleXMLElement $item, int $userId): ?array
    {
        try {
            // Načti mapping z DB (cache by se hodila)
            $mappingModel = new \App\Modules\Products\Models\FieldMapping();
            $dbMappings = $mappingModel->getAllForUser($userId, null, 'product');
            
            // Pokud existují DB mappingy, použij je
            if (!empty($dbMappings)) {
                $mapping = $mappingModel->toConfigFormat($dbMappings);
            } else {
                // Fallback na statickou konfiguraci
                $mapping = \App\Modules\Products\Config\XmlFieldMapping::getProductMapping();
            }
            
            $product = ['user_id' => $userId];
            
            // Automatické mapování podle konfigurace
            foreach ($mapping as $dbColumn => $config) {
                $product[$dbColumn] = \App\Modules\Products\Config\XmlFieldMapping::getXmlValue($item, $config);
            }
            
            // Kontrola povinných polí
            if (empty($product['name'])) {
                Logger::warning('Product without name', ['item_id' => (string) $item['id']]);
                return null;
            }
            
            // Varianty - TAKÉ s field mappingem
            $variants = [];
            
            if (isset($item->VARIANTS->VARIANT)) {
                // Načti variant mapping z DB
                $dbVariantMappings = $mappingModel->getAllForUser($userId, null, 'variant');
                
                if (!empty($dbVariantMappings)) {
                    $variantMapping = $mappingModel->toConfigFormat($dbVariantMappings);
                } else {
                    $variantMapping = \App\Modules\Products\Config\XmlFieldMapping::getVariantMapping();
                }
                
                foreach ($item->VARIANTS->VARIANT as $variant) {
                    $variantData = [];
                    
                    foreach ($variantMapping as $dbColumn => $config) {
                        $variantData[$dbColumn] = \App\Modules\Products\Config\XmlFieldMapping::getXmlValue($variant, $config);
                    }
                    
                    // Speciální handling pro název varianty z parametrů
                    if (empty($variantData['name']) && isset($variant->PARAMETERS->PARAMETER)) {
                        $params = [];
                        foreach ($variant->PARAMETERS->PARAMETER as $param) {
                            $params[] = (string) $param->VALUE;
                        }
                        $variantData['name'] = implode(', ', $params);
                    }
                    
                    $variants[] = $variantData;
                }
            }
            
            $product['variants'] = $variants;
            
            Logger::info('Product parsed', [
                'name' => substr($product['name'], 0, 50),
                'code' => $product['code'],
                'variants_count' => count($variants),
                'used_db_mappings' => !empty($dbMappings)
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

