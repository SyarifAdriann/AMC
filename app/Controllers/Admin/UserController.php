<?php

namespace App\Controllers\Admin;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Security\CsrfManager;
use App\Services\UserAdminService;
use App\Repositories\UserRepository; // ADD THIS
use InvalidArgumentException;
use Throwable;

class UserController extends Controller
{
    protected AuthManager $auth;
    protected CsrfManager $csrf;
    protected UserAdminService $users;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->csrf = $app->make(CsrfManager::class);
        $this->users = $app->make(UserAdminService::class);
    }

    public function handle(): Response
    {
        if (!$this->auth->check() || $this->auth->role() !== 'admin') {
            return Response::json([
                'success' => false,
                'message' => 'Unauthorized access to user management',
            ], 403);
        }

        $request = $this->request();
        $action = strtolower((string) ($request->input('action') ?? $request->query('action') ?? 'list'));

        try {
            // Handle GET requests as list by default
            if ($request->method() === 'GET' && !$request->query('action')) {
                return $this->list($request);
            }

            switch ($action) {
                case 'list':
                    return $this->list($request);
                case 'create':
                    return $this->create($request);
                case 'update':
                    return $this->update($request);
                case 'reset_password':
                    return $this->resetPassword($request);
                case 'set_status':
                    return $this->setStatus($request);
                case 'delete':
                    return $this->delete($request);
                default:
                    return Response::json([
                        'success' => false,
                        'message' => 'Invalid action',
                    ], 400);
            }
        } catch (InvalidArgumentException $e) {
            return Response::json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            error_log('Admin user management error: ' . $e->getMessage());

            return Response::json([
                'success' => false,
                'message' => 'A server error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function list(Request $request): Response
    {
        $filters = [
            'query' => $request->query('query', ''),
            'role' => $request->query('role', ''),
            'status' => $request->query('status', ''),
        ];

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 25);

        $result = $this->users->list($filters, $page, $perPage);

        return Response::json([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    protected function create(Request $request): Response
    {
        if ($response = $this->validateCsrf($request)) {
            return $response;
        }

        $payload = [
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'full_name' => $request->input('full_name'),
            'role' => $request->input('role'),
            'status' => $request->input('status', 'active'),
            'password' => $request->input('password'),
        ];

        $result = $this->users->create($payload, (int) $this->auth->id());

        return Response::json([
            'success' => true,
            'message' => 'User created successfully',
            'temp_password' => $result['temp_password'],
            'id' => $result['id'],
        ]);
    }

    protected function update(Request $request): Response
    {
        if ($response = $this->validateCsrf($request)) {
            return $response;
        }

        $id = (int) $request->input('id');
        if ($id <= 0) {
            throw new InvalidArgumentException('Missing user id.');
        }

        $payload = [
            'email' => $request->input('email'),
            'full_name' => $request->input('full_name'),
            'role' => $request->input('role'),
            'status' => $request->input('status'),
        ];

        $this->users->update($id, $payload, (int) $this->auth->id());

        return Response::json([
            'success' => true,
            'message' => 'User updated successfully',
        ]);
    }

    protected function resetPassword(Request $request): Response
    {
        if ($response = $this->validateCsrf($request)) {
            return $response;
        }

        $id = (int) $request->input('id');
        $password = (string) $request->input('password', '');

        $tempPassword = $this->users->resetPassword($id, $password, (int) $this->auth->id());

        return Response::json([
            'success' => true,
            'message' => 'Password updated successfully',
            'temp_password' => $tempPassword,
        ]);
    }

    protected function setStatus(Request $request): Response
    {
        if ($response = $this->validateCsrf($request)) {
            return $response;
        }

        $id = (int) $request->input('id');
        $status = (string) $request->input('status', '');

        $this->users->setStatus($id, $status, (int) $this->auth->id());

        return Response::json([
            'success' => true,
            'message' => 'User status updated successfully',
        ]);
    }

    protected function delete(Request $request): Response
    {
        if ($response = $this->validateCsrf($request)) {
            return $response;
        }

        $id = (int) $request->input('id');
        if ($id <= 0) {
            throw new InvalidArgumentException('Missing user id.');
        }

        $this->users->delete($id, (int) $this->auth->id());

        return Response::json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    protected function validateCsrf(Request $request): ?Response
    {
        $token = $request->input('csrf_token');
        if (!$this->csrf->validate($token)) {
            return Response::json([
                'success' => false,
                'message' => 'Invalid security token. Please refresh and try again.',
            ], 400);
        }

        return null;
    }
}
