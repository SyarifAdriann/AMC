<?php

use App\Core\Application;

require_once __DIR__ . '/autoload.php';

$basePath = dirname(__DIR__);

$app = new Application($basePath, loadConfig(__DIR__ . '/../config'));

require __DIR__ . '/providers.php';

return $app;
