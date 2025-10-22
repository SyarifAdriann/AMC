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

        $timeout = (int) $this->app->config('app.session_timeout', 1800);
        $lastActivity = $_SESSION['last_activity'] ?? null;
        if ($lastActivity && (time() - $lastActivity) > $timeout) {
            $this->auth->logout();
            return $this->timeoutResponse($request);
        }

        $_SESSION['last_activity'] = time();

        return $next($request);
    }

    protected function unauthenticated(Request $request)
    {
        if ($this->expectsJson($request)) {
            return Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return Response::redirect('login.php');
    }

    protected function timeoutResponse(Request $request)
    {
        if ($this->expectsJson($request)) {
            return Response::json(['success' => false, 'message' => 'Session expired'], 440);
        }

        return Response::redirect('login.php?timeout=1');
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
