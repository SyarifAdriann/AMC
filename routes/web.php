<?php

use App\Controllers\ApronController;
use App\Controllers\AuthController;
use App\Controllers\MasterTableController;
use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;

$legacyPage = function (string $script) {
    return function () use ($script) {
        require dirname(__DIR__) . '/' . ltrim($script, '/');
        return '';
    };
};

foreach (["/login", "/login.php"] as $path) {
    $router->get($path, [AuthController::class, 'showLoginForm']);
    $router->post($path, [AuthController::class, 'login']);
}

foreach (["/logout", "/logout.php"] as $path) {
    $router->get($path, [AuthController::class, 'logout']);
}

$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/', [ApronController::class, 'show']);
    $router->post('/', [ApronController::class, 'handle']);
    $router->get('/apron', [ApronController::class, 'show']);

    $router->get('/index.php', [ApronController::class, 'show']);
    $router->post('/index.php', [ApronController::class, 'handle']);

    foreach (["/master-table", "/master-table.php"] as $path) {
        $router->get($path, [MasterTableController::class, 'show']);
        $router->post($path, [MasterTableController::class, 'handle']);
    }

    foreach (["/dashboard", "/dashboard.php"] as $path) {
        $router->get($path, [DashboardController::class, 'show']);
        $router->post($path, [DashboardController::class, 'handle']);
    }
});
