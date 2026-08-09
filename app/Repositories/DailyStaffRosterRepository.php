<?php

namespace App\Repositories;

use App\Models\DailyStaffRoster;
use PDO;

class DailyStaffRosterRepository extends Repository
{
    /**
     * @return DailyStaffRoster[]
     */
    public function findByDate(string $date, ?string $aerodromeCode = null): array
    {
        if ($aerodromeCode !== null) {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM daily_staff_roster WHERE roster_date = ? AND aerodrome_code = ? ORDER BY shift"
            );
            $stmt->execute([$date, $aerodromeCode]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM daily_staff_roster WHERE roster_date = ? ORDER BY shift"
            );
            $stmt->execute([$date]);
        }

        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row) => DailyStaffRoster::fromArray($row), $records);
    }

    public function upsertRoster(string $date, string $aerodromeCode, array $payload, int $userId): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM daily_staff_roster WHERE roster_date = ? AND aerodrome_code = ? LIMIT 1'
        );
        $stmt->execute([$date, $aerodromeCode]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        $params = [
            $payload['day_shift_staff_1'] ?? '',
            $payload['day_shift_staff_2'] ?? '',
            $payload['day_shift_staff_3'] ?? '',
            $payload['night_shift_staff_1'] ?? '',
            $payload['night_shift_staff_2'] ?? '',
            $payload['night_shift_staff_3'] ?? '',
        ];

        if ($existing) {
            $update = $this->pdo->prepare(
                "UPDATE daily_staff_roster SET
                    day_shift_staff_1 = ?, day_shift_staff_2 = ?, day_shift_staff_3 = ?,
                    night_shift_staff_1 = ?, night_shift_staff_2 = ?, night_shift_staff_3 = ?,
                    updated_by_user_id = ?, updated_at = NOW()
                 WHERE roster_date = ? AND aerodrome_code = ?"
            );
            $update->execute(array_merge($params, [$userId, $date, $aerodromeCode]));

            return 'updated';
        }

        // 'shift' is NOT NULL with no default in the shipped schema, but this
        // table stores day AND night staffing in one row per date, so the
        // column is otherwise unused — nothing in this repository filters or
        // writes it per-shift. A constant value satisfies the constraint
        // without pretending the value means anything.
        //
        // Local dev's MariaDB has a relaxed sql_mode and silently accepted the
        // missing column; MariaDB 10.4's default STRICT_TRANS_TABLES does not,
        // so every INSERT failed in production with "Field 'shift' doesn't
        // have a default value" while working locally.
        $insertStmt = $this->pdo->prepare(
            "INSERT INTO daily_staff_roster (
                roster_date, shift, updated_by_user_id, aerodrome_code,
                day_shift_staff_1, day_shift_staff_2, day_shift_staff_3,
                night_shift_staff_1, night_shift_staff_2, night_shift_staff_3
            ) VALUES (?, 'ALL', ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertStmt->execute(array_merge([$date, $userId, $aerodromeCode], $params));

        return 'created';
    }
}