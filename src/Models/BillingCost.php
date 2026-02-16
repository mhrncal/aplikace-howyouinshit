<?php

namespace App\Models;

use App\Core\Database;

/**
 * BillingCost Model - Mapování nákladů na platby
 */
class BillingCost
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Všechny billing costs pro uživatele
     */
    public function getAll(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM billing_costs WHERE user_id = ? ORDER BY billing_name",
            [$userId]
        );
    }

    /**
     * Najít podle kódu
     */
    public function findByCode(int $userId, string $code): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM billing_costs WHERE user_id = ? AND billing_code = ?",
            [$userId, $code]
        );
    }

    /**
     * Upsert (vytvoř nebo aktualizuj)
     */
    public function upsert(int $userId, string $code, string $name, float $costFixed = 0, float $costPercent = 0, bool $isPositive = true): int
    {
        $existing = $this->findByCode($userId, $code);
        
        $data = [
            'user_id' => $userId,
            'billing_code' => $code,
            'billing_name' => $name,
            'cost_fixed' => $costFixed,
            'cost_percent' => $costPercent,
            'is_positive' => $isPositive ? 1 : 0
        ];

        if ($existing) {
            $this->db->update('billing_costs', $data, 'id = ?', [$existing['id']]);
            return $existing['id'];
        } else {
            return $this->db->insert('billing_costs', $data);
        }
    }

    /**
     * Aktualizace
     */
    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update('billing_costs', $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Smazání
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->delete('billing_costs', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Výpočet nákladů pro billing (fixní + procento z částky)
     */
    public function calculateCost(int $userId, string $code, float $orderTotal): float
    {
        $mapping = $this->findByCode($userId, $code);
        
        if (!$mapping) {
            return 0;
        }
        
        $fixedCost = (float) $mapping['cost_fixed'];
        $percentCost = (float) $mapping['cost_percent'];
        
        return $fixedCost + ($orderTotal * $percentCost / 100);
    }

    /**
     * Je pozitivní?
     */
    public function isPositive(int $userId, string $code): bool
    {
        $mapping = $this->findByCode($userId, $code);
        return $mapping ? (bool) $mapping['is_positive'] : true;
    }
}
