<?php

namespace App\Middleware;

use App\Core\Application;
use App\Core\Auth\AuthManager;
use App\Core\Http\Request;
use App\Core\Http\Response;

class AuthMiddleware
{
    protected Application $app;
    protected AuthManager $auth;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->auth = $app->make(AuthManager::class);
    }

    public function handle(Request $request, callable $next)
    {
        $this->auth->ensureSession();

        if (!$this->auth->check()) {
            return $this->unauthenticated($request);
        }

        // 24/7 ops unit: no inactivity timeout. Sessions are 30 days,
        // sliding — refresh the cookie expiry at most once a day so an
        // in-use screen never gets logged out.
        $lastRefresh = $_SESSION['cookie_refreshed_at'] ?? 0;
        if (time() - $lastRefresh > 86400) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + (int) $this->app->config('session.cookie.lifetime', 2592000),
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
            $_SESSION['cookie_refreshed_at'] = time();
        }

        return $next($request);
    }

    protected function unauthenticated(Request $request)
    {
        if ($this->expectsJson($request)) {
            return Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return Response::redirect('login.php');
    }

    protected function expectsJson(Request $request): bool
    {
        $requestedWith = $request->header('X-Requested-With');
        if ($requestedWith && strtolower($requestedWith) === 'xmlhttprequest') {
            return true;
        }

        $accept = strtolower((string) $request->header('Accept'));
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        return $request->method() !== 'GET';
    }
}
