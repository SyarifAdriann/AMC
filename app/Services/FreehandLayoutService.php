<?php

namespace App\Services;

use App\Core\Application;
use Throwable;

/**
 * Freehand apron positioning — event-mode icon layout.
 *
 * When active for a view, operators can drag plane icons anywhere on the
 * apron map (e.g. aircraft parked in ad-hoc spots during an airport event).
 * Positions are shared: stored server-side and pushed to every open map via
 * the existing SSE change stream. Deactivating clears the layout so icons
 * snap back to their stands. Views A and B keep independent states.
 */
class FreehandLayoutService
{
    public const VIEWS = ['a', 'b', 'c'];

    protected string $file;

    public function __construct(Application $app)
    {
        $this->file = $app->basePath('storage/cache/freehand_layout.json');
    }

    /**
     * @return array{a: array{active: bool, positions: array}, b: array{active: bool, positions: array}}
     */
    public function all(): array
    {
        $state = [];
        foreach (self::VIEWS as $view) {
            $state[$view] = ['active' => false, 'positions' => []];
        }

        try {
            if (is_file($this->file)) {
                $raw = (string) file_get_contents($this->file);
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    foreach (self::VIEWS as $view) {
                        if (isset($data[$view]) && is_array($data[$view])) {
                            $state[$view]['active'] = !empty($data[$view]['active']);
                            $positions = $data[$view]['positions'] ?? [];
                            $state[$view]['positions'] = is_array($positions) ? $positions : [];
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('FreehandLayoutService::all warning: ' . $e->getMessage());
        }

        return $state;
    }

    public function activate(string $view): void
    {
        $state = $this->all();
        $view = $this->normalizeView($view);
        $state[$view]['active'] = true;
        $this->write($state);
    }

    /**
     * Deactivating discards the layout — icons snap back to their stands.
     */
    public function deactivate(string $view): void
    {
        $state = $this->all();
        $view = $this->normalizeView($view);
        $state[$view] = ['active' => false, 'positions' => []];
        $this->write($state);
    }

    /**
     * Merge dragged icon positions into the view's layout.
     *
     * @param array<int|string, array{x: mixed, y: mixed}> $positions keyed by movement id
     */
    public function setPositions(string $view, array $positions): void
    {
        $state = $this->all();
        $view = $this->normalizeView($view);

        if (!$state[$view]['active']) {
            return; // stale request after deactivation — ignore
        }

        foreach ($positions as $movementId => $pos) {
            $id = (int) $movementId;
            if ($id <= 0 || !is_array($pos)) {
                continue;
            }
            // Coordinates live in the map's unscaled 1920x1080 space;
            // clamp with margin so icons can sit at the apron edges.
            $x = max(-100.0, min(2020.0, (float) ($pos['x'] ?? 0)));
            $y = max(-100.0, min(1180.0, (float) ($pos['y'] ?? 0)));
            $state[$view]['positions'][(string) $id] = ['x' => round($x, 1), 'y' => round($y, 1)];
        }

        $this->write($state);
    }

    protected function normalizeView(string $view): string
    {
        $view = strtolower(trim($view));

        return in_array($view, self::VIEWS, true) ? $view : 'a';
    }

    protected function write(array $state): void
    {
        try {
            $dir = dirname($this->file);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            @file_put_contents($this->file, json_encode($state), LOCK_EX);
        } catch (Throwable $e) {
            error_log('FreehandLayoutService::write warning: ' . $e->getMessage());
        }
    }
}
