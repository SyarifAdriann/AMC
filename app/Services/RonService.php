<?php

namespace App\Services;

use PDO;
use App\Services\ApronStatusService;

class RonService
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function carryOverActiveRon(): int
    {
        $today = date('Y-m-d');

        $updateStmt = $this->pdo->prepare(
            "UPDATE aircraft_movements
             SET is_ron = 1
             WHERE (off_block_time IS NULL OR off_block_time = '')
               AND on_block_time IS NOT NULL
               AND on_block_time != ''
               AND movement_date < :today
               AND (is_ron = 0 OR is_ron IS NULL)"
        );
        $updateStmt->execute([':today' => $today]);
        $updated = (int) $updateStmt->rowCount();

        $formatStmt = $this->pdo->prepare(
            "UPDATE aircraft_movements
             SET on_block_time = CONCAT(on_block_time, ' (', DATE_FORMAT(movement_date, '%d/%m/%Y'), ')')
             WHERE is_ron = 1
               AND on_block_time NOT LIKE '%)%'
               AND on_block_time IS NOT NULL
               AND on_block_time != ''"
        );
        $formatStmt->execute();

        return $updated;
    }

    public function setRonForOpenMovements(int $userId, ?string $date = null): int
    {
        $this->carryOverActiveRon();

        $today = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        $formatted = date('d/m/Y', strtotime($today));

        $stmt = $this->pdo->prepare(
            "SELECT id, on_block_time
             FROM aircraft_movements
             WHERE (off_block_time IS NULL OR off_block_time = '')
               AND on_block_time IS NOT NULL
               AND is_ron = 0"
        );
        $stmt->execute();
        $movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updatedCount = 0;

        foreach ($movements as $movement) {
            $onBlockTime = $movement['on_block_time'];
            $id = (int) $movement['id'];

            if (strpos($onBlockTime, '(') === false) {
                $formattedTime = $onBlockTime . ' (' . $formatted . ')';
                $update = $this->pdo->prepare(
                    "UPDATE aircraft_movements
                     SET is_ron = 1,
                         on_block_time = :formatted_time,
                         user_id_updated = :uid
                     WHERE id = :id"
                );
                $update->execute([
                    ':formatted_time' => $formattedTime,
                    ':uid' => $userId,
                    ':id' => $id,
                ]);
            } else {
                $update = $this->pdo->prepare(
                    "UPDATE aircraft_movements
                     SET is_ron = 1,
                         user_id_updated = :uid
                     WHERE id = :id"
                );
                $update->execute([
                    ':uid' => $userId,
                    ':id' => $id,
                ]);
            }

            $updatedCount++;
        }

        return $updatedCount;
    }

    public function markCompletion(int $movementId, ?string $offBlockTime): void
    {
        if (empty($offBlockTime)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE aircraft_movements
             SET ron_complete = 1
             WHERE id = :id AND is_ron = 1"
        );
        $stmt->execute([':id' => $movementId]);
    }
}
