<?php

use App\Core\Http\Request;

$app = require __DIR__ . '/../bootstrap/app.php';

$router = $app->router();

require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/api.php';

$request = Request::capture();
$app->instance(Request::class, $request);
$response = $router->dispatch($request);
$response->send();
