<?php

namespace App\Controllers\Api;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Security\CsrfManager;
use App\Services\AuditLogger;
use App\Services\SnapshotService;
use Throwable;

class SnapshotController extends Controller
{
    protected AuthManager $auth;
    protected SnapshotService $snapshots;
    protected CsrfManager $csrf;
    protected AuditLogger $audit;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->snapshots = $app->make(SnapshotService::class);
        $this->csrf = $app->make(CsrfManager::class);
        $this->audit = $app->make(AuditLogger::class);
    }

    public function handle(): Response
    {
        $request = $this->request();

        try {
            $this->auth->ensureSession();
            $this->csrf->token();

            if (!$this->hasRole(['admin', 'operator'])) {
                return $this->forbidden('Unauthorized access to snapshot management');
            }

            $action = $this->resolveAction($request);

            switch ($action) {
                case 'create':
                    return $this->createSnapshot($request);
                case 'list':
                    return $this->listSnapshots($request);
                case 'view':
                    return $this->viewSnapshot($request);
                case 'delete':
                    return $this->deleteSnapshot($request);
                default:
                    return $this->error('Invalid action');
            }
        } catch (Throwable $e) {
            error_log('SnapshotController error: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function createSnapshot(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return $this->error('Invalid request method', 405);
        }

        if (!$this->csrf->validate($request->input('csrf_token'))) {
            return $this->error('Invalid CSRF token');
        }

        $snapshotDate = $request->input('snapshot_date');
        if (!is_string($snapshotDate) || trim($snapshotDate) === '') {
            $snapshotDate = date('Y-m-d');
        }

        $user = $this->auth->user();
        $userId = (int) ($user['id'] ?? 0);

        $snapshotData = $this->snapshots->collectSnapshotData($snapshotDate);
        $this->snapshots->upsertSnapshot($snapshotDate, $userId, $snapshotData);

        $this->audit->log($userId, 'UPSERT_SNAPSHOT', 'daily_snapshots', null, ['date' => $snapshotDate]);

        return $this->json([
            'success' => true,
            'message' => 'Daily snapshot saved successfully',
        ]);
    }

    protected function listSnapshots(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));

        $result = $this->snapshots->paginateSnapshots($page, $perPage);

        return $this->json([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    protected function viewSnapshot(Request $request): Response
    {
        $snapshotId = (int) $request->query('id', 0);
        if ($snapshotId <= 0) {
            return $this->error('Snapshot not found', 404);
        }

        $snapshot = $this->snapshots->findSnapshotById($snapshotId);

        if (!$snapshot) {
            return $this->error('Snapshot not found', 404);
        }

        return $this->json([
            'success' => true,
            'data' => $snapshot,
        ]);
    }

    protected function deleteSnapshot(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return $this->error('Invalid request method', 405);
        }

        if (!$this->csrf->validate($request->input('csrf_token'))) {
            return $this->error('Invalid CSRF token');
        }

        if (!$this->hasRole('admin')) {
            return $this->forbidden('Only administrators can delete snapshots');
        }

        $snapshotId = (int) $request->input('id', 0);
        if ($snapshotId <= 0) {
            return $this->error('Snapshot not found', 404);
        }

        $snapshotInfo = $this->snapshots->deleteSnapshot($snapshotId);

        if (!$snapshotInfo) {
            return $this->error('Snapshot not found', 404);
        }

        $user = $this->auth->user();
        $userId = (int) ($user['id'] ?? 0);

        $this->audit->log($userId, 'DELETE_SNAPSHOT', 'daily_snapshots', $snapshotId, null, $snapshotInfo);

        return $this->json([
            'success' => true,
            'message' => 'Snapshot deleted successfully',
        ]);
    }

    protected function resolveAction(Request $request): string
    {
        $action = $request->input('action');

        if (!is_string($action) || $action === '') {
            $action = $request->query('action', '');
        }

        return strtolower((string) $action);
    }

    protected function hasRole($roles): bool
    {
        $user = $this->auth->user();
        $role = $user['role'] ?? null;

        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }

        return $role === $roles;
    }

    protected function error(string $message, int $status = 400): Response
    {
        return $this->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function forbidden(string $message): Response
    {
        return $this->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
