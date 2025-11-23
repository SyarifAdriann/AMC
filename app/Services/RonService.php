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

        // Combined update: mark as RON and format time in single query
        $updateStmt = $this->pdo->prepare(
            "UPDATE aircraft_movements
             SET is_ron = 1,
                 on_block_time = CASE
                     WHEN on_block_time NOT LIKE '%)%'
                     THEN CONCAT(on_block_time, ' (', DATE_FORMAT(movement_date, '%d/%m/%Y'), ')')
                     ELSE on_block_time
                 END
             WHERE (off_block_time IS NULL OR off_block_time = '')
               AND on_block_time IS NOT NULL
               AND on_block_time != ''
               AND movement_date < :today
               AND (is_ron = 0 OR is_ron IS NULL)"
        );
        $updateStmt->execute([':today' => $today]);
        $updated = (int) $updateStmt->rowCount();

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
            $cleanTime = $this->normalizeRonTime($onBlockTime);

            if (strpos($onBlockTime, '(') === false) {
                $timePart = $cleanTime !== '' ? $cleanTime : trim((string) $onBlockTime);
                $formattedTime = ($timePart !== '' ? $timePart : $onBlockTime) . ' (' . $formatted . ')';
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

    private function normalizeRonTime(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{3,4}$/', $value)) {
            $value = str_pad($value, 4, '0', STR_PAD_LEFT);
            return substr($value, 0, 2) . ':' . substr($value, 2, 2);
        }

        return $value;
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
