<?php

declare(strict_types=1);

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Drafter;

use CodeIgniter\Database\BaseConnection;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/** @internal */
final class DatabaseHandlerRelationsTest extends TestCase
{
    public static function fakeConnection(): object
    {
        return new class (['DSN' => '', 'hostname' => '', 'username' => '', 'password' => '', 'database' => '', 'DBDriver' => 'Stub', 'DBPrefix' => 'pre_', 'charset' => '', 'DBCollat' => '', 'swapPre' => '', 'encrypt' => false, 'compress' => false, 'strictOn' => false, 'failSwap' => false, 'port' => null]) extends BaseConnection {
            public function connect($persistent = false)
            {
            }

            public function reconnect()
            {
            }

            public function close()
            {
            }

            protected function _close()
            {
            }

            public function getVersion(): string
            {
                return 'stub';
            }

            public function setDatabase(string $databaseName)
            {
            }

            public function query(string $sql, $binds = null, $setEscapeFlags = true, $queryClass = 'CodeIgniter\\Database\\Query')
            {
                return null;
            }

            protected function execute(string $sql)
            {
            }

            public function affectedRows(): int
            {
                return 0;
            }

            public function error(): array
            {
                return ['code' => 0, 'message' => ''];
            }

            public function insertID()
            {
                return 0;
            }

            protected function _transBegin(): bool
            {
                return true;
            }

            protected function _transCommit(): bool
            {
                return true;
            }

            protected function _transRollback(): bool
            {
                return true;
            }

            protected function _fieldData(string $table): array
            {
                return [];
            }

            protected function _indexData(string $table): array
            {
                return [];
            }

            protected function _foreignKeyData(string $table): array
            {
                return [];
            }

            protected function _listColumns($table = '')
            {
                return [];
            }

            protected function _listTables(bool $constrainByPrefix = false, ?string $tableName = null)
            {
                return [];
            }

            public function getFieldData(string $table): array
            {
                return match ($table) {
                    'users' => [(object) ['name' => 'id', 'primary_key' => 1]], 'groups' => [(object) ['name' => 'id', 'primary_key' => 1]], 'records' => [(object) ['name' => 'id', 'primary_key' => 1], (object) ['name' => 'user_id', 'primary_key' => 0]], 'groups_users' => [(object) ['name' => 'group_id', 'primary_key' => 0], (object) ['name' => 'user_id', 'primary_key' => 0]], 'posts' => [(object) ['name' => 'id', 'primary_key' => 1], (object) ['name' => 'user_id', 'primary_key' => 0]], default => []
                };
            }

            public function getIndexData(string $table): array
            {
                return [];
            }

            public function getForeignKeyData(string $table): array
            {
                if ($table === 'records') {
                    return [(object) ['constraint_name' => 'pre_fk_records_user_id', 'table_name' => 'pre_records', 'column_name' => 'user_id', 'foreign_table_name' => 'pre_users', 'foreign_column_name' => 'id']];
                }

return [];
            }

            public function listTables(bool $constrain = true): array
            {
                return ['pre_users', 'pre_groups', 'pre_records', 'pre_groups_users', 'pre_posts'];
            }

            public function getPrefix(): string
            {
                return 'pre_';
            }
        };
    }

    public function testRelationsFromForeignKeysAndPivot(): void
    {
        $config                = new SchemasConfig();
        $config->ignoredTables = []; // ensure none ignored
        $stub                  = self::fakeConnection();
        $handler               = new DatabaseHandler($config, null);
        // Inject stub connection via reflection since class is final
        $ref = new ReflectionClass($handler);

        foreach (['db', 'prefix'] as $prop) {
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($handler, $prop === 'db' ? $stub : $stub->getPrefix());
            }
        }
        $schema = $handler->draft();

        $this->assertInstanceOf(Schema::class, $schema);
        // BelongsTo relation on records -> users
        $this->assertTrue(isset($schema->tables->records->relations->users));
        $this->assertSame('belongsTo', $schema->tables->records->relations->users->type);
        // Inverse hasMany users -> records
        $this->assertTrue(isset($schema->tables->users->relations->records));
        $this->assertSame('hasMany', $schema->tables->users->relations->records->type);
        // Pivot table flagged
        $this->assertTrue($schema->tables->{'groups_users'}->pivot);
        // groups table exists (prefix stripped)
        $this->assertTrue(isset($schema->tables->groups), 'groups table missing');
        // ManyToMany users <-> groups
        $this->assertTrue(isset($schema->tables->users->relations->groups));
        $this->assertSame('manyToMany', $schema->tables->users->relations->groups->type);
        $this->assertTrue(isset($schema->tables->groups->relations->users));
        $this->assertSame('manyToMany', $schema->tables->groups->relations->users->type);
        // Heuristic relation posts->users (belongsTo) via user_id without FK
        $this->assertTrue(isset($schema->tables->posts->relations->users));
        $this->assertSame('belongsTo', $schema->tables->posts->relations->users->type);
        $this->assertTrue(isset($schema->tables->users->relations->posts));
        $this->assertSame('hasMany', $schema->tables->users->relations->posts->type);

        // stripPrefix effect: no table keys start with pre_
        foreach ($schema->tables as $tName => $_t) {
            $this->assertDoesNotMatchRegularExpression('/^pre_/', $tName, 'Prefix not stripped for ' . $tName);
        }
    }
}
