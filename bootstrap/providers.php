<?php

use App\Core\Application;
use App\Core\Auth\AuthManager;
use App\Core\Database\DatabaseManager;
use App\Core\View\View;
use App\Repositories\AircraftMovementRepository;
use App\Repositories\AircraftDetailRepository;
use App\Repositories\DailySnapshotRepository;
use App\Repositories\DailyStaffRosterRepository;
use App\Repositories\FlightReferenceRepository;
use App\Repositories\StandRepository;
use App\Repositories\UserRepository;
use App\Security\CsrfManager;
use App\Security\LoginThrottler;
use App\Services\ApronStatusService;
use App\Services\AuditLogger;
use App\Services\RonService;
use App\Services\SnapshotService;
use App\Services\ReportService;
use App\Services\UserAdminService;

/** @var Application $app */

// Register database services
$app->singleton(DatabaseManager::class, function (Application $app) {
    return new DatabaseManager($app);
});

$app->singleton(\PDO::class, function (Application $app) {
    return $app->make(DatabaseManager::class)->connection();
});

$app->singleton(AuthManager::class, function (Application $app) {
    return new AuthManager($app);
});

$app->singleton(UserRepository::class, function (Application $app) {
    return new UserRepository($app->make(\PDO::class));
});

$app->singleton(AircraftMovementRepository::class, function (Application $app) {
    return new AircraftMovementRepository($app->make(\PDO::class));
});

$app->singleton(AircraftDetailRepository::class, function (Application $app) {
    return new AircraftDetailRepository($app->make(\PDO::class));
});

$app->singleton(DailyStaffRosterRepository::class, function (Application $app) {
    return new DailyStaffRosterRepository($app->make(\PDO::class));
});

$app->singleton(DailySnapshotRepository::class, function (Application $app) {
    return new DailySnapshotRepository($app->make(\PDO::class));
});

$app->singleton(FlightReferenceRepository::class, function (Application $app) {
    return new FlightReferenceRepository($app->make(\PDO::class));
});

$app->singleton(StandRepository::class, function (Application $app) {
    return new StandRepository($app->make(\PDO::class));
});

$app->singleton(LoginThrottler::class, function (Application $app) {
    return new LoginThrottler($app);
});

$app->singleton(CsrfManager::class, function (Application $app) {
    return new CsrfManager($app->make(AuthManager::class));
});

$app->singleton(ApronStatusService::class, function (Application $app) {
    return new ApronStatusService(
        $app->make(App\Repositories\StandRepository::class),
        $app->make(App\Repositories\AircraftMovementRepository::class)
    );
});

$app->singleton(RonService::class, function (Application $app) {
    return new RonService($app->make(\PDO::class));
});

$app->singleton(SnapshotService::class, function (Application $app) {
    return new SnapshotService(
        $app->make(DailySnapshotRepository::class),
        $app->make(DailyStaffRosterRepository::class),
        $app->make(AircraftMovementRepository::class),
        $app->make(ApronStatusService::class),
        $app->make(RonService::class)
    );
});

$app->singleton(ReportService::class, function (Application $app) {
    return new ReportService($app->make(\PDO::class));
});

$app->singleton(AuditLogger::class, function (Application $app) {
    return new AuditLogger($app->make(\PDO::class));
});
$app->singleton(UserAdminService::class, function (Application $app) {
    return new UserAdminService(
        $app->make(App\Repositories\UserRepository::class),
        $app->make(AuditLogger::class)
    );
});


// Share common view data
View::share('appName', $app->config('app.name'));
if (!function_exists('configureLogging')) {
    function configureLogging(Application $app): void
    {
        $logging = $app->config('logging');
        if (!$logging) {
            return;
        }
        error_reporting($logging['error_reporting'] ?? E_ALL);
        ini_set('display_errors', !empty($logging['display_errors']) ? '1' : '0');
        ini_set('log_errors', !empty($logging['log_errors']) ? '1' : '0');
        if (!empty($logging['error_log'])) {
            ini_set('error_log', $logging['error_log']);
        }
    }
}
if (!function_exists('configureSession')) {
    function configureSession(Application $app): void
    {
        $config = $app->config('session');
        if (!$config) {
            return;
        }
        if (!empty($config['name'])) {
            session_name($config['name']);
        }
        $cookie = $config['cookie'] ?? [];
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $params = [
            'lifetime' => $cookie['lifetime'] ?? 0,
            'path' => $cookie['path'] ?? '/',
            'domain' => $cookie['domain'] ?? ($_SERVER['HTTP_HOST'] ?? ''),
            'secure' => $cookie['secure'] ?? $https,
            'httponly' => $cookie['httponly'] ?? true,
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ];
        session_set_cookie_params($params);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', $params['httponly'] ? '1' : '0');
        ini_set('session.cookie_secure', $params['secure'] ? '1' : '0');
    }
}
configureLogging($app);
configureSession($app);

