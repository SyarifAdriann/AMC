<?php

namespace App\Controllers;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\UserRepository;
use App\Security\LoginThrottler;
use App\Services\AuditLogger;
use App\Models\User;
use PDO;
use Throwable;

class AuthController extends Controller
{
    protected AuthManager $auth;
    protected UserRepository $users;
    protected LoginThrottler $throttler;
    protected AuditLogger $audit;
    protected PDO $pdo;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->users = $app->make(UserRepository::class);
        $this->throttler = $app->make(LoginThrottler::class);
        $this->audit = $app->make(AuditLogger::class);
        $this->pdo = $app->make(PDO::class);
    }

    public function showLoginForm(): Response
    {
        $request = $this->request();
        $this->auth->ensureSession();

        if ($this->auth->check()) {
            return Response::redirect('index.php');
        }

        $error = '';
        $showLockout = false;

        if ($request->query('timeout')) {
            $error = 'Your session has expired. Please log in again.';
        }

        return $this->renderLogin($request, $error, $showLockout);
    }

    public function login(): Response
    {
        $request = $this->request();
        $this->auth->ensureSession();

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if ($this->auth->check()) {
            return Response::redirect('index.php');
        }

        if ($username === '' || $password === '') {
            return $this->renderLogin($request, 'Please enter both username and password.');
        }

        try {
            if ($this->throttler->hasTooManyAttempts($this->pdo, $ip)) {
                return $this->renderLogin($request, 'Too many failed attempts. Please try again in 15 minutes.', true);
            }

            $user = $this->users->findForAuthentication($username);

            if ($this->canAuthenticate($user, $password)) {
                $this->auth->login([
                    'id' => $user->id(),
                    'username' => $user->username(),
                    'role' => $user->role(),
                ]);
                $this->audit->log((int) $user->id(), 'LOGIN_SUCCESS', 'users', (int) $user->id());
                $this->throttler->clear($this->pdo, $ip);

                return Response::redirect('index.php');
            }

            $this->throttler->hit($this->pdo, $ip, $username);

            if ($user) {
                $this->audit->log((int) $user->id(), 'LOGIN_FAIL', 'users', (int) $user->id(), ['ip' => $ip]);
            }

            return $this->renderLogin($request, 'Invalid username or password.');
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            return $this->renderLogin($request, 'Login system temporarily unavailable. Please try again.');
        }
    }

    public function logout(): Response
    {
        $this->auth->ensureSession();

        $user = $this->auth->user();

        if ($user) {
            try {
                $this->audit->log($user['id'], 'LOGOUT', 'users', $user['id']);
            } catch (Throwable $e) {
                error_log('Logout logging error: ' . $e->getMessage());
            }
        }

        $this->auth->logout();

        return Response::redirect('login.php');
    }

    protected function renderLogin(Request $request, string $errorMessage = '', bool $showLockout = false): Response
    {
        $old = [
            'username' => $request->input('username', ''),
        ];

        return $this->view('auth/login', [
            'error_message' => $errorMessage,
            'show_lockout' => $showLockout,
            'old' => $old,
        ]);
    }

    protected function canAuthenticate(?User $user, string $password): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->status() !== 'active') {
            return false;
        }

        return password_verify($password, $user->passwordHash());
    }
}
