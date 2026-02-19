<?php

namespace App\Traits;

/**
 * StoreAware Trait
 * Přidá podporu pro store_id do modelů
 */
trait StoreAware
{
    /**
     * Přidá store_id podmínku do WHERE
     * 
     * @param array $where Pole WHERE podmínek
     * @param array $params Pole parametrů
     * @param int|null $storeId ID shopu (pokud null, použije se aktuální)
     * @return void
     */
    protected function addStoreCondition(array &$where, array &$params, ?int $storeId = null): void
    {
        if ($storeId === null) {
            $storeId = currentStoreId();
        }
        
        if ($storeId !== null) {
            $where[] = "store_id = ?";
            $params[] = $storeId;
        }
    }
    
    /**
     * Přidá store_id do dat před vložením
     * 
     * @param array $data Data k vložení
     * @param int|null $storeId ID shopu (pokud null, použije se aktuální)
     * @return array Data s přidaným store_id
     */
    protected function addStoreId(array $data, ?int $storeId = null): array
    {
        if ($storeId === null) {
            $storeId = currentStoreId();
        }
        
        if ($storeId !== null && !isset($data['store_id'])) {
            $data['store_id'] = $storeId;
        }
        
        return $data;
    }
    
    /**
     * Vytvoří WHERE clause s store_id
     * 
     * @param int $userId User ID
     * @param int|null $storeId Store ID (pokud null, použije se aktuální)
     * @return array ['where' => string, 'params' => array]
     */
    protected function buildStoreWhere(int $userId, ?int $storeId = null): array
    {
        $where = ["user_id = ?"];
        $params = [$userId];
        
        $this->addStoreCondition($where, $params, $storeId);
        
        return [
            'where' => implode(' AND ', $where),
            'params' => $params
        ];
    }
}
