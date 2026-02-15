<?php

namespace App\Modules\Products\Config;

/**
 * XML FIELD MAPPING CONFIG
 * 
 * Konfigurace mapování XML elementů na databázové sloupce
 * Když přidáš nový sloupec do products tabulky, stačí ho přidat sem!
 */
class XmlFieldMapping
{
    /**
     * HLAVNÍ MAPOVÁNÍ - Shoptet XML na DB sloupce
     * 
     * Formát:
     * 'db_column' => [
     *     'xml_path' => 'XPATH/K/ELEMENTU',  // První možnost
     *     'xml_path_alt' => 'ALTERNATIVNI',  // Záložní možnost (volitelné)
     *     'transform' => 'callable',         // Transformace (volitelné)
     *     'default' => 'hodnota'             // Výchozí hodnota (volitelné)
     * ]
     */
    public static function getProductMapping(): array
    {
        return [
            // Základní pole
            'name' => [
                'xml_path' => 'NAME',
                'xml_path_alt' => 'PRODUCT',
                'required' => true,
            ],
            
            'code' => [
                'xml_path' => 'CODE',
                'default' => '',
            ],
            
            'ean' => [
                'xml_path' => 'EAN',
                'default' => '',
            ],
            
            'manufacturer' => [
                'xml_path' => 'MANUFACTURER',
                'default' => '',
            ],
            
            'category' => [
                'xml_path' => 'CATEGORIES/DEFAULT_CATEGORY',
                'xml_path_alt' => 'CATEGORIES/CATEGORY[0]',
                'xml_path_alt2' => 'CATEGORYTEXT',
                'default' => '',
            ],
            
            'description' => [
                'xml_path' => 'DESCRIPTION',
                'xml_path_alt' => 'SHORT_DESCRIPTION',
                'transform' => 'strip_tags',
                'default' => '',
            ],
            
            'price' => [
                'xml_path' => 'PRICE_VAT',
                'transform' => 'floatval',
                'default' => 0,
            ],
            
            'price_vat' => [
                'xml_path' => 'PRICE_VAT',
                'transform' => 'floatval',
                'default' => 0,
            ],
            
            'url' => [
                'xml_path' => 'ORIG_URL',
                'xml_path_alt' => 'URL',
                'default' => '',
            ],
            
            'image_url' => [
                'xml_path' => 'IMAGES/IMAGE[0]',
                'xml_path_alt' => 'IMGURL',
                'default' => '',
            ],
            
            'availability' => [
                'xml_path' => 'STOCK/AMOUNT',
                'transform' => function($value) {
                    return (int)$value > 0 ? 'Skladem' : 'Není skladem';
                },
                'default' => 'Skladem',
            ],
            
            // === PŘÍKLADY DALŠÍCH POLÍ ===
            // Odkomentuj a přidej sloupec do DB když potřebuješ
            
            /*
            'warranty' => [
                'xml_path' => 'WARRANTY',
                'default' => '24 měsíců',
            ],
            
            'weight' => [
                'xml_path' => 'LOGISTIC/WEIGHT',
                'transform' => 'floatval',
                'default' => 0,
            ],
            
            'vat_rate' => [
                'xml_path' => 'VAT',
                'transform' => 'intval',
                'default' => 21,
            ],
            
            'brand' => [
                'xml_path' => 'MANUFACTURER',
                'default' => '',
            ],
            
            'stock_amount' => [
                'xml_path' => 'STOCK/AMOUNT',
                'transform' => 'intval',
                'default' => 0,
            ],
            
            'min_stock' => [
                'xml_path' => 'STOCK/MINIMAL_AMOUNT',
                'transform' => 'intval',
                'default' => 0,
            ],
            
            'is_active' => [
                'xml_path' => 'VISIBLE',
                'transform' => function($value) {
                    return (int)$value === 1;
                },
                'default' => true,
            ],
            
            'short_description' => [
                'xml_path' => 'SHORT_DESCRIPTION',
                'transform' => 'strip_tags',
                'default' => '',
            ],
            
            'meta_title' => [
                'xml_path' => 'NAME',
                'default' => '',
            ],
            
            'meta_description' => [
                'xml_path' => 'SHORT_DESCRIPTION',
                'transform' => 'strip_tags',
                'default' => '',
            ],
            */
        ];
    }
    
    /**
     * MAPOVÁNÍ PRO VARIANTY
     */
    public static function getVariantMapping(): array
    {
        return [
            'name' => [
                'xml_path' => 'PARAMETERS/PARAMETER/VALUE',
                'transform' => function($params) {
                    // Spojí všechny parametry čárkou
                    return is_array($params) ? implode(', ', $params) : (string)$params;
                },
                'default' => 'Varianta',
            ],
            
            'code' => [
                'xml_path' => 'CODE',
                'default' => '',
            ],
            
            'ean' => [
                'xml_path' => 'EAN',
                'default' => '',
            ],
            
            'standard_price' => [
                'xml_path' => 'PRICE',  // Ve variantách je PRICE, ne PRICE_VAT
                'transform' => 'floatval',
                'default' => 0,
            ],
            
            'stock' => [
                'xml_path' => 'STOCK/WAREHOUSES/WAREHOUSE/VALUE',  // Sečte hodnoty ze všech skladů
                'transform' => function($values) {
                    if (is_array($values)) {
                        return array_sum(array_map('intval', $values));
                    }
                    return intval($values);
                },
                'default' => 0,
            ],
            
            'availability_status' => [
                'xml_path' => 'STOCK/WAREHOUSES/WAREHOUSE/VALUE',
                'transform' => function($values) {
                    $total = 0;
                    if (is_array($values)) {
                        $total = array_sum(array_map('intval', $values));
                    } else {
                        $total = intval($values);
                    }
                    return $total > 0 ? 'Skladem' : 'Vyprodáno';
                },
                'default' => 'Vyprodáno',
            ],
            
            // === PŘÍKLADY DALŠÍCH POLÍ PRO VARIANTY ===
            /*
            'purchase_price' => [
                'xml_path' => 'PRICE_PURCHASE',
                'transform' => 'floatval',
                'default' => 0,
            ],
            
            'weight' => [
                'xml_path' => 'LOGISTIC/WEIGHT',
                'transform' => 'floatval',
                'default' => 0,
            ],
            */
        ];
    }
    
    /**
     * HELPER - Získá hodnotu z XML podle cesty
     */
    public static function getXmlValue(\SimpleXMLElement $xml, array $config): mixed
    {
        // Zkus hlavní cestu
        $value = self::getByPath($xml, $config['xml_path']);
        
        // Zkus alternativy
        if (empty($value) && isset($config['xml_path_alt'])) {
            $value = self::getByPath($xml, $config['xml_path_alt']);
        }
        
        if (empty($value) && isset($config['xml_path_alt2'])) {
            $value = self::getByPath($xml, $config['xml_path_alt2']);
        }
        
        // Transformace
        if (!empty($value) && isset($config['transform'])) {
            if (is_callable($config['transform'])) {
                $value = $config['transform']($value);
            } elseif (function_exists($config['transform'])) {
                $value = $config['transform']($value);
            }
        }
        
        // Default hodnota
        if (empty($value) && isset($config['default'])) {
            $value = $config['default'];
        }
        
        return $value;
    }
    
    /**
     * HELPER - Získá hodnotu podle XPath
     */
    private static function getByPath(\SimpleXMLElement $xml, string $path): mixed
    {
        $parts = explode('/', $path);
        $current = $xml;
        
        foreach ($parts as $part) {
            // Podpora pro array index [0]
            if (preg_match('/(.+)\[(\d+)\]/', $part, $matches)) {
                $element = $matches[1];
                $index = (int)$matches[2];
                
                if (isset($current->$element) && isset($current->$element[$index])) {
                    $current = $current->$element[$index];
                } else {
                    return null;
                }
            } else {
                if (isset($current->$part)) {
                    $current = $current->$part;
                } else {
                    return null;
                }
            }
        }
        
        return (string)$current;
    }
}
