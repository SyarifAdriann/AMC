<?php

use App\Core\Application;

if (!function_exists('legacy_app')) {
    function legacy_app(): Application
    {
        static $app;

        if (!$app) {
            $app = require __DIR__ . '/app.php';
        }

        return $app;
    }
}

if (!function_exists('legacy_pdo')) {
    function legacy_pdo(): \PDO
    {
        return legacy_app()->make(\PDO::class);
    }
}

if (!function_exists('legacy_config')) {
    function legacy_config(string $key, $default = null)
    {
        return legacy_app()->config($key, $default);
    }
}

if (!function_exists('legacy_csrf_token')) {
    function legacy_csrf_token(): string
    {
        return legacy_app()->make(\App\Security\CsrfManager::class)->token();
    }
}

if (!function_exists('legacy_validate_csrf')) {
    function legacy_validate_csrf(?string $token): bool
    {
        return legacy_app()->make(\App\Security\CsrfManager::class)->validate($token);
    }
}

if (!function_exists('legacy_regenerate_csrf')) {
    function legacy_regenerate_csrf(): string
    {
        return legacy_app()->make(\App\Security\CsrfManager::class)->regenerate();
    }
}
if (!function_exists('legacy_apron_status')) {
    function legacy_apron_status(): array
    {
        return legacy_app()->make(\App\Services\ApronStatusService::class)->getStatus();
    }
}

if (!function_exists('legacy_ron_service')) {
    function legacy_ron_service(): \App\Services\RonService
    {
        return legacy_app()->make(\App\Services\RonService::class);
    }
}

if (!function_exists('legacy_user_repository')) {
    function legacy_user_repository(): \App\Repositories\UserRepository
    {
        return legacy_app()->make(\App\Repositories\UserRepository::class);
    }
}

if (!function_exists('legacy_daily_snapshot_repository')) {
    function legacy_daily_snapshot_repository(): \App\Repositories\DailySnapshotRepository
    {
        return legacy_app()->make(\App\Repositories\DailySnapshotRepository::class);
    }
}

if (!function_exists('legacy_daily_staff_roster_repository')) {
    function legacy_daily_staff_roster_repository(): \App\Repositories\DailyStaffRosterRepository
    {
        return legacy_app()->make(\App\Repositories\DailyStaffRosterRepository::class);
    }
}

if (!function_exists('legacy_aircraft_movement_repository')) {
    function legacy_aircraft_movement_repository(): \App\Repositories\AircraftMovementRepository
    {
        return legacy_app()->make(\App\Repositories\AircraftMovementRepository::class);
    }
}

if (!function_exists('legacy_flight_reference_repository')) {
    function legacy_flight_reference_repository(): \App\Repositories\FlightReferenceRepository
    {
        return legacy_app()->make(\App\Repositories\FlightReferenceRepository::class);
    }
}

if (!function_exists('legacy_stand_repository')) {
    function legacy_stand_repository(): \App\Repositories\StandRepository
    {
        return legacy_app()->make(\App\Repositories\StandRepository::class);
    }
}
