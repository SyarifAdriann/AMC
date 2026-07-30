<?php

namespace App\Repositories;

use App\Models\FlightReference;
use PDO;

class FlightReferenceRepository extends Repository
{
    public function findByFlightNumber(string $flightNumber): ?FlightReference
    {
        $stmt = $this->pdo->prepare('SELECT * FROM flight_references WHERE flight_no = ? LIMIT 1');
        $stmt->execute([$flightNumber]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ? FlightReference::fromArray($record) : null;
    }

    public function upsert(string $flightNumber, string $defaultRoute): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO flight_references (flight_no, default_route)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE default_route = VALUES(default_route)"
        );
        $stmt->execute([$flightNumber, $defaultRoute]);
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE flight_no LIKE :search OR default_route LIKE :search_route';
            $params[':search'] = '%' . $search . '%';
            $params[':search_route'] = '%' . $search . '%';
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM flight_references {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT id, flight_no, default_route FROM flight_references {$where}
             ORDER BY flight_no ASC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM flight_references WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
