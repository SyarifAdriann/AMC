<?php

namespace App\Security;

use App\Core\Application;
use PDO;

class LoginThrottler
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function hasTooManyAttempts(PDO $pdo, string $ipAddress): bool
    {
        $attempts = $this->recentAttemptCount($pdo, $ipAddress);
        return $attempts >= $this->maxAttempts();
    }

    public function recentAttemptCount(PDO $pdo, string $ipAddress): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL ? SECOND)'
        );
        $stmt->execute([$ipAddress, $this->lockoutSeconds()]);

        return (int) $stmt->fetchColumn();
    }

    public function hit(PDO $pdo, string $ipAddress, string $username): void
    {
        $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, username_attempted, attempt_time) VALUES (?, ?, NOW())');
        $stmt->execute([$ipAddress, $username]);
    }

    public function clear(PDO $pdo, string $ipAddress): void
    {
        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
        $stmt->execute([$ipAddress]);
    }

    public function maxAttempts(): int
    {
        return (int) $this->app->config('app.login.max_attempts', 5);
    }

    public function lockoutSeconds(): int
    {
        return (int) $this->app->config('app.login.lockout_seconds', 900);
    }
}
