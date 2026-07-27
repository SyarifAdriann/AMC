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
