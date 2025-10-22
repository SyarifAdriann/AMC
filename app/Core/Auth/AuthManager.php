<?php

namespace App\Core\Auth;

use App\Core\Application;

class AuthManager
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function id(): ?int
    {
        return $this->check() ? (int) $_SESSION['user_id'] : null;
    }

    public function role(): ?string
    {
        return $this->check() ? (string) $_SESSION['role'] : null;
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
        ];
    }

    public function login(array $user): void
    {
        $this->ensureSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
