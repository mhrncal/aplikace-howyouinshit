<?php

namespace App\Modules\FeedSources\Models;

use App\Core\Database;

class FeedSource
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(int $userId, int $page = 1): array
    {
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        $feedSources = $this->db->fetchAll(
            "SELECT * FROM feed_sources 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM feed_sources WHERE user_id = ?",
            [$userId]
        )['count'];
        
        return [
            'feed_sources' => $feedSources,
            'pagination' => paginate($total, $perPage, $page)
        ];
    }

    public function findById(int $id, int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM feed_sources WHERE id = ? AND user_id = ?",
            [$id, $userId]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert('feed_sources', $data);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        return $this->db->update(
            'feed_sources',
            $data,
            'id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->db->delete(
            'feed_sources',
            'id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }

    public function toggleActive(int $id, int $userId): bool
    {
        $feedSource = $this->findById($id, $userId);
        if (!$feedSource) {
            return false;
        }

        $newStatus = !$feedSource['is_active'];
        
        return $this->db->update(
            'feed_sources',
            ['is_active' => $newStatus],
            'id = ? AND user_id = ?',
            [$id, $userId]
        ) > 0;
    }
}
