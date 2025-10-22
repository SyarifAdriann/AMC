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

        $insertStmt = $this->pdo->prepare(
            "INSERT INTO daily_staff_roster (
                roster_date, updated_by_user_id, aerodrome_code,
                day_shift_staff_1, day_shift_staff_2, day_shift_staff_3,
                night_shift_staff_1, night_shift_staff_2, night_shift_staff_3
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $insertStmt->execute(array_merge([$date, $userId, $aerodromeCode], $params));

        return 'created';
    }
}