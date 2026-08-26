<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version00003date20260826 extends SimpleMigrationStep
{
    public function __construct(private IDBConnection $connection)
    {
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('ncdownloader_info') || !$schema->hasTable('mediafetch_info')) {
            return;
        }

        $query = $this->connection->getQueryBuilder();
        $result = $query
            ->select('*')
            ->from('ncdownloader_info')
            ->executeQuery();

        $migrated = 0;
        while ($row = $result->fetchAssociative()) {
            $record = [
                'uid' => $row['uid'] ?? null,
                'gid' => $row['gid'],
                'filename' => $row['filename'] ?? 'unknown',
                'type' => (int) ($row['type'] ?? 1),
                'status' => (int) ($row['status'] ?? 1),
                'followedby' => (string) ($row['followedby'] ?? '0'),
                'timestamp' => (int) ($row['timestamp'] ?? 0),
                'data' => $row['data'] ?? null,
                'speed' => (string) ($row['speed'] ?? 'unknown'),
                'progress' => (string) ($row['progress'] ?? '0'),
                'filesize' => (string) ($row['filesize'] ?? ''),
            ];

            if ($this->connection->insertIfNotExist('*PREFIX*mediafetch_info', $record, ['gid'])) {
                $migrated++;
            }
        }
        $result->closeCursor();

        if ($migrated > 0) {
            $output->info(sprintf('Migrated %d NCDownloader download record(s) to MediaFetch.', $migrated));
        }
    }
}
