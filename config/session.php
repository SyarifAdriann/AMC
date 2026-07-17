<?php

return [
    'name' => getenv('SESSION_NAME') ?: 'amc_session',
    'cookie' => [
        // 30 days — 24/7 ops unit, no inactivity timeout. The cookie slides
        // (AuthMiddleware re-issues it daily) so active screens never expire.
        'lifetime' => 2592000,
        'path' => '/',
        'domain' => null,
        'secure' => null,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
