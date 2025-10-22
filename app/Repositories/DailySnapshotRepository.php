<?php

namespace App\Repositories;

use App\Models\DailySnapshot;
use PDO;

class DailySnapshotRepository extends Repository
{
    public function upsert(string $date, int $userId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO daily_snapshots (snapshot_date, snapshot_data, created_by_user_id)
             VALUES (:date, :data, :user)
             ON DUPLICATE KEY UPDATE
                snapshot_data = VALUES(snapshot_data),
                created_by_user_id = VALUES(created_by_user_id)"
        );

        $stmt->execute([
            ':date' => $date,
            ':data' => json_encode($data),
            ':user' => $userId,
        ]);
    }

    public function existsForDate(string $date): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM daily_snapshots WHERE snapshot_date = ?');
        $stmt->execute([$date]);

        return (bool) $stmt->fetchColumn();
    }

    public function paginate(int $page, int $perPage): array
    {
        $total = $this->countAll();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            "SELECT ds.*, u.username AS created_by_username
             FROM daily_snapshots ds
             LEFT JOIN users u ON ds.created_by_user_id = u.id
             ORDER BY ds.snapshot_date DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $snapshots = array_map(fn(array $row) => $this->mapSnapshot($row), $records);

        return [
            'data' => $snapshots,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function findById(int $id): ?DailySnapshot
    {
        $stmt = $this->pdo->prepare(
            "SELECT ds.*, u.username AS created_by_username
             FROM daily_snapshots ds
             LEFT JOIN users u ON ds.created_by_user_id = u.id
             WHERE ds.id = ?"
        );
        $stmt->execute([$id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ? $this->mapSnapshot($record) : null;
    }

    public function deleteById(int $id): ?DailySnapshot
    {
        $snapshot = $this->findById($id);

        if (!$snapshot) {
            return null;
        }

        $stmt = $this->pdo->prepare('DELETE FROM daily_snapshots WHERE id = ?');
        $stmt->execute([$id]);

        return $snapshot;
    }

    protected function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM daily_snapshots')->fetchColumn();
    }

    protected function mapSnapshot(array $row): DailySnapshot
    {
        if (isset($row['snapshot_data']) && is_string($row['snapshot_data'])) {
            $decoded = json_decode($row['snapshot_data'], true);
            if (is_array($decoded)) {
                $row['snapshot_data'] = $decoded;
            }
        }

        return DailySnapshot::fromArray($row);
    }
}
