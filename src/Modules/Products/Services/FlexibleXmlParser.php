<?php

namespace App\Modules\Products\Services;

use App\Modules\Products\Models\FieldMapping;
use App\Core\Logger;

/**
 * Flexibilní XML parser s podporou custom JSON polí
 */
class FlexibleXmlParser
{
    private FieldMapping $mappingModel;
    
    public function __construct()
    {
        $this->mappingModel = new FieldMapping();
    }
    
    /**
     * Parsuje SHOPITEM element s flexibilním mappingem
     */
    public function parseProduct(\SimpleXMLElement $item, int $userId): ?array
    {
        try {
            // 1. HARDCODED VÝCHOZÍ MAPPINGY (vždy platí)
            $defaultMappings = $this->getDefaultMappings('product');
            
            // 2. CUSTOM MAPPINGY z DB (uživatel přidal)
            $customMappings = $this->mappingModel->getAllForUser($userId, null, 'product');
            
            // 3. KOMBINUJ (custom mají přednost)
            $allMappings = array_merge($defaultMappings, $customMappings);
            
            // Extrahuj všechna data z XML
            $rawData = $this->extractXmlData($item);
            
            // SHOPTET: Uložit ID z atributu jako external_id pro párování
            if (isset($rawData['@id'])) {
                $rawData['EXTERNAL_ID'] = $rawData['@id'];
            }
            
            // SHOPTET: Pokud CODE chybí na produktu, ale existuje na variantě, použij celý CODE varianty
            if (empty($rawData['CODE']) && isset($item->VARIANTS->VARIANT[0]->CODE)) {
                $rawData['CODE'] = (string) $item->VARIANTS->VARIANT[0]->CODE;
            }
            
            // Rozdel na column data a custom_data podle mappingů
            $prepared = $this->prepareData($rawData, $allMappings, $userId);
            
            // Kontrola povinných polí
            if (empty($prepared['name'])) {
                Logger::warning('Product without name', ['item_id' => (string) ($item['id'] ?? 'unknown')]);
                return null;
            }
            
            // Přidej raw XML pro backup
            $prepared['raw_xml'] = $item->asXML();
            $prepared['imported_at'] = date('Y-m-d H:i:s');
            
            // Parsuj varianty
            $variants = [];
            if (isset($item->VARIANTS->VARIANT)) {
                $variantMappings = array_merge(
                    $this->getDefaultMappings('variant'),
                    $this->mappingModel->getAllForUser($userId, null, 'variant')
                );
                
                foreach ($item->VARIANTS->VARIANT as $variantXml) {
                    $variant = $this->parseVariant($variantXml, $variantMappings, $userId);
                    if ($variant) {
                        $variants[] = $variant;
                    }
                }
            }
            
            $prepared['variants'] = $variants;
            
            Logger::info('Product parsed (flexible)', [
                'name' => substr($prepared['name'], 0, 50),
                'code' => $prepared['code'] ?? 'N/A',
                'custom_fields' => count(json_decode($prepared['custom_data'] ?? '{}', true)),
                'variants' => count($variants)
            ]);
            
            return $prepared;
            
        } catch (\Exception $e) {
            Logger::error('Parse product failed', [
                'error' => $e->getMessage(),
                'item_id' => (string) ($item['id'] ?? 'unknown')
            ]);
            return null;
        }
    }
    
    /**
     * Parsuje variantu
     */
    private function parseVariant(\SimpleXMLElement $variantXml, array $mappings, int $userId): ?array
    {
        $rawData = $this->extractXmlData($variantXml);
        $prepared = $this->prepareData($rawData, $mappings, $userId);
        
        // Speciální handling pro název varianty z parametrů
        if (empty($prepared['name']) && isset($variantXml->PARAMETERS->PARAMETER)) {
            $params = [];
            foreach ($variantXml->PARAMETERS->PARAMETER as $param) {
                $params[] = (string) $param->VALUE;
            }
            $prepared['name'] = implode(', ', $params);
        }
        
        $prepared['raw_xml'] = $variantXml->asXML();
        
        return $prepared;
    }
    
    /**
     * Extrahuje všechna data z XML do flat array
     */
    private function extractXmlData(\SimpleXMLElement $xml): array
    {
        $data = [];
        
        // ATRIBUTY SHOPITEM (např. id="20929")
        foreach ($xml->attributes() as $attrName => $attrValue) {
            $data['@' . $attrName] = (string) $attrValue;
        }
        
        // Jednoduché elementy
        foreach ($xml->children() as $name => $value) {
            $data[$name] = (string) $value;
        }
        
        // Vnořené - speciální případy
        if (isset($xml->CATEGORIES->DEFAULT_CATEGORY)) {
            $data['CATEGORY'] = (string) $xml->CATEGORIES->DEFAULT_CATEGORY;
        }
        
        if (isset($xml->IMAGES->IMAGE)) {
            $data['IMAGE'] = (string) $xml->IMAGES->IMAGE[0];
        }
        
        if (isset($xml->STOCK->AMOUNT)) {
            $data['STOCK_AMOUNT'] = (int) $xml->STOCK->AMOUNT;
        }
        
        if (isset($xml->LOGISTIC->WEIGHT)) {
            $data['WEIGHT'] = (float) $xml->LOGISTIC->WEIGHT;
        }
        
        // ORIG_URL (kompletní URL produktu)
        if (isset($xml->ORIG_URL)) {
            $data['URL'] = (string) $xml->ORIG_URL;
        }
        
        return $data;
    }
    
    /**
     * Připraví data podle mappingů
     * Rozdělí na standardní sloupce a custom_data JSON
     */
    private function prepareData(array $rawData, array $mappings, int $userId): array
    {
        $columnData = ['user_id' => $userId];
        $customData = [];
        
        foreach ($mappings as $mapping) {
            if (!$mapping['is_active']) {
                continue;
            }
            
            // Získej hodnotu z raw data
            $value = $rawData[$mapping['xml_path']] ?? $mapping['default_value'] ?? null;
            
            // Aplikuj transformer
            $value = $this->applyTransformer($mapping['transformer'] ?? null, $value);
            
            // Konverze typu
            $value = $this->convertDataType($value, $mapping['data_type']);
            
            // Kam uložit?
            if ($mapping['target_type'] === 'json') {
                $customData[$mapping['db_column']] = $value;
            } else {
                $columnData[$mapping['db_column']] = $value;
            }
        }
        
        // Přidej custom_data jako JSON string
        if (!empty($customData)) {
            $columnData['custom_data'] = json_encode($customData, JSON_UNESCAPED_UNICODE);
        }
        
        // AUTOMATICKÝ VÝPOČET CENY BEZ DPH
        // Pro PRODUKTY: price_vat → price (bez DPH)
        if (isset($columnData['price_vat']) && !empty($columnData['price_vat'])) {
            $priceWithVat = (float) $columnData['price_vat'];
            $vatRate = isset($columnData['vat_rate']) ? (float) $columnData['vat_rate'] : 21.0;
            
            // price = price_vat / (1 + vat_rate/100)
            $columnData['price'] = round($priceWithVat / (1 + $vatRate / 100), 2);
        }
        
        // Pro VARIANTY: price z XML je S DPH, uložit jako price_with_vat
        // V Shoptet Marketing feedu je PRICE na variantě S DPH!
        // Takže varianty mají naopak: price (s DPH) → potřebuju price_without_vat
        // ALE product_variants tabulka má jen "price" sloupec
        // Takže: PRICE z XML (4350 s DPH) → uložit jako price BEZ DPH
        if (isset($columnData['price']) && !empty($columnData['price']) && !isset($columnData['price_vat'])) {
            // Toto je varianta
            $priceWithVat = (float) $columnData['price'];
            $vatRate = 21.0; // Varianty nemají vat_rate, použij default
            
            // price = price_xml / (1 + 21/100) = cena bez DPH
            $columnData['price'] = round($priceWithVat / (1 + $vatRate / 100), 2);
        }
        
        return $columnData;
    }
    
    /**
     * Aplikuje transformer funkci
     */
    private function applyTransformer(?string $transformer, $value)
    {
        if (!$transformer || $value === null) {
            return $value;
        }
        
        return match($transformer) {
            'strip_tags' => strip_tags($value),
            'trim' => trim($value),
            'strtoupper' => strtoupper($value),
            'strtolower' => strtolower($value),
            'ucfirst' => ucfirst($value),
            'ucwords' => ucwords($value),
            default => $value,
        };
    }
    
    /**
     * Konvertuje hodnotu na správný datový typ
     */
    private function convertDataType($value, string $type)
    {
        if ($value === null) {
            return null;
        }
        
        return match($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }
    
    /**
     * Výchozí mappingy pro Shoptet
     */
    private function getDefaultMappings(string $fieldType): array
    {
        if ($fieldType === 'product') {
            return [
                ['db_column' => 'external_id', 'xml_path' => 'EXTERNAL_ID', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'name', 'xml_path' => 'NAME', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'code', 'xml_path' => 'CODE', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'manufacturer', 'xml_path' => 'MANUFACTURER', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'price_vat', 'xml_path' => 'PRICE_VAT', 'data_type' => 'float', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'category', 'xml_path' => 'CATEGORY', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'url', 'xml_path' => 'ORIG_URL', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'image_url', 'xml_path' => 'IMAGE', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'description', 'xml_path' => 'DESCRIPTION', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1, 'transformer' => 'strip_tags'],
            ];
        } else {
            return [
                ['db_column' => 'code', 'xml_path' => 'CODE', 'data_type' => 'string', 'target_type' => 'column', 'is_active' => 1],
                ['db_column' => 'price', 'xml_path' => 'PRICE', 'data_type' => 'float', 'target_type' => 'column', 'is_active' => 1],
            ];
        }
    }
}
