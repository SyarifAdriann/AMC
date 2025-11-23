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
}