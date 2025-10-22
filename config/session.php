<?php

return [
    'name' => getenv('SESSION_NAME') ?: 'amc_session',
    'timeout' => 1800,
    'cookie' => [
        'lifetime' => 0,
        'path' => '/',
        'domain' => null,
        'secure' => null,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
