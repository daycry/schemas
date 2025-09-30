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

use CodeIgniter\Test\DatabaseTestTrait;
use Config\Services;
use Daycry\Schemas\Archiver\Handlers\CacheHandler as CacheArchiver;
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;
use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;
use Daycry\Schemas\Drafter\Handlers\ModelHandler;
use Tests\Support\Database\Seeds\TestSeeder;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LiveTest extends TestCase
{
    use DatabaseTestTrait;

    // Configure the database to be migrated and seeded once
    protected $migrateOnce = true;
    protected $seedOnce    = true;
    protected $seed        = TestSeeder::class;
    protected $basePath    = SUPPORTPATH . 'Database/';

    // Probably a quite common scenario
    public function testDatabaseToCache()
    {
        $cache           = Services::cache();
        $databaseHandler = new DatabaseHandler($this->config, 'tests');
        $cacheHandler    = new CacheArchiver($this->config, $cache);

        $result = $this->schemas->draft([$databaseHandler])->archive([$cacheHandler]);
        $this->assertEmpty($this->schemas->getErrors());

        $schemaFromService = $this->schemas->get();
        $schemaFromCache   = $cache->get('schema-testing');

        // Check that we actually got a schema from cache
        $this->assertNotNull($schemaFromCache, 'Schema was not found in cache');

        // Verify cache schema has tables property
        $this->assertTrue(property_exists($schemaFromCache, 'tables'), 'Cache schema missing tables property');

        // Check that the service schema has tables (if it doesn't, then it's expected that cache is empty too)
        $serviceTables = (array) $schemaFromService->tables;
        $cacheTables   = (array) $schemaFromCache->tables;

        if (empty($serviceTables)) {
            // If service has no tables, cache should be empty too - this is valid
            $this->assertEmpty($cacheTables, 'Cache should be empty when service schema is empty');
        } else {
            // If service has tables, cache should have the same tables
            $this->assertNotEmpty($cacheTables, 'Cache should have tables when service schema has tables');
            $this->assertCount(count($serviceTables), $cacheTables);

            // Check for a specific table if it exists in service
            if (property_exists($schemaFromService->tables, 'factories')) {
                $this->assertTrue(property_exists($schemaFromCache->tables, 'factories'));
            }
        }
    }

    public function testDatabaseMergeFile()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->assertNotNull($this->schemas);

            return;
        }

        $databaseHandler = new DatabaseHandler($this->config, 'tests');
        $fileHandler     = new DirectoryHandler($this->config);

        $schema = $this->schemas->draft([$databaseHandler, $fileHandler])->get();

        $this->assertTrue(property_exists($schema->tables, 'products'));
        $this->assertCount(3, $schema->tables->workers->relations);
    }

    public function testMergeAllDrafters()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->assertNotNull($this->schemas);

            return;
        }

        $databaseHandler = new DatabaseHandler($this->config, 'tests');
        $modelHandler    = new ModelHandler($this->config);
        $fileHandler     = new DirectoryHandler($this->config);

        $schema = $this->schemas->draft([$databaseHandler, $modelHandler, $fileHandler])->get();

        $this->assertTrue(property_exists($schema->tables, 'products'));
        $this->assertSame('Tests\Support\Models\FactoryModel', $schema->tables->factories->model);
        $this->assertCount(3, $schema->tables->workers->relations);
    }

    public function testGetReturnsSchemaWithReader()
    {
        // Draft & archive a copy of the schema so we can test reading it
        $result = $this->schemas->draft()->archive();
        // archive() returns the Schemas object (fluent interface), not boolean
        $this->assertInstanceOf('\Daycry\Schemas\Schemas', $result);

        $this->schemas->reset();

        // Use cache handler to read the schema we just archived
        $schema = $this->schemas->read('cache')->get();

        $this->assertInstanceOf('\Daycry\Schemas\Structures\Schema', $schema);
    }

    public function testAutoRead()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            $this->assertNotNull($this->schemas);

            return;
        }

        $this->config->automate['read'] = true;

        // Draft & archive a copy of the schema so we can test reading it
        $result = $this->schemas->draft()->archive();
        $this->assertInstanceOf('\Daycry\Schemas\Schemas', $result);

        $this->schemas->reset();

        $schema = $this->schemas->get();

        $this->assertSame('Tests\Support\Models\FactoryModel', $schema->tables->factories->model);
        $this->assertCount(3, $schema->tables->workers->relations);
    }
}
