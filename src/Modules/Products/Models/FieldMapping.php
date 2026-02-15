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
        return $this->db->insert('field_mappings', array_merge($data, [
            'user_id' => $userId
        ]));
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
