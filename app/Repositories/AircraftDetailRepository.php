<?php

namespace App\Repositories;

use App\Core\Cache\FileCache;
use App\Models\AircraftDetail;
use PDO;

class AircraftDetailRepository extends Repository
{
    protected ?FileCache $cache = null;

    protected function getCache(): FileCache
    {
        if ($this->cache === null) {
            $cacheDir = __DIR__ . '/../../storage/cache/aircraft_details';
            $this->cache = new FileCache($cacheDir, 600); // 10 minute TTL
        }
        return $this->cache;
    }

    public function findByRegistration(string $registration): ?AircraftDetail
    {
        $cacheKey = 'aircraft_detail:' . strtoupper($registration);
        $cache = $this->getCache();

        // Try cache first
        $cached = $cache->get($cacheKey);
        if ($cached !== null) {
            return $cached === 'NULL' ? null : AircraftDetail::fromArray($cached);
        }

        // Query database
        $stmt = $this->pdo->prepare('SELECT * FROM aircraft_details WHERE registration = ? LIMIT 1');
        $stmt->execute([$registration]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        // Cache the result (cache NULL as string 'NULL' to distinguish from cache miss)
        if ($record) {
            $cache->set($cacheKey, $record);
            return AircraftDetail::fromArray($record);
        } else {
            $cache->set($cacheKey, 'NULL', 60); // Cache NULL results for 1 minute only
            return null;
        }
    }

    public function upsert(string $registration, array $attributes): void
    {
        $params = [
            ':registration' => $registration,
            ':aircraft_type' => $attributes['aircraft_type'] ?? null,
            ':operator_airline' => $attributes['operator_airline'] ?? null,
            ':category' => $attributes['category'] ?? null,
            ':notes' => $attributes['notes'] ?? null,
        ];

        $stmt = $this->pdo->prepare(
            "INSERT INTO aircraft_details (registration, aircraft_type, operator_airline, category, notes)
             VALUES (:registration, :aircraft_type, :operator_airline, :category, :notes)
             ON DUPLICATE KEY UPDATE
                 aircraft_type = VALUES(aircraft_type),
                 operator_airline = VALUES(operator_airline),
                 category = VALUES(category),
                 notes = VALUES(notes)"
        );

        $stmt->execute($params);

        // Invalidate cache after update
        $cacheKey = 'aircraft_detail:' . strtoupper($registration);
        $this->getCache()->delete($cacheKey);
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
            $where = 'WHERE registration LIKE :s1 OR aircraft_type LIKE :s2 OR operator_airline LIKE :s3';
            $params[':s1'] = '%' . $search . '%';
            $params[':s2'] = '%' . $search . '%';
            $params[':s3'] = '%' . $search . '%';
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM aircraft_details {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT registration, aircraft_type, operator_airline, category, notes
             FROM aircraft_details {$where}
             ORDER BY registration ASC LIMIT :limit OFFSET :offset"
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

    public function deleteByRegistration(string $registration): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM aircraft_details WHERE registration = :registration');
        $stmt->execute([':registration' => $registration]);

        // Keep the lookup cache honest, same as upsert().
        $this->getCache()->delete('aircraft_detail:' . strtoupper($registration));

        return $stmt->rowCount() > 0;
    }
}