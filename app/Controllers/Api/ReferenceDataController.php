<?php

namespace App\Controllers\Api;

use App\Core\Auth\AuthManager;
use App\Core\Controller;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Repositories\AircraftDetailRepository;
use App\Repositories\FlightReferenceRepository;
use App\Security\CsrfManager;
use App\Services\AuditLogger;
use Throwable;

/**
 * Browse / edit / delete the two reference tables behind the dashboard's
 * "Manage Aircraft Details" and "Manage Flight References" screens.
 *
 * Deliberately separate from DashboardController::handle(), which renders full
 * pages for the original add-only forms. Those still work untouched; this adds
 * the JSON API the list UI needs.
 *
 * GET  ?action=list&type=aircraft|flight&page=&search=
 * POST action=save   type=... + fields
 * POST action=delete type=... + key        (admin only)
 */
class ReferenceDataController extends Controller
{
    protected AuthManager $auth;
    protected AircraftDetailRepository $aircraft;
    protected FlightReferenceRepository $flights;
    protected CsrfManager $csrf;
    protected AuditLogger $audit;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->auth = $app->make(AuthManager::class);
        $this->aircraft = $app->make(AircraftDetailRepository::class);
        $this->flights = $app->make(FlightReferenceRepository::class);
        $this->csrf = $app->make(CsrfManager::class);
        $this->audit = $app->make(AuditLogger::class);
    }

    public function handle(): Response
    {
        $request = $this->request();

        try {
            $this->auth->ensureSession();
            $this->csrf->token();

            // Same audience as the existing reference buttons on the dashboard.
            if (!$this->hasRole(['admin', 'operator'])) {
                return $this->forbidden('Unauthorized access to reference data');
            }

            switch ($this->resolveAction($request)) {
                case 'list':
                    return $this->list($request);
                case 'save':
                    return $this->save($request);
                case 'delete':
                    return $this->delete($request);
                default:
                    return $this->error('Invalid action');
            }
        } catch (Throwable $e) {
            error_log('ReferenceDataController error: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => $this->app->config('app.debug')
                    ? $e->getMessage()
                    : 'Unable to complete that request right now.',
            ], 500);
        }
    }

    protected function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 15)));
        $search = trim((string) $request->query('search', ''));

        $result = $this->isAircraft($request)
            ? $this->aircraft->paginate($page, $perPage, $search)
            : $this->flights->paginate($page, $perPage, $search);

        return $this->json([
            'success' => true,
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
        ]);
    }

    protected function save(Request $request): Response
    {
        if (($guard = $this->guardWrite($request)) !== null) {
            return $guard;
        }

        $userId = $this->userId();

        if ($this->isAircraft($request)) {
            $registration = strtoupper(trim((string) $request->input('registration', '')));
            if ($registration === '') {
                return $this->error('Registration is required.');
            }

            // category is NOT NULL in the schema; fall back rather than blow up.
            $category = trim((string) $request->input('category', ''));

            $this->aircraft->upsert($registration, [
                'aircraft_type' => trim((string) $request->input('aircraft_type', '')),
                'operator_airline' => trim((string) $request->input('operator_airline', '')),
                'category' => $category !== '' ? $category : 'CHARTER',
                'notes' => trim((string) $request->input('notes', '')),
            ]);

            $this->audit->log($userId, 'SAVE_AIRCRAFT_DETAIL', 'aircraft_details', null, ['registration' => $registration]);

            return $this->json(['success' => true, 'message' => 'Aircraft saved.']);
        }

        $flightNo = strtoupper(trim((string) $request->input('flight_no', '')));
        $route = trim((string) $request->input('default_route', ''));

        if ($flightNo === '') {
            return $this->error('Flight number is required.');
        }

        $this->flights->upsert($flightNo, $route);
        $this->audit->log($userId, 'SAVE_FLIGHT_REFERENCE', 'flight_references', null, ['flight_no' => $flightNo]);

        return $this->json(['success' => true, 'message' => 'Flight reference saved.']);
    }

    protected function delete(Request $request): Response
    {
        if (($guard = $this->guardWrite($request)) !== null) {
            return $guard;
        }

        // Matches SnapshotController: operators may add and edit, only admins delete.
        if (!$this->hasRole('admin')) {
            return $this->forbidden('Only administrators can delete reference records');
        }

        $userId = $this->userId();

        if ($this->isAircraft($request)) {
            $registration = strtoupper(trim((string) $request->input('registration', '')));

            if ($registration === '' || !$this->aircraft->deleteByRegistration($registration)) {
                return $this->error('That aircraft record no longer exists.', 404);
            }

            $this->audit->log($userId, 'DELETE_AIRCRAFT_DETAIL', 'aircraft_details', null, null, ['registration' => $registration]);

            return $this->json(['success' => true, 'message' => 'Aircraft deleted.']);
        }

        $id = (int) $request->input('id', 0);

        if ($id <= 0 || !$this->flights->deleteById($id)) {
            return $this->error('That flight reference no longer exists.', 404);
        }

        $this->audit->log($userId, 'DELETE_FLIGHT_REFERENCE', 'flight_references', $id);

        return $this->json(['success' => true, 'message' => 'Flight reference deleted.']);
    }

    protected function isAircraft(Request $request): bool
    {
        $type = strtolower((string) ($request->input('type') ?: $request->query('type', '')));

        return $type !== 'flight';
    }

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
        return $this->json(['success' => false, 'message' => $message], $status);
    }

    protected function forbidden(string $message): Response
    {
        return $this->json(['success' => false, 'message' => $message], 403);
    }
}
