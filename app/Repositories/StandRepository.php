<?php

namespace App\Repositories;

use App\Models\Stand;
use PDO;

class StandRepository extends Repository
{
    public function countActive(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM stands WHERE capacity > 0');

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return Stand[]
     */
    public function listActive(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM stands WHERE capacity > 0 AND is_active = 1 ORDER BY stand_name');
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row) => Stand::fromArray($row), $records);
    }
}
