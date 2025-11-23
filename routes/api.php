<?php

use App\Controllers\Api\SnapshotController;
use App\Controllers\Admin\UserController;
use App\Controllers\ApronController;
use App\Controllers\DashboardController;
use App\Controllers\MasterTableController;
use App\Middleware\AuthMiddleware;

$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    // User management routes - register BEFORE generic patterns
    $router->get('/api/admin/users', [UserController::class, 'handle']);
    $router->post('/api/admin/users', [UserController::class, 'handle']);
    
    // Other routes...
    $router->match(['GET', 'POST'], '/snapshot-manager.php', [SnapshotController::class, 'handle']);
    $router->match(['GET', 'POST'], '/api/snapshots', [SnapshotController::class, 'handle']);
    $router->post('/api/apron', [ApronController::class, 'handle']);
    $router->get('/api/apron/status', [ApronController::class, 'status']);
    $router->get('/api/apron/movements', [ApronController::class, 'movements']);
    $router->post('/api/apron/recommend', [ApronController::class, 'recommend']);
    $router->get('/api/ml/metrics', [ApronController::class, 'mlMetrics']);
    $router->get('/api/ml/logs', [ApronController::class, 'mlPredictionLog']);
    $router->get('/api/dashboard/movements', [DashboardController::class, 'movementMetrics']);
    $router->post('/api/master-table', [MasterTableController::class, 'handle']);

    // Legacy user management routes
    foreach (['/admin-users.php', '/user_management.php'] as $path) {
        $router->match(['GET', 'POST'], $path, [UserController::class, 'handle']);
    }
});
