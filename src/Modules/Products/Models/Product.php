<?php

namespace App\Modules\Products\Models;

use App\Core\Database;

/**
 * Product Model
 */
class Product
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Získá všechny produkty pro uživatele (s podporou Super Admina)
     */
    public function getAll(?int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        if ($userId === null || $userId === 0) {
            // Super Admin - vidí vše
            $products = $this->db->fetchAll(
                "SELECT p.*, u.name as user_name, u.email as user_email 
                 FROM products p
                 LEFT JOIN users u ON p.user_id = u.id
                 ORDER BY p.created_at DESC 
                 LIMIT ? OFFSET ?",
                [$perPage, $offset]
            );
            
            $total = $this->db->fetchOne("SELECT COUNT(*) as count FROM products")['count'];
        } else {
            // Běžný uživatel - jen své produkty
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
        }
        
        return [
            'products' => $products,
            'pagination' => paginate($total, $perPage, $page)
        ];
    }

    /**
     * Získá produkt podle ID
     */
    public function findById(int $id, ?int $userId): ?array
    {
        if ($userId === null || $userId === 0) {
            // Super Admin
            return $this->db->fetchOne(
                "SELECT p.*, u.name as user_name FROM products p 
                 LEFT JOIN users u ON p.user_id = u.id 
                 WHERE p.id = ?",
                [$id]
            );
        } else {
            return $this->db->fetchOne(
                "SELECT * FROM products WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );
        }
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

    /**
     * Získá varianty produktu
     */
    public function getVariants(int $productId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM product_variants WHERE product_id = ? ORDER BY name",
            [$productId]
        );
    }

    /**
     * Všechny produkty pro export
     */
    public function getAllForExport(?int $userId): array
    {
        if ($userId === null || $userId === 0) {
            return $this->db->fetchAll(
                "SELECT * FROM products ORDER BY created_at DESC"
            );
        } else {
            return $this->db->fetchAll(
                "SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC",
                [$userId]
            );
        }
    }

    /**
     * Upsert produktu (insert nebo update podle GUID)
     */
    public function upsert(array $data): int
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM products WHERE user_id = ? AND guid = ?",
            [$data['user_id'], $data['guid']]
        );

        if ($existing) {
            // Update
            $this->db->update(
                'products',
                $data,
                'id = ?',
                [$existing['id']]
            );
            return $existing['id'];
        } else {
            // Insert
            return $this->db->insert('products', $data);
        }
    }

    /**
     * Batch upsert produktů (pro import)
     */
    public function batchUpsert(array $products): array
    {
        $inserted = 0;
        $updated = 0;
        $failed = 0;

        if (empty($products)) {
            return [
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0
            ];
        }

        $this->db->beginTransaction();

        try {
            foreach ($products as $productData) {
                // Sanitizuj data - odstraň variants před uložením
                $variants = $productData['variants'] ?? [];
                unset($productData['variants']);
                
                // Najdi existující produkt podle external_id (SHOPITEM id) nebo code
                $existing = null;
                
                // 1. Preferuj external_id (stabilní ID z feedu)
                if (!empty($productData['external_id'])) {
                    $existing = $this->db->fetchOne(
                        "SELECT id FROM products WHERE user_id = ? AND external_id = ? LIMIT 1",
                        [$productData['user_id'], $productData['external_id']]
                    );
                }
                
                // 2. Fallback na code pokud external_id není
                if (!$existing && !empty($productData['code'])) {
                    $existing = $this->db->fetchOne(
                        "SELECT id FROM products WHERE user_id = ? AND code = ? LIMIT 1",
                        [$productData['user_id'], $productData['code']]
                    );
                }

                if ($existing) {
                    // Update existujícího
                    $productData['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->update('products', $productData, 'id = ?', [$existing['id']]);
                    $productId = $existing['id'];
                    $updated++;
                } else {
                    // Insert nového
                    $productData['created_at'] = date('Y-m-d H:i:s');
                    $productData['updated_at'] = date('Y-m-d H:i:s');
                    $productId = $this->db->insert('products', $productData);
                    $inserted++;
                }
                
                // Ulož varianty pokud existují
                if (!empty($variants) && $productId) {
                    // Smaž staré varianty
                    $this->db->query("DELETE FROM product_variants WHERE product_id = ?", [$productId]);
                    
                    // Vlož nové
                    foreach ($variants as $variant) {
                        $variant['product_id'] = $productId;
                        $variant['created_at'] = date('Y-m-d H:i:s');
                        
                        // Odstraň price_vat - product_variants má jen price
                        unset($variant['price_vat']);
                        
                        $this->db->insert('product_variants', $variant);
                    }
                }
            }

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            $failed = count($products);
            
            \App\Core\Logger::error('Batch upsert failed', [
                'error' => $e->getMessage(),
                'products_count' => count($products)
            ]);
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'failed' => $failed
        ];
    }
}
