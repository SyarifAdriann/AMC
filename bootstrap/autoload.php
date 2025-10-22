<?php

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

if (!function_exists('loadConfig')) {
    function loadConfig(string $configPath): array
    {
        $config = [];

        if (!is_dir($configPath)) {
            return $config;
        }

        foreach (glob(rtrim($configPath, '/\\') . '/*.php') as $file) {
            $name = basename($file, '.php');
            $config[$name] = require $file;
        }

        return $config;
    }
}

