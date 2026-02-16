<?php

namespace App\Models;

use App\Core\Database;

/**
 * Order Model - Objednávky a analytika výnosů
 */
class Order
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Všechny objednávky
     */
    public function getAll(int $userId, int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        $where = ["user_id = ?"];
        $params = [$userId];
        
        // Filtry
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "order_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "order_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['source'])) {
            $where[] = "source = ?";
            $params[] = $filters['source'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $orders = $this->db->fetchAll(
            "SELECT * FROM orders 
             WHERE {$whereClause}
             ORDER BY order_date DESC 
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders WHERE {$whereClause}",
            $params
        )['count'];
        
        return [
            'orders' => $orders,
            'pagination' => paginate($total, $perPage, $page)
        ];
    }

    /**
     * Detail objednávky s položkami
     */
    public function findByIdWithItems(int $orderId, int $userId): ?array
    {
        $order = $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = ? AND user_id = ?",
            [$orderId, $userId]
        );
        
        if (!$order) {
            return null;
        }
        
        $order['items'] = $this->db->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? ORDER BY item_type, id",
            [$orderId]
        );
        
        return $order;
    }

    /**
     * Upsert objednávky
     */
    public function upsert(array $data): int
    {
        $existing = $this->db->fetchOne(
            "SELECT id FROM orders WHERE user_id = ? AND order_code = ?",
            [$data['user_id'], $data['order_code']]
        );

        if ($existing) {
            $this->db->update('orders', $data, 'id = ?', [$existing['id']]);
            return $existing['id'];
        } else {
            return $this->db->insert('orders', $data);
        }
    }

    /**
     * Přidání položky objednávky
     */
    public function addItem(int $orderId, array $itemData): int
    {
        $itemData['order_id'] = $orderId;
        return $this->db->insert('order_items', $itemData);
    }

    /**
     * Přepočet součtů objednávky
     */
    public function recalculateTotals(int $orderId): void
    {
        $totals = $this->db->fetchOne(
            "SELECT 
                SUM(total_revenue) as revenue,
                SUM(total_cost) as cost,
                SUM(total_profit) as profit
             FROM order_items 
             WHERE order_id = ?",
            [$orderId]
        );
        
        $revenue = (float) ($totals['revenue'] ?? 0);
        $cost = (float) ($totals['cost'] ?? 0);
        $profit = (float) ($totals['profit'] ?? 0);
        $margin = $revenue > 0 ? ($profit / $revenue * 100) : 0;
        
        $this->db->update('orders', [
            'total_revenue' => $revenue,
            'total_cost' => $cost,
            'total_profit' => $profit,
            'margin_percent' => $margin
        ], 'id = ?', [$orderId]);
    }

    /**
     * Analytika - celkové přehledy
     */
    public function getAnalytics(int $userId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = ["user_id = ?"];
        $params = [$userId];
        
        if ($dateFrom) {
            $where[] = "order_date >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $where[] = "order_date <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Celkové součty
        $totals = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as order_count,
                SUM(total_revenue) as total_revenue,
                SUM(total_cost) as total_cost,
                SUM(total_profit) as total_profit,
                AVG(margin_percent) as avg_margin
             FROM orders 
             WHERE {$whereClause} AND status != 'Stornována'",
            $params
        );
        
        // Podle statusu
        $byStatus = $this->db->fetchAll(
            "SELECT status, 
                    COUNT(*) as count,
                    SUM(total_revenue) as revenue,
                    SUM(total_profit) as profit
             FROM orders 
             WHERE {$whereClause}
             GROUP BY status",
            $params
        );
        
        // Podle zdroje
        $bySource = $this->db->fetchAll(
            "SELECT source, 
                    COUNT(*) as count,
                    SUM(total_revenue) as revenue,
                    SUM(total_profit) as profit
             FROM orders 
             WHERE {$whereClause} AND status != 'Stornována'
             GROUP BY source
             ORDER BY profit DESC",
            $params
        );
        
        return [
            'totals' => $totals,
            'by_status' => $byStatus,
            'by_source' => $bySource
        ];
    }

    /**
     * TOP produkty podle zisku
     */
    public function getTopProducts(int $userId, int $limit = 10, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = ["o.user_id = ?", "oi.item_type = 'product'"];
        $params = [$userId];
        
        if ($dateFrom) {
            $where[] = "o.order_date >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $where[] = "o.order_date <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $whereClause = implode(' AND ', $where);
        
        return $this->db->fetchAll(
            "SELECT 
                oi.item_name,
                oi.item_code,
                oi.manufacturer,
                COUNT(*) as sold_count,
                SUM(oi.amount) as total_amount,
                SUM(oi.total_revenue) as total_revenue,
                SUM(oi.total_cost) as total_cost,
                SUM(oi.total_profit) as total_profit,
                AVG((oi.total_profit / NULLIF(oi.total_revenue, 0)) * 100) as avg_margin
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE {$whereClause} AND o.status != 'Stornována'
             GROUP BY oi.item_code, oi.item_name, oi.manufacturer
             ORDER BY total_profit DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Měsíční trendy
     */
    public function getMonthlyTrends(int $userId, int $year): array
    {
        return $this->db->fetchAll(
            "SELECT 
                MONTH(order_date) as month,
                COUNT(*) as order_count,
                SUM(total_revenue) as revenue,
                SUM(total_cost) as cost,
                SUM(total_profit) as profit,
                AVG(margin_percent) as avg_margin
             FROM orders 
             WHERE user_id = ? 
               AND YEAR(order_date) = ?
               AND status != 'Stornována'
             GROUP BY MONTH(order_date)
             ORDER BY month",
            [$userId, $year]
        );
    }
}
