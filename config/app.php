<?php

return [
    'name' => 'Aircraft Movement Control',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'roles' => [
        'admin',
        'operator',
        'viewer',
    ],
    'session_timeout' => 1800,
    'login' => [
        'max_attempts' => 5,
        'lockout_seconds' => 900,
    ],
];
