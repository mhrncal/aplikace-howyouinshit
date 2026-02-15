<?php

namespace App\Modules\Products\Models;

use App\Core\Database;

/**
 * Product Model - Příklad modulu
 */
class Product
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Získá všechny produkty pro uživatele
     */
    public function getAll(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $products = $this->db->fetchAll(
            "SELECT * FROM products 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE user_id = ?",
            [$userId]
        )['count'];
        
        return [
            'products' => $products,
            'pagination' => paginate($total, $perPage, $page)
        ];
    }

    /**
     * Získá produkt podle ID
     */
    public function findById(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM products WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Vytvoří nový produkt
     */
    public function create(array $data): int
    {
        return $this->db->insert('products', $data);
    }

    /**
     * Aktualizuje produkt
     */
    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update(
            'products',
            $data,
            'id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    /**
     * Smaže produkt
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->delete(
            'products',
            'id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    /**
     * Vyhledá produkty
     */
    public function search(int $userId, string $query): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM products 
             WHERE user_id = ? 
             AND (name LIKE ? OR code LIKE ? OR ean LIKE ?)
             ORDER BY name 
             LIMIT 50",
            [$userId, "%{$query}%", "%{$query}%", "%{$query}%"]
        );
    }

    /**
     * Počet produktů
     */
    public function count(int $userId): int
    {
        return (int) $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE user_id = ?",
            [$userId]
        )['count'];
    }

    /**
     * Top produkty podle ceny
     */
    public function getTopByPrice(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM products 
             WHERE user_id = ? AND standard_price > 0
             ORDER BY standard_price DESC 
             LIMIT ?",
            [$userId, $limit]
        );
    }
}
