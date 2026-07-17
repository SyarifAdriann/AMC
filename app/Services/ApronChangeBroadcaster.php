<?php

namespace App\Services;

use App\Core\Application;
use Throwable;

/**
 * Tracks a monotonically increasing "apron data version" in a small file.
 *
 * Every movement write bumps the version; the SSE endpoint (/api/apron/stream)
 * watches the file and pushes an `apron-update` event to every connected tab,
 * so an edit in one tab (or on another machine) appears everywhere without a
 * manual refresh. File-based on purpose: works on shared hosting, XAMPP and
 * Docker without any extra daemon or extension.
 */
class ApronChangeBroadcaster
{
    protected string $versionFile;

    public function __construct(Application $app)
    {
        $this->versionFile = $app->basePath('storage/cache/apron_version.json');
    }

    /**
     * Record that apron data changed. Never throws — broadcasting is
     * best-effort and must not break the write that triggered it.
     */
    public function bump(string $source = ''): void
    {
        try {
            $dir = dirname($this->versionFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $current = $this->currentVersion();

            $payload = json_encode([
                'version' => $current + 1,
                'updated_at' => date(DATE_ATOM),
                'source' => $source,
            ]);

            @file_put_contents($this->versionFile, $payload, LOCK_EX);
        } catch (Throwable $e) {
            error_log('ApronChangeBroadcaster::bump warning: ' . $e->getMessage());
        }
    }

    public function currentVersion(): int
    {
        try {
            if (!is_file($this->versionFile)) {
                return 0;
            }

            $raw = @file_get_contents($this->versionFile);
            if ($raw === false || $raw === '') {
                return 0;
            }

            $data = json_decode($raw, true);

            return is_array($data) ? (int) ($data['version'] ?? 0) : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
