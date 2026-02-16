<?php

namespace App\Modules\Products\Config;

/**
 * FIXNÍ XML FIELD MAPPING
 * 
 * Pevně definované mapování XML elementů na databázové sloupce.
 * Uživatel NEMÁ možnost editace - vše je fixní.
 */
class XmlFieldMapping
{
    /**
     * FIXNÍ MAPOVÁNÍ PRO PRODUKTY
     */
    public static function getProductMapping(): array
    {
        return [
            'name' => [
                'xml_path' => 'NAME',
                'required' => true,
            ],
            
            'code' => [
                'xml_path' => 'CODE',
                'default' => '',
            ],
            
            'manufacturer' => [
                'xml_path' => 'MANUFACTURER',
                'default' => '',
            ],
            
            'supplier' => [
                'xml_path' => 'SUPPLIER',
                'default' => '',
            ],
            
            'category' => [
                'xml_path' => 'CATEGORIES/DEFAULT_CATEGORY',
                'default' => '',
            ],
            
            'external_id' => [
                'xml_path' => '@id',
                'attribute' => true,
            ],
            
            'guid' => [
                'xml_path' => 'GUID',
                'default' => '',
            ],
        ];
    }
    
    /**
     * FIXNÍ MAPOVÁNÍ PRO VARIANTY
     */
    public static function getVariantMapping(): array
    {
        return [
            'name' => [
                'xml_path' => 'PARAMETERS/PARAMETER/VALUE',
                'transform' => function($params) {
                    return is_array($params) ? implode(', ', $params) : (string)$params;
                },
                'default' => 'Varianta',
            ],
            
            'code' => [
                'xml_path' => 'CODE',
                'default' => '',
            ],
            
            'external_id' => [
                'xml_path' => '@id',
                'attribute' => true,
            ],
        ];
    }

    /**
     * Pomocná metoda - získání hodnoty z XML
     */
    public static function getXmlValue($xmlElement, array $config)
    {
        if (empty($config['xml_path'])) {
            return $config['default'] ?? null;
        }

        // Atribut
        if (!empty($config['attribute'])) {
            $attrName = ltrim($config['xml_path'], '@');
            return (string) $xmlElement[$attrName] ?? $config['default'] ?? '';
        }

        // Parsování XPath
        $path = explode('/', $config['xml_path']);
        $current = $xmlElement;
        
        foreach ($path as $step) {
            if (!isset($current->$step)) {
                return $config['default'] ?? null;
            }
            $current = $current->$step;
        }

        // Pokud je to kolekce, vrať pole hodnot
        if ($current->count() > 1) {
            $values = [];
            foreach ($current as $item) {
                $values[] = (string) $item;
            }
            
            // Aplikuj transformaci pokud existuje
            if (isset($config['transform']) && is_callable($config['transform'])) {
                return $config['transform']($values);
            }
            
            return $values;
        }

        $value = (string) $current;
        
        // Transformace
        if (isset($config['transform']) && is_callable($config['transform'])) {
            return $config['transform']($value);
        }

        return $value ?: ($config['default'] ?? null);
    }
}
