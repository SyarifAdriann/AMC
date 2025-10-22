<?php

namespace App\Security;

use App\Core\Auth\AuthManager;

class CsrfManager
{
    protected AuthManager $auth;
    protected string $sessionKey = '_csrf_token';

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    public function token(): string
    {
        $this->auth->ensureSession();

        if (empty($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = bin2hex(random_bytes(32));
        }

        return $_SESSION[$this->sessionKey];
    }

    public function validate(?string $token): bool
    {
        $this->auth->ensureSession();

        if (!is_string($token) || $token === '') {
            return false;
        }

        $stored = $_SESSION[$this->sessionKey] ?? null;

        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    public function regenerate(): string
    {
        $this->auth->ensureSession();
        $_SESSION[$this->sessionKey] = bin2hex(random_bytes(32));

        return $_SESSION[$this->sessionKey];
    }

    public function inputField(string $name = 'csrf_token'): string
    {
        $token = $this->token();

        return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
