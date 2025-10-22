<?php

namespace App\Services;

use App\Models\DailySnapshot;
use App\Repositories\AircraftMovementRepository;
use App\Repositories\DailySnapshotRepository;
use App\Repositories\DailyStaffRosterRepository;

class SnapshotService
{
    protected DailySnapshotRepository $snapshots;
    protected DailyStaffRosterRepository $rosters;
    protected AircraftMovementRepository $movements;
    protected ApronStatusService $apronStatus;
    protected RonService $ronService;

    public function __construct(
        DailySnapshotRepository $snapshots,
        DailyStaffRosterRepository $rosters,
        AircraftMovementRepository $movements,
        ApronStatusService $apronStatus,
        RonService $ronService
    ) {
        $this->snapshots = $snapshots;
        $this->rosters = $rosters;
        $this->movements = $movements;
        $this->apronStatus = $apronStatus;
        $this->ronService = $ronService;
    }

    public function collectSnapshotData(string $date): array
    {
        $this->ronService->carryOverActiveRon();

        return [
            'staff_roster' => $this->modelsToArray($this->rosters->findByDate($date)),
            'movements' => $this->modelsToArray($this->movements->findByDateWithDetails($date)),
            'ron_data' => $this->modelsToArray($this->movements->findRonByDate($date)),
            'daily_metrics' => $this->buildDailyMetrics($date),
        ];
    }

    public function upsertSnapshot(string $date, int $userId, array $data): void
    {
        $this->snapshots->upsert($date, $userId, $data);
    }

    public function snapshotExistsForDate(string $date): bool
    {
        return $this->snapshots->existsForDate($date);
    }

    public function paginateSnapshots(int $page, int $perPage): array
    {
        $result = $this->snapshots->paginate($page, $perPage);

        return [
            'data' => array_map(fn(DailySnapshot $snapshot) => $this->snapshotToArray($snapshot), $result['data']),
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ];
    }

    public function findSnapshotById(int $id): ?array
    {
        $snapshot = $this->snapshots->findById($id);

        if (!$snapshot) {
            return null;
        }

        return $this->snapshotToArray($snapshot);
    }

    public function deleteSnapshot(int $id): ?array
    {
        $snapshot = $this->snapshots->deleteById($id);

        if (!$snapshot) {
            return null;
        }

        return ['snapshot_date' => $snapshot->snapshotDate()];
    }

    protected function buildDailyMetrics(string $date): array
    {
        $totals = $this->movements->countArrivalsAndDepartures($date);
        $newRonCount = $this->movements->countNewRon($date);
        $activeRonCount = $this->movements->countActiveRon();
        $hourly = $this->movements->hourlyBreakdown($date);
        $movementsByCategory = $this->movements->categoryBreakdown($date);

        return [
            'total_arrivals' => $totals['total_arrivals'] ?? 0,
            'total_departures' => $totals['total_departures'] ?? 0,
            'new_ron' => $newRonCount,
            'active_ron' => $activeRonCount,
            'hourly_movements' => $hourly,
            'movements_by_category' => $movementsByCategory,
            'ron_count' => $newRonCount,
            'apron_status' => $this->apronStatus->getStatus(),
            'snapshot_generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<int, \App\Models\Model> $models
     */
    protected function modelsToArray(array $models): array
    {
        return array_map(static fn($model) => $model->toArray(), $models);
    }

    protected function snapshotToArray(DailySnapshot $snapshot): array
    {
        $data = $snapshot->toArray();
        $data['snapshot_data'] = $snapshot->data();

        return $data;
    }
}
