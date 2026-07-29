<?php

namespace App\Services;

use App\Core\Application;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Save / restore point-in-time versions of aircraft_movements.
 *
 * Only aircraft_movements is touched. Users, aircraft details, stands and ML
 * logs are never read or written here.
 *
 * "Wipe the apron clear" is not a separate operation: it is restoreTo(null),
 * i.e. a restore whose target happens to be empty. Same code path, same
 * auto-save-first guarantee.
 */
class MovementVersionService
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function pdo(): PDO
    {
        return $this->app->make(PDO::class);
    }

    /**
     * ponytail: CREATE TABLE IF NOT EXISTS on each admin call instead of a
     * migration file. It is a cheap metadata check, only runs on admin actions,
     * and means there is no migration step to forget when deploying. Move this
     * into a migration if this project ever grows a migration runner.
     */
    protected function ensureTable(): void
    {
        $this->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS movement_versions (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                label VARCHAR(120) NOT NULL,
                is_auto TINYINT(1) NOT NULL DEFAULT 0,
                row_count INT NOT NULL DEFAULT 0,
                payload LONGTEXT NOT NULL,
                created_by BIGINT(20) UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<int, array<string, mixed>> newest first, without payloads
     */
    public function listVersions(): array
    {
        $this->ensureTable();

        $stmt = $this->pdo()->query(
            'SELECT v.id, v.label, v.is_auto, v.row_count, v.created_at, u.username AS created_by_name
             FROM movement_versions v
             LEFT JOIN users u ON u.id = v.created_by
             ORDER BY v.created_at DESC, v.id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveVersion(string $label, ?int $userId, bool $isAuto = false): int
    {
        $label = trim($label);
        if ($label === '') {
            throw new RuntimeException('A label is required.');
        }

        $this->ensureTable();
        $pdo = $this->pdo();

        $rows = $pdo->query('SELECT * FROM aircraft_movements ORDER BY id')->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // ponytail: the whole table is json_encode'd in memory. Fine at ~6k rows
        // (~1.5MB, well under PHP's 256M limit). Stream the save in chunks if
        // aircraft_movements ever passes ~50k rows.
        $payload = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new RuntimeException('Failed to encode movement data.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO movement_versions (label, is_auto, row_count, payload, created_by)
             VALUES (:label, :is_auto, :row_count, :payload, :created_by)'
        );

        $stmt->execute([
            ':label' => mb_substr($label, 0, 120),
            ':is_auto' => $isAuto ? 1 : 0,
            ':row_count' => count($rows),
            ':payload' => $payload,
            ':created_by' => $userId,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Replace every movement row with the given version's rows.
     * Pass null to restore to an empty apron.
     *
     * Always saves the current state first, so every restore is undoable.
     */
    public function restoreTo(?int $versionId, ?int $userId): array
    {
        $this->ensureTable();
        $pdo = $this->pdo();

        $rows = [];
        $label = 'Empty apron';

        if ($versionId !== null) {
            $stmt = $pdo->prepare('SELECT label, payload FROM movement_versions WHERE id = :id');
            $stmt->execute([':id' => $versionId]);
            $version = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$version) {
                throw new RuntimeException('That saved version no longer exists.');
            }

            $label = (string) $version['label'];
            $rows = json_decode((string) $version['payload'], true);

            if (!is_array($rows)) {
                throw new RuntimeException('That version cannot be read (corrupt payload). Nothing was changed.');
            }
        }

        $autoSaveId = $this->saveVersion('Auto-save before restore - ' . date('d M Y H:i'), $userId, true);

        $pdo->beginTransaction();

        try {
            // DELETE, not TRUNCATE: TRUNCATE is DDL in MariaDB and forces an
            // implicit commit, which would break this transaction and leave the
            // table empty if the re-insert below failed.
            $pdo->exec('DELETE FROM aircraft_movements');
            $this->insertRows($pdo, $rows);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();

            throw new RuntimeException('Restore failed and nothing was changed: ' . $e->getMessage(), 0, $e);
        }

        return [
            'restored_label' => $label,
            'row_count' => count($rows),
            'auto_save_id' => $autoSaveId,
        ];
    }

    /**
     * Re-insert saved rows with their original IDs. Safe because nothing in the
     * schema foreign-keys to aircraft_movements.id.
     */
    protected function insertRows(PDO $pdo, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        // Column names come out of stored JSON, so they are checked against the
        // live table before being interpolated into SQL. This also makes a
        // restore survive schema drift: columns dropped since the save are
        // skipped rather than blowing up the whole restore.
        $liveColumns = $pdo->query('SHOW COLUMNS FROM aircraft_movements')->fetchAll(PDO::FETCH_COLUMN);
        $columns = array_values(array_intersect(array_keys($rows[0]), $liveColumns));

        if ($columns === []) {
            throw new RuntimeException('Saved version has no columns matching the current movements table.');
        }

        $columnList = implode(', ', array_map(static fn($c) => '`' . $c . '`', $columns));
        $rowPlaceholder = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';

        // Chunked so a single INSERT never approaches max_allowed_packet (16MB default).
        foreach (array_chunk($rows, 500) as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                foreach ($columns as $column) {
                    $values[] = $row[$column] ?? null;
                }
            }

            $sql = 'INSERT INTO aircraft_movements (' . $columnList . ') VALUES '
                . implode(',', array_fill(0, count($chunk), $rowPlaceholder));

            $pdo->prepare($sql)->execute($values);
        }
    }

    public function deleteVersion(int $id): bool
    {
        $this->ensureTable();

        $stmt = $this->pdo()->prepare('DELETE FROM movement_versions WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
