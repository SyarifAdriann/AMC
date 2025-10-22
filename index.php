<?php

use App\Core\Http\Request;

require_once __DIR__ . '/bootstrap/legacy.php';

$app = legacy_app();
$router = $app->router();

$request = Request::capture();
$app->instance(Request::class, $request);

require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/api.php';

$response = $router->dispatch($request);
$response->send();
