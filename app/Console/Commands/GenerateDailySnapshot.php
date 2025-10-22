<?php

namespace App\Console\Commands;

use App\Repositories\UserRepository;
use App\Services\SnapshotService;
use DateTimeImmutable;
use Exception;

class GenerateDailySnapshot
{
    protected SnapshotService $snapshots;
    protected UserRepository $users;

    public function __construct(SnapshotService $snapshots, UserRepository $users)
    {
        $this->snapshots = $snapshots;
        $this->users = $users;
    }

    /**
     * Handle the command.
     *
     * @param string|null $date Optional date (Y-m-d). Defaults to yesterday.
     */
    public function handle(?string $date = null): int
    {
        try {
            $targetDate = $this->normaliseDate($date);
        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            return 1;
        }

        if ($this->snapshots->snapshotExistsForDate($targetDate)) {
            echo "Snapshot for {$targetDate} already exists." . PHP_EOL;
            return 0;
        }

        $systemUser = $this->users->ensureSystemUser();
        $systemUserId = $systemUser ? ($systemUser->id() ?? 0) : 0;

        $payload = $this->snapshots->collectSnapshotData($targetDate);
        $this->snapshots->upsertSnapshot($targetDate, $systemUserId, $payload);

        echo "Daily snapshot for {$targetDate} created successfully." . PHP_EOL;
        return 0;
    }

    protected function normaliseDate(?string $input): string
    {
        if ($input === null || trim($input) === '') {
            return (new DateTimeImmutable('yesterday'))->format('Y-m-d');
        }

        try {
            return (new DateTimeImmutable($input))->format('Y-m-d');
        } catch (Exception $e) {
            throw new Exception('Invalid date supplied: ' . $input, 0, $e);
        }
    }
}
