<?php

namespace App\Models;

use App\Core\Database;

/**
 * ShippingCost Model - Mapování nákladů na dopravu
 */
class ShippingCost
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Všechny shipping costs pro uživatele
     */
    public function getAll(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM shipping_costs WHERE user_id = ? ORDER BY shipping_name",
            [$userId]
        );
    }

    /**
     * Najít podle kódu
     */
    public function findByCode(int $userId, string $code): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM shipping_costs WHERE user_id = ? AND shipping_code = ?",
            [$userId, $code]
        );
    }

    /**
     * Upsert (vytvoř nebo aktualizuj)
     */
    public function upsert(int $userId, string $code, string $name, float $cost = 0, bool $isPositive = true): int
    {
        $existing = $this->findByCode($userId, $code);
        
        $data = [
            'user_id' => $userId,
            'shipping_code' => $code,
            'shipping_name' => $name,
            'cost' => $cost,
            'is_positive' => $isPositive ? 1 : 0
        ];

        if ($existing) {
            $this->db->update('shipping_costs', $data, 'id = ?', [$existing['id']]);
            return $existing['id'];
        } else {
            return $this->db->insert('shipping_costs', $data);
        }
    }

    /**
     * Aktualizace
     */
    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update('shipping_costs', $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Smazání
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->delete('shipping_costs', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Získání nákladů pro shipping kód
     */
    public function getCost(int $userId, string $code): float
    {
        $mapping = $this->findByCode($userId, $code);
        return $mapping ? (float) $mapping['cost'] : 0;
    }

    /**
     * Je posititivní?
     */
    public function isPositive(int $userId, string $code): bool
    {
        $mapping = $this->findByCode($userId, $code);
        return $mapping ? (bool) $mapping['is_positive'] : true;
    }
}
