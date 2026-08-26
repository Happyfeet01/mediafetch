<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version00002date20210912 extends SimpleMigrationStep
{
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // A failed beta install may already have recorded migration 00001 while
        // still using the legacy ncdownloader_info table. Be defensive and
        // create the MediaFetch table here as well if it is missing.
        if (!$schema->hasTable('mediafetch_info')) {
            $table = $schema->createTable('mediafetch_info');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 10,
            ]);
            $table->addColumn('uid', 'string', [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('gid', 'string', [
                'notnull' => true,
                'length' => 32,
            ]);
            $table->addColumn('filename', 'string', [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('type', 'smallint', [
                'notnull' => true,
                'length' => 4,
                'default' => 1,
                'comment' => 'Download Type(Aria2 = 1, yt-dlp = 2, Others = 3)',
            ]);
            $table->addColumn('status', 'smallint', [
                'notnull' => true,
                'length' => 1,
                'default' => 1,
            ]);
            $table->addColumn('followedby', 'string', [
                'notnull' => true,
                'length' => 16,
                'default' => '0',
            ]);
            $table->addColumn('timestamp', 'bigint', [
                'notnull' => true,
                'length' => 15,
                'default' => 0,
            ]);
            $table->addColumn('data', 'blob', [
                'notnull' => false,
                'default' => null,
            ]);
            $table->setPrimaryKey(['id']);
        } else {
            $table = $schema->getTable('mediafetch_info');
        }

        if (!$table->hasColumn('speed')) {
            $table->addColumn('speed', 'string', [
                'notnull' => true,
                'length' => 255,
                'default' => 'unknown',
            ]);
        }
        if (!$table->hasColumn('progress')) {
            $table->addColumn('progress', 'string', [
                'notnull' => true,
                'length' => 255,
                'default' => '0',
            ]);
        }
        if (!$table->hasColumn('filesize')) {
            $table->addColumn('filesize', 'string', [
                'notnull' => false,
                'length' => 255,
                'default' => '',
            ]);
        }
        if (!$table->hasIndex('mediafetch_gid_index')) {
            $table->addUniqueIndex(['gid'], 'mediafetch_gid_index');
        }

        return $schema;
    }
}
