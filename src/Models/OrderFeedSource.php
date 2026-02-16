<?php

namespace App\Models;

use App\Core\Database;

/**
 * OrderFeedSource Model - Zdroje CSV feedů objednávek
 */
class OrderFeedSource
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Všechny feed sources pro uživatele
     */
    public function getAll(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM order_feed_sources WHERE user_id = ? ORDER BY name",
            [$userId]
        );
    }

    /**
     * Najít podle ID
     */
    public function findById(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM order_feed_sources WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    /**
     * Vytvořit nový feed source
     */
    public function create(array $data): int
    {
        return $this->db->insert('order_feed_sources', $data);
    }

    /**
     * Aktualizace
     */
    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update('order_feed_sources', $data, 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Smazání
     */
    public function delete(int $id, int $userId): bool
    {
        return $this->db->delete('order_feed_sources', 'id = ? AND user_id = ?', [$id, $userId]) > 0;
    }

    /**
     * Update statistik po importu
     */
    public function updateStats(int $id, int $recordsImported): void
    {
        $this->db->query(
            "UPDATE order_feed_sources 
             SET last_imported_at = NOW(),
                 total_imports = total_imports + 1,
                 last_import_records = ?
             WHERE id = ?",
            [$recordsImported, $id]
        );
    }

    /**
     * Aktivní feed sources pro plánované importy
     */
    public function getActiveForSchedule(string $schedule): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM order_feed_sources 
             WHERE is_active = 1 AND schedule = ?",
            [$schedule]
        );
    }
}
