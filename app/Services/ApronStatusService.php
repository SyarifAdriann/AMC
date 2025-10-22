<?php

namespace App\Services;

use App\Repositories\AircraftMovementRepository;
use App\Repositories\StandRepository;
use Throwable;

class ApronStatusService
{
    protected StandRepository $stands;
    protected AircraftMovementRepository $movements;

    public function __construct(StandRepository $stands, AircraftMovementRepository $movements)
    {
        $this->stands = $stands;
        $this->movements = $movements;
    }

    public function getStatus(): array
    {
        try {
            $totalStands = $this->stands->countActive();
            $occupiedStands = $this->movements->countOccupiedStands();
            $availableStands = $totalStands - $occupiedStands;
            $ronCount = $this->movements->countActiveRonStands();

            return [
                'total' => $totalStands,
                'available' => max(0, $availableStands),
                'occupied' => $occupiedStands,
                'ron' => $ronCount,
            ];
        } catch (Throwable $e) {
            error_log('ApronStatusService error: ' . $e->getMessage());

            return [
                'total' => 83,
                'available' => 0,
                'occupied' => 0,
                'ron' => 0,
            ];
        }
    }
}