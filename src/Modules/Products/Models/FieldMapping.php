<?php

namespace App\Modules\Products\Models;

use App\Core\Database;

class FieldMapping
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Získá všechny aktivní mappingy pro uživatele
     */
    public function getAllForUser(int $userId, ?int $feedSourceId = null, string $fieldType = 'product'): array
    {
        $sql = "SELECT * FROM field_mappings 
                WHERE user_id = ? 
                AND field_type = ?
                AND is_active = 1
                AND (feed_source_id IS NULL OR feed_source_id = ?)
                ORDER BY is_required DESC, db_column ASC";
        
        return $this->db->fetchAll($sql, [$userId, $fieldType, $feedSourceId]);
    }
    
    /**
     * Vytvoří nový mapping
     */
    public function create(int $userId, array $data): int|false
    {
        $mappingData = [
            'user_id' => $userId,
            'db_column' => $data['db_column'],
            'xml_path' => $data['xml_path'],
            'data_type' => $data['data_type'] ?? 'string',
            'default_value' => $data['default_value'] ?? null,
            'field_type' => $data['field_type'] ?? 'product',
            'target_type' => $data['target_type'] ?? 'column', // NEW: column nebo json
            'transformer' => $data['transformer'] ?? null, // NEW: strip_tags, strtoupper, atd.
            'is_searchable' => $data['is_searchable'] ?? false, // NEW: vytvořit virtual column
            'is_active' => $data['is_active'] ?? 1,
        ];
        
        // Pokud je target_type = json, nastav json_path
        if ($mappingData['target_type'] === 'json') {
            $mappingData['json_path'] = '$.' . $data['db_column'];
        }
        
        return $this->db->insert('field_mappings', $mappingData);
    }
    
    /**
     * Aktualizuje mapping
     */
    public function update(int $id, int $userId, array $data): bool
    {
        $this->db->update('field_mappings', $data, 'id = ? AND user_id = ?', [$id, $userId]);
        return true;
    }
    
    /**
     * Aplikuje transformer funkci na hodnotu
     */
    public function applyTransformer(?string $transformer, $value)
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
            'intval' => (int) $value,
            'floatval' => (float) $value,
            'boolval' => (bool) $value,
            'json_encode' => json_encode($value),
            'serialize' => serialize($value),
            'md5' => md5($value),
            'urlencode' => urlencode($value),
            'base64_encode' => base64_encode($value),
            default => $value,
        };
    }
    
    /**
     * Připraví data pro uložení podle mappingů
     * Rozdělí na column data a custom_data (JSON)
     */
    public function prepareDataForSave(array $rawData, array $mappings): array
    {
        $columnData = [];
        $customData = [];
        
        foreach ($mappings as $mapping) {
            if (!$mapping['is_active']) {
                continue;
            }
            
            $value = $rawData[$mapping['xml_path']] ?? $mapping['default_value'] ?? null;
            
            // Aplikuj transformer
            $value = $this->applyTransformer($mapping['transformer'], $value);
            
            // Kde uložit?
            if ($mapping['target_type'] === 'json') {
                // Do JSON custom_data
                $customData[$mapping['db_column']] = $value;
            } else {
                // Do standardního sloupce
                $columnData[$mapping['db_column']] = $value;
            }
        }
        
        return [
            'columns' => $columnData,
            'custom_data' => $customData,
        ];
    }
    
    /**
     * Smaže mapping
     */
    public function delete(int $id, int $userId): bool
    {
        $this->db->query(
            "DELETE FROM field_mappings WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
        return true;
    }
    
    /**
     * Najde podle ID
     */
    public function findById(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM field_mappings WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }
    
    /**
     * Převede DB mappingy na formát pro XmlFieldMapping
     */
    public function toConfigFormat(array $mappings): array
    {
        $config = [];
        
        foreach ($mappings as $mapping) {
            $field = [
                'xml_path' => $mapping['xml_path'],
                'default' => $mapping['default_value'] ?? '',
            ];
            
            // Alternativní cesty
            if (!empty($mapping['xml_path_alt'])) {
                $field['xml_path_alt'] = $mapping['xml_path_alt'];
            }
            
            if (!empty($mapping['xml_path_alt2'])) {
                $field['xml_path_alt2'] = $mapping['xml_path_alt2'];
            }
            
            // Transformace
            if ($mapping['transform_type'] !== 'none') {
                switch ($mapping['transform_type']) {
                    case 'floatval':
                        $field['transform'] = 'floatval';
                        break;
                    case 'intval':
                        $field['transform'] = 'intval';
                        break;
                    case 'strip_tags':
                        $field['transform'] = 'strip_tags';
                        break;
                    case 'boolean':
                        $field['transform'] = fn($v) => (int)$v === 1;
                        break;
                    case 'custom':
                        // Vlastní transformace z DB (POZOR: bezpečnostní riziko!)
                        if (!empty($mapping['transform_custom'])) {
                            // Pro jednoduchost: availability
                            if ($mapping['db_column'] === 'availability') {
                                $field['transform'] = fn($v) => (int)$v > 0 ? 'Skladem' : 'Není skladem';
                            }
                        }
                        break;
                }
            }
            
            // Povinné pole
            if ($mapping['is_required']) {
                $field['required'] = true;
            }
            
            $config[$mapping['db_column']] = $field;
        }
        
        return $config;
    }
    
    /**
     * Získá dostupné sloupce z products tabulky
     */
    public function getAvailableColumns(): array
    {
        $columns = $this->db->fetchAll("DESCRIBE products");
        $available = [];
        
        foreach ($columns as $col) {
            $name = $col['Field'];
            
            // Přeskočit systémové sloupce
            if (in_array($name, ['id', 'user_id', 'created_at', 'updated_at', 'variants'])) {
                continue;
            }
            
            $available[] = [
                'name' => $name,
                'type' => $col['Type'],
                'null' => $col['Null'] === 'YES',
                'default' => $col['Default'],
            ];
        }
        
        return $available;
    }
}
