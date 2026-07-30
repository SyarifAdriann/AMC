<?php

namespace App\Repositories;

use App\Models\AircraftMovement;
use InvalidArgumentException;
use PDO;
use Throwable;

class AircraftMovementRepository extends Repository
{
    /**
     * @return AircraftMovement[]
     */
    public function findByDateWithDetails(string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT am.*, ad.category, ad.operator_airline AS aircraft_operator
             FROM aircraft_movements am
             LEFT JOIN aircraft_details ad ON am.registration = ad.registration
             WHERE am.movement_date = ?
             ORDER BY am.on_block_time, am.off_block_time"
        );
        $stmt->execute([$date]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row) => AircraftMovement::fromArray($row), $records);
    }

    /**
     * @return AircraftMovement[]
     */
    public function findRonByDate(string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT am.*, ad.category
             FROM aircraft_movements am
             LEFT JOIN aircraft_details ad ON am.registration = ad.registration
             WHERE am.movement_date = ? AND am.is_ron = 1
             ORDER BY am.parking_stand"
        );
        $stmt->execute([$date]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row) => AircraftMovement::fromArray($row), $records);
    }

    public function countArrivalsAndDepartures(string $date): array
    {
        // Arrivals: counted on on_block_date (COALESCE fallback for legacy records without on_block_date)
        // Departures: counted on off_block_date so RON departures appear on the correct day
        // Note: capacity column does not exist on stands table; use status = 'active' instead
        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(CASE WHEN COALESCE(am.on_block_date, am.movement_date) = ?
                         AND am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON'
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS total_arrivals,
                SUM(CASE WHEN am.off_block_date = ?
                         AND am.off_block_time IS NOT NULL AND am.off_block_time != ''
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS total_departures
             FROM aircraft_movements am
             WHERE (COALESCE(am.on_block_date, am.movement_date) = ? OR am.off_block_date = ?)"
        );
        $stmt->execute([$date, $date, $date, $date]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_arrivals' => (int) ($totals['total_arrivals'] ?? 0),
            'total_departures' => (int) ($totals['total_departures'] ?? 0),
        ];
    }

    public function countNewRon(string $date): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM aircraft_movements WHERE movement_date = ? AND is_ron = 1');
        $stmt->execute([$date]);

        return (int) $stmt->fetchColumn();
    }

    public function countActiveRon(): int
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM aircraft_movements WHERE is_ron = 1 AND ron_complete = 0');

        return (int) $stmt->fetchColumn();
    }

    public function hourlyBreakdown(string $date): array
    {
        // UNION approach: arrivals and departures are bucketed by their OWN timestamp on their OWN date.
        // SUBSTRING_INDEX extracts the hour part safely even when off_block_time contains a date
        // string in parentheses (e.g. "06:00 (17/05/2026)").
        $stmt = $this->pdo->prepare(
            "SELECT
                CONCAT(LPAD(FLOOR(hour_val/2)*2,2,'0'), ':00-',
                       LPAD(FLOOR(hour_val/2)*2+1,2,'0'), ':59') AS time_range,
                SUM(is_arrival) AS Arrivals,
                SUM(is_departure) AS Departures
             FROM (
                 SELECT
                     CAST(SUBSTRING_INDEX(on_block_time, ':', 1) AS UNSIGNED) AS hour_val,
                     1 AS is_arrival, 0 AS is_departure
                 FROM aircraft_movements
                 WHERE COALESCE(on_block_date, movement_date) = ?
                   AND on_block_time IS NOT NULL AND on_block_time != '' AND on_block_time != 'EX RON'
                   AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                 UNION ALL
                 SELECT
                     CAST(SUBSTRING_INDEX(off_block_time, ':', 1) AS UNSIGNED) AS hour_val,
                     0 AS is_arrival, 1 AS is_departure
                 FROM aircraft_movements
                 WHERE off_block_date = ?
                   AND off_block_time IS NOT NULL AND off_block_time != ''
                   AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
             ) combined
             GROUP BY FLOOR(hour_val/2)
             ORDER BY time_range"
        );
        $stmt->execute([$date, $date]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function categoryBreakdown(string $date): array
    {
        // Arrivals counted on on_block_date, departures on off_block_date.
        // WHERE clause is broadened to catch records for either event on the queried date.
        $stmt = $this->pdo->prepare(
            "SELECT
                COALESCE(ad.category, 'charter') AS category,
                SUM(CASE WHEN COALESCE(am.on_block_date, am.movement_date) = ?
                         AND am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON'
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS arrivals,
                SUM(CASE WHEN am.off_block_date = ?
                         AND am.off_block_time IS NOT NULL AND am.off_block_time != ''
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS departures
             FROM aircraft_movements am
             LEFT JOIN aircraft_details ad ON am.registration = ad.registration
             WHERE (COALESCE(am.on_block_date, am.movement_date) = ? OR am.off_block_date = ?)
             GROUP BY category"
        );
        $stmt->execute([$date, $date, $date, $date]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Stand Usage Gantt data for a given date.
     *
     * Returns one row per movement that was active on $date, with start and
     * end times converted to minutes-since-midnight (0–1440) so the frontend
     * can render proportional bars on a 24-hour timeline.
     *
     * - Same-day movements: start = on_block, end = off_block
     * - RON arrivals (no departure yet):  start = on_block, end = 1440
     * - RON departures (arrived prev day): start = 0,        end = off_block
     */
    public function standUsage(string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                am.parking_stand,
                am.registration,
                am.operator_airline,
                COALESCE(ad.category, 'charter') AS category,
                am.on_block_time,
                am.off_block_time,
                am.is_ron,
                -- start_minutes: on_block on this date, or 0 for RON departures from previous day
                CASE
                    WHEN COALESCE(am.on_block_date, am.movement_date) = ?
                         AND am.on_block_time IS NOT NULL
                         AND am.on_block_time != ''
                         AND am.on_block_time != 'EX RON'
                    THEN (CAST(SUBSTRING_INDEX(am.on_block_time, ':', 1) AS UNSIGNED) * 60
                         + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(am.on_block_time, ':', 2), ':', -1) AS UNSIGNED))
                    ELSE 0
                END AS start_minutes,
                -- end_minutes: off_block on this date, or 1440 (end of day) if still parked
                CASE
                    WHEN am.off_block_date = ? AND am.off_block_time IS NOT NULL AND am.off_block_time != ''
                    THEN (CAST(SUBSTRING_INDEX(am.off_block_time, ':', 1) AS UNSIGNED) * 60
                         + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(am.off_block_time, ':', 2), ':', -1) AS UNSIGNED))
                    ELSE 1440
                END AS end_minutes
             FROM aircraft_movements am
             LEFT JOIN aircraft_details ad ON am.registration = ad.registration
             WHERE
                 -- Condition 1: Arrived on this date
                 (COALESCE(am.on_block_date, am.movement_date) = ?
                  AND am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON')
                 OR
                 -- Condition 2: Departed on this date
                 (am.off_block_date = ?
                  AND am.off_block_time IS NOT NULL AND am.off_block_time != '')
                 OR
                 -- Condition 3: Arrived before this date and still occupying the stand on this date
                 -- (no off_block recorded yet, or off_block is after this date)
                 (COALESCE(am.on_block_date, am.movement_date) < ?
                  AND am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON'
                  AND (am.off_block_date IS NULL OR am.off_block_date = '' OR am.off_block_date > ?
                       OR am.off_block_time IS NULL OR am.off_block_time = ''))
             ORDER BY am.parking_stand, start_minutes"
        );
        $stmt->execute([$date, $date, $date, $date, $date, $date]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Every stand the Gantt chart's "Show All Stands" toggle should list.
     *
     * Source of truth is the stands table, not the movement history — a stand
     * that has never been used still exists and must be shown. Stands found in
     * movements but missing from the table are unioned in so historical data
     * never silently disappears from the chart.
     */
    public function getAllStands(): array
    {
        // No is_active filter on purpose: every row in the shipped stands table
        // has is_active = 0, so filtering on it would return nothing. The flag
        // is unused in this dataset.
        $stmt = $this->pdo->query(
            "SELECT stand_name AS stand FROM stands
             UNION
             SELECT DISTINCT parking_stand FROM aircraft_movements
             WHERE parking_stand IS NOT NULL AND parking_stand != ''"
        );

        $stands = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'stand');

        // Natural sort so B2 comes before B10, and SA09 before SA10.
        usort($stands, static fn($a, $b) => strnatcasecmp((string) $a, (string) $b));

        return $stands;
    }

    public function findCurrentApronMovements(): array
    {

        $stmt = $this->pdo->prepare(
            "SELECT am.registration, am.aircraft_type, am.on_block_time, am.off_block_time, am.parking_stand,
                    am.from_location, am.to_location, am.flight_no_arr, am.flight_no_dep, am.operator_airline,
                    am.remarks, am.is_ron, am.ron_complete, am.movement_date, am.id,
                    COALESCE(ad.category, '') AS category
             FROM aircraft_movements am
             LEFT JOIN aircraft_details ad ON ad.registration = am.registration
             WHERE (
                (am.movement_date = CURDATE() AND (am.off_block_time IS NULL OR am.off_block_time = '')) OR
                (am.is_ron = 1 AND am.ron_complete = 0)
             )
             ORDER BY am.movement_date DESC, am.id ASC"
        );
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findHangarMovements(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM aircraft_movements WHERE to_location = 'HGR'");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Off block carries a "(dd/mm/yyyy)" stamp only when it falls on a
     * different day than the movement itself — i.e. the aircraft stayed
     * overnight and you would otherwise not know which day it left.
     *
     * A same-day departure needs no stamp: the movement date already says it.
     * Values the operator stamped by hand (already containing a bracket) are
     * passed through untouched.
     */
    protected function stampOffBlockTime(string $offBlockTime, ?string $movementDate, string $offBlockDate): string
    {
        if ($offBlockTime === '' || strpos($offBlockTime, '(') !== false) {
            return $offBlockTime;
        }

        if ($movementDate !== null && $movementDate !== $offBlockDate) {
            return $offBlockTime . ' (' . date('d/m/Y', strtotime($offBlockDate)) . ')';
        }

        return $offBlockTime;
    }

    protected function movementDateFor(int $id): ?string
    {
        $row = $this->currentMovementFor($id);

        return $row['movement_date'] ?? null;
    }

    protected function currentMovementFor(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT movement_date, off_block_time FROM aircraft_movements WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * True when the given text names a parking stand.
     *
     * Source of truth is the stands table, so every stand counts whether or not
     * it has ever been used.
     */
    protected function isParkingStand(string $value): bool
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT 1 FROM stands WHERE UPPER(stand_name) = :name LIMIT 1');
        $stmt->execute([':name' => $value]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Reposition: when an aircraft off blocks with a parking stand in its
     * destination field, it has not left the apron — it has taxied to another
     * stand. Open the follow-on movement automatically so the operator does not
     * have to retype it.
     *
     * Carries registration, aircraft type and operator only. No on block: the
     * new record is a planned arrival until the operator confirms it.
     *
     * Returns the new movement id, or null when no reposition applies.
     */
    protected function createRepositionMovement(
        string $registration,
        string $aircraftType,
        string $operatorAirline,
        string $destination,
        int $userId
    ): ?int {
        $stand = strtoupper(trim($destination));

        if ($registration === '' || !$this->isParkingStand($stand)) {
            return null;
        }

        // Idempotency guard: if this aircraft already has an open movement at
        // that stand, a reposition was already created. Re-saving the closed
        // record must not spawn duplicates.
        $existing = $this->pdo->prepare(
            "SELECT 1 FROM aircraft_movements
             WHERE registration = :registration
               AND UPPER(parking_stand) = :stand
               AND (off_block_time IS NULL OR off_block_time = '')
             LIMIT 1"
        );
        $existing->execute([':registration' => $registration, ':stand' => $stand]);

        if ($existing->fetchColumn()) {
            return null;
        }

        $insert = $this->pdo->prepare(
            "INSERT INTO aircraft_movements (
                registration, aircraft_type, on_block_time, off_block_time, parking_stand,
                from_location, to_location, flight_no_arr, flight_no_dep, operator_airline,
                remarks, is_ron, ron_complete, movement_date, on_block_date, off_block_date,
                user_id_created, user_id_updated, created_at, updated_at
             ) VALUES (
                :registration, :aircraft_type, '', NULL, :parking_stand,
                '', '', '', '', :operator_airline,
                '', 0, 0, :movement_date, NULL, NULL,
                :user_id_created, :user_id_updated, NOW(), NOW()
             )"
        );

        // Two distinct placeholders on purpose: EMULATE_PREPARES is off, so a
        // repeated named parameter is rejected by the driver.
        $insert->execute([
            ':registration' => $registration,
            ':aircraft_type' => $aircraftType,
            ':parking_stand' => $stand,
            ':operator_airline' => $operatorAirline,
            ':movement_date' => date('Y-m-d'),
            ':user_id_created' => $userId,
            ':user_id_updated' => $userId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function saveMovement(array $attributes, int $userId): array
    {
        $id = isset($attributes['id']) ? (int) $attributes['id'] : 0;
        $isUpdate = $id > 0;

        $registration = trim((string) ($attributes['registration'] ?? ''));
        if ($registration === '') {
            throw new InvalidArgumentException('Registration is required.');
        }

        $aircraftType = trim((string) ($attributes['aircraft_type'] ?? ''));
        $onBlockTime = trim((string) ($attributes['on_block_time'] ?? ''));
        $offBlockTime = trim((string) ($attributes['off_block_time'] ?? ''));
        $parkingStand = trim((string) ($attributes['parking_stand'] ?? ''));
        $fromLocation = trim((string) ($attributes['from_location'] ?? ''));
        $toLocation = trim((string) ($attributes['to_location'] ?? ''));
        $flightNoArr = trim((string) ($attributes['flight_no_arr'] ?? ''));
        $flightNoDep = trim((string) ($attributes['flight_no_dep'] ?? ''));
        $operatorAirline = trim((string) ($attributes['operator_airline'] ?? ''));
        $remarks = trim((string) ($attributes['remarks'] ?? ''));
        $isRon = filter_var($attributes['is_ron'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $params = [
            ':registration' => $registration,
            ':aircraft_type' => $aircraftType,
            ':on_block_time' => $onBlockTime,
            ':parking_stand' => $parkingStand,
            ':from_location' => $fromLocation,
            ':to_location' => $toLocation,
            ':flight_no_arr' => $flightNoArr,
            ':flight_no_dep' => $flightNoDep,
            ':operator_airline' => $operatorAirline,
            ':remarks' => $remarks,
            ':user_id_updated' => $userId,
        ];

        $previous = $isUpdate ? $this->currentMovementFor($id) : null;
        $previousOffBlock = trim((string) ($previous['off_block_time'] ?? ''));

        if ($offBlockTime !== '') {
            $offBlockDate = date('Y-m-d');
            // On update the movement may have started on an earlier day, so the
            // stored movement_date decides whether this is an overnight stay.
            $movementDate = $isUpdate ? ($previous['movement_date'] ?? null) : $offBlockDate;

            $params[':off_block_time'] = $this->stampOffBlockTime($offBlockTime, $movementDate, $offBlockDate);
            $params[':off_block_date'] = $offBlockDate;
            $params[':ron_complete'] = $isRon ? 1 : 0;
        } else {
            $params[':off_block_time'] = null;
            $params[':off_block_date'] = null;
            $params[':ron_complete'] = 0;
        }

        if ($isUpdate) {
            $params[':id'] = $id;
            $params[':is_ron'] = $isRon ? 1 : 0;
            $sql = "UPDATE aircraft_movements SET
                        registration = :registration,
                        aircraft_type = :aircraft_type,
                        on_block_time = :on_block_time,
                        off_block_time = :off_block_time,
                        parking_stand = :parking_stand,
                        from_location = :from_location,
                        to_location = :to_location,
                        flight_no_arr = :flight_no_arr,
                        flight_no_dep = :flight_no_dep,
                        operator_airline = :operator_airline,
                        remarks = :remarks,
                        is_ron = :is_ron,
                        ron_complete = :ron_complete,
                        off_block_date = :off_block_date,
                        user_id_updated = :user_id_updated,
                        updated_at = NOW()
                    WHERE id = :id";
        } else {
            $params[':user_id_created'] = $userId;
            $params[':movement_date'] = date('Y-m-d');
            $params[':on_block_date'] = $onBlockTime !== '' ? date('Y-m-d') : null;
            $params[':is_ron'] = $isRon ? 1 : 0;
            $sql = "INSERT INTO aircraft_movements (
                        registration, aircraft_type, on_block_time, off_block_time, parking_stand,
                        from_location, to_location, flight_no_arr, flight_no_dep, operator_airline,
                        remarks, is_ron, ron_complete, movement_date, on_block_date, off_block_date,
                        user_id_created, user_id_updated, created_at, updated_at
                    ) VALUES (
                        :registration, :aircraft_type, :on_block_time, :off_block_time, :parking_stand,
                        :from_location, :to_location, :flight_no_arr, :flight_no_dep, :operator_airline,
                        :remarks, :is_ron, :ron_complete, :movement_date, :on_block_date, :off_block_date,
                        :user_id_created, :user_id_updated, NOW(), NOW()
                    )";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $newId = $isUpdate ? $id : (int) $this->pdo->lastInsertId();

        // Only on the transition from "still parked" to "off blocked". Editing
        // an already-closed movement must not open a second reposition.
        $repositionId = null;
        if ($offBlockTime !== '' && $previousOffBlock === '') {
            $repositionId = $this->createRepositionMovement(
                $registration,
                $aircraftType,
                $operatorAirline,
                $toLocation,
                $userId
            );
        }

        return [
            'id' => $newId,
            'is_new' => !$isUpdate,
            'reposition_id' => $repositionId,
        ];
    }

    public function bulkUpdate(array $changes, int $userId): void
    {
        if (empty($changes)) {
            return;
        }

        $allowedFields = [
            'registration',
            'aircraft_type',
            'on_block_time',
            'parking_stand',
            'from_location',
            'to_location',
            'flight_no_arr',
            'flight_no_dep',
            'operator_airline',
            'remarks',
            'is_ron',
            'off_block_time',
        ];

        $this->pdo->beginTransaction();

        try {
            foreach ($changes as $change) {
                $id = (int) ($change['id'] ?? 0);
                $field = $change['field'] ?? '';

                if ($id <= 0 || !in_array($field, $allowedFields, true)) {
                    continue;
                }

                $value = $change['value'] ?? null;

                if ($field === 'off_block_time') {
                    $stmt = $this->pdo->prepare(
                        'SELECT is_ron, movement_date, off_block_time, registration, aircraft_type,
                                operator_airline, to_location
                         FROM aircraft_movements WHERE id = :id'
                    );
                    $stmt->execute([':id' => $id]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$current) {
                        continue;
                    }

                    $wasOpen = trim((string) ($current['off_block_time'] ?? '')) === '';

                    if (!empty($value)) {
                        // Same rule as saveMovement(): stamp the date only when
                        // the off block falls on a different day than the
                        // movement, RON or not.
                        $formatted = $this->stampOffBlockTime(
                            (string) $value,
                            $current['movement_date'] ?? null,
                            date('Y-m-d')
                        );

                        $update = $this->pdo->prepare(
                            "UPDATE aircraft_movements
                             SET off_block_time = :value,
                                 ron_complete = :ron_complete,
                                 off_block_date = :off_date,
                                 user_id_updated = :user_id,
                                 updated_at = NOW()
                             WHERE id = :id"
                        );
                        $update->execute([
                            ':value' => $formatted,
                            ':ron_complete' => ((int) ($current['is_ron'] ?? 0) === 1 ? 1 : 0),
                            ':off_date' => date('Y-m-d'),
                            ':user_id' => $userId,
                            ':id' => $id,
                        ]);

                        // Reposition, same rule as saveMovement(): only on the
                        // transition from open to off blocked.
                        if ($wasOpen) {
                            $this->createRepositionMovement(
                                (string) ($current['registration'] ?? ''),
                                (string) ($current['aircraft_type'] ?? ''),
                                (string) ($current['operator_airline'] ?? ''),
                                (string) ($current['to_location'] ?? ''),
                                $userId
                            );
                        }
                    } else {
                        $reset = $this->pdo->prepare(
                            "UPDATE aircraft_movements
                             SET off_block_time = NULL,
                                 ron_complete = 0,
                                 off_block_date = NULL,
                                 user_id_updated = :user_id,
                                 updated_at = NOW()
                             WHERE id = :id"
                        );
                        $reset->execute([
                            ':user_id' => $userId,
                            ':id' => $id,
                        ]);
                    }

                    continue;
                }

                $sql = "UPDATE aircraft_movements SET `$field` = :value, user_id_updated = :user_id, updated_at = NOW() WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':value' => $value,
                    ':user_id' => $userId,
                    ':id' => $id,
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function paginateActiveMovements(array $filters, int $page, int $perPage): array
    {
        [$filterSql, $params] = $this->buildFilterClause($filters);
        $baseCondition = "(am.movement_date = CURDATE()) OR (am.is_ron = 1 AND am.ron_complete = 0) OR (am.is_ron = 1 AND am.ron_complete = 1 AND am.off_block_date = CURDATE())";
        $where = $filterSql !== '' ? $filterSql : $baseCondition;

        $offset = max(0, ($page - 1) * $perPage);

        $countSql = "SELECT COUNT(*) FROM aircraft_movements am LEFT JOIN aircraft_details ad ON am.registration = ad.registration WHERE {$where}";
        $total = $this->executeCountQuery($countSql, $params);

        $sql = "SELECT am.*, ad.category
                FROM aircraft_movements am
                LEFT JOIN aircraft_details ad ON am.registration = ad.registration
                WHERE {$where}
                ORDER BY am.movement_date ASC, am.id ASC
                LIMIT :limit OFFSET :offset";

        $records = $this->executeListQuery($sql, $params, $perPage, $offset);

        return [
            'records' => $records,
            'total_results' => $total,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            'offset' => $offset,
        ];
    }

    public function paginateCompletedRonMovements(array $filters, int $page, int $perPage): array
    {
        [$filterSql, $params] = $this->buildFilterClause($filters);
        $baseCondition = 'am.is_ron = 1 AND am.ron_complete = 1';
        $where = $baseCondition . ($filterSql !== '' ? ' AND ' . $filterSql : '');

        $offset = max(0, ($page - 1) * $perPage);

        $countSql = "SELECT COUNT(*) FROM aircraft_movements am LEFT JOIN aircraft_details ad ON am.registration = ad.registration WHERE {$where}";
        $total = $this->executeCountQuery($countSql, $params);

        $sql = "SELECT am.id, am.registration, am.aircraft_type, am.operator_airline,
                       am.on_block_time, am.off_block_time
                FROM aircraft_movements am
                LEFT JOIN aircraft_details ad ON am.registration = ad.registration
                WHERE {$where}
                ORDER BY am.updated_at DESC
                LIMIT :limit OFFSET :offset";

        $records = $this->executeListQuery($sql, $params, $perPage, $offset);

        return [
            'records' => $records,
            'total_results' => $total,
            'total_pages' => $total > 0 ? (int) ceil($total / $perPage) : 0,
            'offset' => $offset,
        ];
    }

    public function findDuplicateFlights(string $date): array
    {
        $stmtArr = $this->pdo->prepare(
            "SELECT flight_no_arr FROM aircraft_movements WHERE movement_date = ? AND flight_no_arr != '' GROUP BY flight_no_arr HAVING COUNT(*) > 1"
        );
        $stmtArr->execute([$date]);
        $arrivals = $stmtArr->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];

        $stmtDep = $this->pdo->prepare(
            "SELECT flight_no_dep FROM aircraft_movements WHERE movement_date = ? AND flight_no_dep != '' GROUP BY flight_no_dep HAVING COUNT(*) > 1"
        );
        $stmtDep->execute([$date]);
        $departures = $stmtDep->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];

        return array_values(array_unique(array_merge($arrivals, $departures)));
    }

    /**
     * Returns the open movement (aircraft still on the ground) currently
     * occupying a stand, if any. Used for double-booking warnings.
     */
    public function findOpenMovementOnStand(string $stand, ?int $excludeId = null): ?array
    {
        $stand = trim($stand);
        if ($stand === '') {
            return null;
        }

        $sql = "SELECT id, registration, aircraft_type, on_block_time, is_ron
                FROM aircraft_movements
                WHERE UPPER(parking_stand) = UPPER(:stand)
                  AND (
                      (movement_date = CURDATE() AND on_block_time IS NOT NULL AND on_block_time != ''
                       AND (off_block_time IS NULL OR off_block_time = '')) OR
                      (is_ron = 1 AND ron_complete = 0)
                  )";
        $params = [':stand' => $stand];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id != :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= ' ORDER BY id DESC LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM aircraft_movements WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function evaluateInputWarnings(array $movement, ?int $excludeId = null, ?string $movementDate = null): array
    {
        $warnings = [];
        $duplicateFlights = [];

        $onBlock = trim((string) ($movement['on_block_time'] ?? ''));
        $offBlock = trim((string) ($movement['off_block_time'] ?? ''));

        // RON records depart on a later calendar day -- the time comparison across midnight
        // is meaningless, so skip the warning entirely for RON movements.
        $isRon = !empty($movement['is_ron']);
        if (!$isRon && $this->isOffBlockEarlierThanOnBlock($onBlock, $offBlock)) {
            $warnings[] = 'Off block timestamp is earlier than on block timestamp for the same date. Please verify.';
        }

        $movementDate = $movementDate ?: date('Y-m-d');
        $duplicates = array_map('strtoupper', $this->findDuplicateFlights($movementDate));
        $arr = strtoupper(trim((string) ($movement['flight_no_arr'] ?? '')));
        $dep = strtoupper(trim((string) ($movement['flight_no_dep'] ?? '')));

        if ($arr !== '' && in_array($arr, $duplicates, true)) {
            $duplicateFlights[] = $arr;
        }
        if ($dep !== '' && in_array($dep, $duplicates, true)) {
            $duplicateFlights[] = $dep;
        }

        $duplicateFlights = array_values(array_unique($duplicateFlights));
        if (!empty($duplicateFlights)) {
            $warnings[] = 'Duplicate flight number detected on the same date: ' . implode(', ', $duplicateFlights) . '.';
        }

        return [
            'warnings' => $warnings,
            'duplicate_flights' => $duplicateFlights,
        ];
    }

    private function isOffBlockEarlierThanOnBlock(string $onBlockTime, string $offBlockTime): bool
    {
        // If off_block_time contains a parenthesised date (e.g. "06:00 (17/05/2026)"),
        // it was recorded on a later calendar day (RON departure). The comparison is
        // meaningless across day boundaries, so skip it to avoid false-positive warnings.
        if (strpos($offBlockTime, '(') !== false) {
            return false;
        }

        $onMinutes = $this->extractTimeToMinutes($onBlockTime);
        $offMinutes = $this->extractTimeToMinutes($offBlockTime);

        if ($onMinutes === null || $offMinutes === null) {
            return false;
        }

        return $offMinutes < $onMinutes;
    }

    private function extractTimeToMinutes(string $value): ?int
    {
        if (!preg_match('/(\d{1,2}):(\d{2})/', $value, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return ($hour * 60) + $minute;
    }

    public function countOccupiedStands(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(DISTINCT T1.parking_stand)
             FROM aircraft_movements AS T1
             INNER JOIN stands AS T2 ON T1.parking_stand = T2.stand_name
             WHERE T2.capacity > 0 AND (
                 (T1.on_block_time IS NOT NULL AND T1.on_block_time != '' AND (T1.off_block_time IS NULL OR T1.off_block_time = '')) OR
                 (T1.is_ron = 1 AND T1.ron_complete = 0)
             )"
        );

        return (int) $stmt->fetchColumn();
    }

    public function countActiveRonStands(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT parking_stand)
             FROM aircraft_movements
             WHERE is_ron = 1 AND ron_complete = 0
               AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)"
        );
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function buildFilterClause(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['registration'])) {
            $conditions[] = 'am.registration LIKE :registration';
            $params[':registration'] = '%' . $filters['registration'] . '%';
        }

        // Legacy rows have NULL on_block_date — fall back to movement_date so
        // the date filter doesn't silently drop them.
        if (!empty($filters['date_from'])) {
            $conditions[] = 'COALESCE(am.on_block_date, am.movement_date) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'COALESCE(am.on_block_date, am.movement_date) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['category'])) {
            // aircraft_details.category holds mixed spellings/casings
            // ('Komersial', 'commercial', 'charter', ...) — match by group.
            $categoryGroups = [
                'commercial' => ['commercial', 'komersial'],
                'cargo' => ['cargo', 'kargo'],
                'charter' => ['charter', 'private', ''],
            ];
            $group = $categoryGroups[strtolower($filters['category'])] ?? [strtolower($filters['category'])];

            $placeholders = [];
            foreach (array_values($group) as $index => $value) {
                $placeholder = ':category' . $index;
                $placeholders[] = $placeholder;
                $params[$placeholder] = $value;
            }
            // NULL category counts as charter, matching the dashboards
            $conditions[] = "LOWER(COALESCE(ad.category, 'charter')) IN (" . implode(', ', $placeholders) . ')';
        }

        if (!empty($filters['airline'])) {
            $conditions[] = 'am.operator_airline LIKE :airline';
            $params[':airline'] = '%' . $filters['airline'] . '%';
        }

        if (!empty($filters['flight_no'])) {
            $conditions[] = '(am.flight_no_arr LIKE :flight_no OR am.flight_no_dep LIKE :flight_no)';
            $params[':flight_no'] = '%' . $filters['flight_no'] . '%';
        }

        $sql = implode(' AND ', $conditions);

        return [$sql, $params];
    }

    private function executeCountQuery(string $sql, array $params): int
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function executeListQuery(string $sql, array $params, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
