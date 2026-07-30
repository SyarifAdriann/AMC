<?php

namespace App\Controllers\Api;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Security\CsrfManager;
use App\Services\AuditLogger;
use App\Services\MovementVersionService;
use RuntimeException;
use Throwable;

/**
 * Admin-only save / restore of aircraft_movements state.
 *
 * GET  ?action=list                       list saved versions
 * POST action=save    label=...           save current movements as a version
 * POST action=restore id=...              replace movements with that version
 * POST action=wipe                        replace movements with nothing
 * POST action=delete  id=...              delete a saved version
 */
class MovementVersionController extends Controller
{
    protected AuthManager $auth;
    protected MovementVersionService $versions;
    protected CsrfManager $csrf;
    protected AuditLogger $audit;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->versions = $app->make(MovementVersionService::class);
        $this->csrf = $app->make(CsrfManager::class);
        $this->audit = $app->make(AuditLogger::class);
    }

    public function handle(): Response
    {
        $request = $this->request();

        try {
            $this->auth->ensureSession();
            $this->csrf->token();

            if (!$this->hasRole('admin')) {
                return $this->forbidden('Only administrators can manage movement versions');
            }

            switch ($this->resolveAction($request)) {
                case 'list':
                    return $this->listVersions();
                case 'save':
                    return $this->saveVersion($request);
                case 'restore':
                    return $this->restore($request);
                case 'wipe':
                    return $this->wipe($request);
                case 'delete':
                    return $this->deleteVersion($request);
                default:
                    return $this->error('Invalid action');
            }
        } catch (RuntimeException $e) {
            // Messages this app raises deliberately, safe and useful to show.
            error_log('MovementVersionController: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            // Anything unexpected: raw text can carry SQL and paths, so only in debug.
            error_log('MovementVersionController error: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => $this->app->config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to complete that request right now.',
            ], 500);
        }
    }

    protected function listVersions(): Response
    {
        return $this->json([
            'success' => true,
            'data' => $this->versions->listVersions(),
        ]);
    }

    protected function saveVersion(Request $request): Response
    {
        if (($guard = $this->guardWrite($request)) !== null) {
            return $guard;
        }

        $label = (string) $request->input('label', '');
        if (trim($label) === '') {
            return $this->error('A name for this version is required.');
        }

        $userId = $this->userId();
        $id = $this->versions->saveVersion($label, $userId ?: null);

        $this->audit->log($userId, 'SAVE_MOVEMENT_VERSION', 'movement_versions', $id, ['label' => $label]);

        return $this->json([
            'success' => true,
            'message' => 'Current movement data saved.',
            'id' => $id,
        ]);
    }

    protected function restore(Request $request): Response
    {
        if (($guard = $this->guardWrite($request)) !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->error('Which version to restore was not specified.');
        }

        return $this->applyRestore($id, 'RESTORE_MOVEMENT_VERSION');
    }

    protected function wipe(Request $request): Response
    {
        if (($guard = $this->guardWrite($request)) !== null) {
            return $guard;
        }

        return $this->applyRestore(null, 'WIPE_MOVEMENTS');
    }

    protected function applyRestore(?int $versionId, string $auditAction): Response
    {
        $userId = $this->userId();
        $result = $this->versions->restoreTo($versionId, $userId ?: null);

        $this->audit->log($userId, $auditAction, 'aircraft_movements', $versionId, $result);

        return $this->json([
            'success' => true,
            'message' => sprintf(
                'Restored "%s" (%d movements). Previous state was saved first.',
                $result['restored_label'],
                $result['row_count']
            ),
            'data' => $result,
        ]);
    }

    protected function deleteVersion(Request $request): Response
    {
        if (($guard = $this->guardWrite($request)) !== null) {
            return $guard;
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0 || !$this->versions->deleteVersion($id)) {
            return $this->error('That saved version no longer exists.', 404);
        }

        $this->audit->log($this->userId(), 'DELETE_MOVEMENT_VERSION', 'movement_versions', $id);

        return $this->json([
            'success' => true,
            'message' => 'Version deleted.',
        ]);
    }

    /**
     * POST + valid CSRF, or a Response explaining why not.
     */
    protected function guardWrite(Request $request): ?Response
    {
        if ($request->method() !== 'POST') {
            return $this->error('Invalid request method', 405);
        }

        if (!$this->csrf->validate($request->input('csrf_token'))) {
            return $this->error('Invalid CSRF token', 403);
        }

        return null;
    }

    protected function userId(): int
    {
        $user = $this->auth->user();

        return (int) ($user['id'] ?? 0);
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
