<?php

namespace App\Models;

use App\Core\Database;

/**
 * Store Model - E-shopy (jeden uživatel může mít více shopů)
 */
class Store
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Všechny shopy uživatele
     */
    public function getAllForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM stores WHERE user_id = ? ORDER BY name",
            [$userId]
        );
    }

    /**
     * Aktivní shopy uživatele
     */
    public function getActiveForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM stores WHERE user_id = ? AND is_active = 1 ORDER BY name",
            [$userId]
        );
    }

    /**
     * Najít shop podle ID
     */
    public function findById(int $id, ?int $userId = null): ?array
    {
        $sql = "SELECT * FROM stores WHERE id = ?";
        $params = [$id];
        
        if ($userId !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Najít shop podle kódu
     */
    public function findByCode(int $userId, string $code): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM stores WHERE user_id = ? AND code = ?",
            [$userId, $code]
        );
    }

    /**
     * Vytvoř nový shop
     */
    public function create(array $data): int
    {
        // Validace
        if (empty($data['name'])) {
            throw new \Exception('Název shopu je povinný');
        }
        
        if (empty($data['code'])) {
            // Automaticky vygeneruj kód z názvu
            $data['code'] = $this->generateCode($data['name']);
        }
        
        // Zkontroluj unikátnost kódu
        $existing = $this->findByCode($data['user_id'], $data['code']);
        if ($existing) {
            throw new \Exception('Shop s tímto kódem již existuje');
        }
        
        return $this->db->insert('stores', $data);
    }

    /**
     * Aktualizuj shop
     */
    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update('stores', $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Smaž shop (jen pokud nemá data)
     */
    public function delete(int $id, int $userId): bool
    {
        // Zkontroluj jestli má shop nějaká data
        $hasProducts = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE store_id = ?",
            [$id]
        )['count'] > 0;
        
        $hasOrders = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders WHERE store_id = ?",
            [$id]
        )['count'] > 0;
        
        if ($hasProducts || $hasOrders) {
            throw new \Exception('Nelze smazat shop, který má produkty nebo objednávky. Deaktivujte ho místo toho.');
        }
        
        return $this->db->delete('stores', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Aktivuj/deaktivuj shop
     */
    public function toggleActive(int $id, int $userId): bool
    {
        $shop = $this->findById($id, $userId);
        if (!$shop) {
            return false;
        }
        
        return $this->update($id, $userId, ['is_active' => !$shop['is_active']]);
    }

    /**
     * Výchozí shop pro uživatele (první aktivní)
     */
    public function getDefaultForUser(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM stores WHERE user_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1",
            [$userId]
        );
    }

    /**
     * Spočítej celkové náklady pro shop v daném měsíci
     */
    public function calculateTotalCosts(int $storeId, string $month): float
    {
        $store = $this->findById($storeId);
        if (!$store) {
            return 0;
        }
        
        $totalCosts = 0;
        
        switch ($store['cost_sharing_mode']) {
            case 'own':
                // Jen vlastní náklady
                $totalCosts = $this->getStoreCosts($storeId, $month);
                break;
                
            case 'shared':
                // Jen globální náklady (s alokací)
                $globalCosts = $this->getGlobalCosts($store['user_id'], $month);
                $totalCosts = $globalCosts * ($store['global_cost_allocation_percent'] / 100);
                break;
                
            case 'combined':
                // Vlastní + globální (s alokací)
                $ownCosts = $this->getStoreCosts($storeId, $month);
                $globalCosts = $this->getGlobalCosts($store['user_id'], $month);
                $allocatedGlobal = $globalCosts * ($store['global_cost_allocation_percent'] / 100);
                $totalCosts = $ownCosts + $allocatedGlobal;
                break;
        }
        
        return $totalCosts;
    }

    /**
     * Vlastní náklady shopu (scope = store)
     */
    private function getStoreCosts(int $storeId, string $month): float
    {
        $result = $this->db->fetchOne(
            "SELECT SUM(amount) as total 
             FROM costs 
             WHERE store_id = ? 
               AND scope = 'store'
               AND DATE_FORMAT(date, '%Y-%m') = ?",
            [$storeId, $month]
        );
        
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Globální náklady uživatele (scope = global)
     */
    private function getGlobalCosts(int $userId, string $month): float
    {
        $result = $this->db->fetchOne(
            "SELECT SUM(amount) as total 
             FROM costs 
             WHERE user_id = ? 
               AND scope = 'global'
               AND DATE_FORMAT(date, '%Y-%m') = ?",
            [$userId, $month]
        );
        
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Generuj unikátní kód z názvu
     */
    private function generateCode(string $name): string
    {
        // Převeď na lowercase, odstraň diakritiku, nahraď mezery pomlčkami
        $code = mb_strtolower($name);
        $code = iconv('UTF-8', 'ASCII//TRANSLIT', $code);
        $code = preg_replace('/[^a-z0-9]+/', '-', $code);
        $code = trim($code, '-');
        
        return substr($code, 0, 50);
    }

    /**
     * Statistiky shopu
     */
    public function getStats(int $storeId): array
    {
        $products = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE store_id = ?",
            [$storeId]
        )['count'];
        
        $orders = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders WHERE store_id = ?",
            [$storeId]
        )['count'];
        
        $costs = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM costs WHERE store_id = ? AND scope = 'store'",
            [$storeId]
        )['count'];
        
        return [
            'products' => $products,
            'orders' => $orders,
            'costs' => $costs
        ];
    }
}
