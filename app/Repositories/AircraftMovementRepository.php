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
        $stmt = $this->pdo->prepare(
            "SELECT
                SUM(CASE WHEN am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON'
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS total_arrivals,
                SUM(CASE WHEN am.off_block_time IS NOT NULL AND am.off_block_time != ''
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS total_departures
             FROM aircraft_movements am
             WHERE am.movement_date = ?"
        );
        $stmt->execute([$date]);
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
        $stmt = $this->pdo->prepare(
            "SELECT
                CONCAT(LPAD(FLOOR(HOUR(on_block_time)/2)*2,2,'0'), ':00-',
                       LPAD(FLOOR(HOUR(on_block_time)/2)*2+1,2,'0'), ':59') AS time_range,
                SUM(CASE WHEN on_block_time IS NOT NULL AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS Arrivals,
                SUM(CASE WHEN off_block_time IS NOT NULL AND parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS Departures
             FROM aircraft_movements
             WHERE movement_date = ?
               AND (on_block_time IS NOT NULL OR off_block_time IS NOT NULL)
             GROUP BY FLOOR(HOUR(on_block_time)/2)
             ORDER BY time_range"
        );
        $stmt->execute([$date]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function categoryBreakdown(string $date): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COALESCE(ad.category, 'charter') AS category,
                SUM(CASE WHEN am.on_block_time IS NOT NULL AND am.on_block_time != '' AND am.on_block_time != 'EX RON'
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS arrivals,
                SUM(CASE WHEN am.off_block_time IS NOT NULL AND am.off_block_time != ''
                         AND am.parking_stand IN (SELECT stand_name FROM stands WHERE capacity > 0)
                    THEN 1 ELSE 0 END) AS departures
             FROM aircraft_movements am
             LEFT JOIN aircraft_details ad ON am.registration = ad.registration
             WHERE am.movement_date = ?
             GROUP BY category"
        );
        $stmt->execute([$date]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findCurrentApronMovements(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT registration, aircraft_type, on_block_time, off_block_time, parking_stand,
                    from_location, to_location, flight_no_arr, flight_no_dep, operator_airline,
                    remarks, is_ron, ron_complete, movement_date, id
             FROM aircraft_movements
             WHERE (
                (movement_date = CURDATE() AND (off_block_time IS NULL OR off_block_time = '')) OR
                (is_ron = 1 AND ron_complete = 0)
             )
             ORDER BY movement_date DESC, id ASC"
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

        if ($offBlockTime !== '') {
            if (strpos($offBlockTime, '(') === false) {
                $params[':off_block_time'] = $offBlockTime . ' (' . date('d/m/Y') . ')';
            } else {
                $params[':off_block_time'] = $offBlockTime;
            }
            $params[':off_block_date'] = date('Y-m-d');
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

        return [
            'id' => $newId,
            'is_new' => !$isUpdate,
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
                    $stmt = $this->pdo->prepare('SELECT is_ron FROM aircraft_movements WHERE id = :id');
                    $stmt->execute([':id' => $id]);
                    $current = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$current) {
                        continue;
                    }

                    if (!empty($value)) {
                        $formatted = (string) $value;
                        if ((int) ($current['is_ron'] ?? 0) === 1 && strpos($formatted, '(') === false) {
                            $formatted .= ' (' . date('d/m/Y') . ')';
                        }

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

        if (!empty($filters['date_from'])) {
            $conditions[] = 'am.on_block_date >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'am.on_block_date <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['category'])) {
            $conditions[] = 'ad.category = :category';
            $params[':category'] = $filters['category'];
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