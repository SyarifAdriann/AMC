<?php

namespace App\Repositories;

use App\Models\AircraftDetail;
use PDO;

class AircraftDetailRepository extends Repository
{
    public function findByRegistration(string $registration): ?AircraftDetail
    {
        $stmt = $this->pdo->prepare('SELECT * FROM aircraft_details WHERE registration = ? LIMIT 1');
        $stmt->execute([$registration]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ? AircraftDetail::fromArray($record) : null;
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
    }
}