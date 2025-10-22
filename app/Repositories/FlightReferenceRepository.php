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
}
