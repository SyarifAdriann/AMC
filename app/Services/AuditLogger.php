<?php

namespace App\Services;

use PDO;

class AuditLogger
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(
        int $userId,
        string $actionType,
        string $targetTable,
        ?int $targetId = null,
        ?array $newValues = null,
        ?array $oldValues = null
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action_type, target_table, target_id, old_values, new_values) VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId,
            $actionType,
            $targetTable,
            $targetId ?? 0,
            $oldValues !== null ? json_encode($oldValues) : null,
            $newValues !== null ? json_encode($newValues) : '',
        ]);
    }
}
