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

use Daycry\Schemas\Cache\IntelligentCacheManager;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class IntelligentCacheManagerTest extends TestCase
{
    protected IntelligentCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheManager = new IntelligentCacheManager();
        
        // Clean cache before each test
        cache()->clean();
    }

    public function testStoreAndRetrieveSchema(): void
    {
        $schema = $this->createTestSchema();
        $key = 'test_schema';
        
        $stored = $this->cacheManager->store($key, $schema, 3600);
        $this->assertTrue($stored);
        
        $retrieved = $this->cacheManager->get($key);
        $this->assertInstanceOf(Schema::class, $retrieved);
    }

    public function testVersioning(): void
    {
        $schema1 = $this->createTestSchema('table1');
        $schema2 = $this->createTestSchema('table2');
        $key = 'versioned_schema';
        
        // Store version 1
        $this->cacheManager->store($key, $schema1, 3600);
        $version1 = $this->cacheManager->get($key);
        
        // Store version 2
        $this->cacheManager->store($key, $schema2, 3600);
        $version2 = $this->cacheManager->get($key);
        
        // Should get latest version by default
        $this->assertNotEquals($version1, $version2);
        
        // Should be able to get specific version
        $retrievedV1 = $this->cacheManager->getVersion($key, 1);
        $this->assertInstanceOf(Schema::class, $retrievedV1);
    }

    public function testCacheExists(): void
    {
        $schema = $this->createTestSchema();
        $key = 'existence_test';
        
        $this->assertFalse($this->cacheManager->exists($key));
        
        $this->cacheManager->store($key, $schema, 3600);
        $this->assertTrue($this->cacheManager->exists($key));
    }

    public function testInvalidation(): void
    {
        $schema = $this->createTestSchema();
        $key = 'invalidation_test';
        
        $this->cacheManager->store($key, $schema, 3600);
        $this->assertTrue($this->cacheManager->exists($key));
        
        $this->cacheManager->invalidate($key);
        $this->assertFalse($this->cacheManager->exists($key));
    }

    public function testTagging(): void
    {
        $schema1 = $this->createTestSchema('table1');
        $schema2 = $this->createTestSchema('table2');
        
        $this->cacheManager->store('schema1', $schema1, 3600);
        $this->cacheManager->store('schema2', $schema2, 3600);
        
        $this->cacheManager->tag('schema1', ['users', 'database']);
        $this->cacheManager->tag('schema2', ['posts', 'database']);
        
        // Both should exist
        $this->assertTrue($this->cacheManager->exists('schema1'));
        $this->assertTrue($this->cacheManager->exists('schema2'));
        
        // Invalidate by tag should remove both
        $this->cacheManager->invalidateByTag('database');
        
        $this->assertFalse($this->cacheManager->exists('schema1'));
        $this->assertFalse($this->cacheManager->exists('schema2'));
    }

    public function testMetadata(): void
    {
        $schema = $this->createTestSchema();
        $key = 'metadata_test';
        $metadata = [
            'source' => 'database',
            'table_count' => 5,
            'last_modified' => time()
        ];
        
        $this->cacheManager->store($key, $schema, 3600, $metadata);
        
        $retrievedMetadata = $this->cacheManager->getMetadata($key);
        $this->assertIsArray($retrievedMetadata);
        $this->assertEquals('database', $retrievedMetadata['source']);
        $this->assertEquals(5, $retrievedMetadata['table_count']);
        $this->assertArrayHasKey('timestamp', $retrievedMetadata);
        $this->assertArrayHasKey('version', $retrievedMetadata);
    }

    public function testExpiration(): void
    {
        $schema = $this->createTestSchema();
        $key = 'expiration_test';
        
        // Store with past timestamp metadata
        $pastMetadata = [
            'timestamp' => time() - 7200, // 2 hours ago
            'ttl' => 3600 // 1 hour TTL
        ];
        
        $this->cacheManager->store($key, $schema, 3600, $pastMetadata);
        
        // Manually override metadata to simulate expiration
        $expiredMetadata = [
            'timestamp' => time() - 7200,
            'ttl' => 3600,
            'version' => 1
        ];
        cache()->save('daycry_schemas_' . $key . '_metadata', $expiredMetadata, 3600);
        
        // Should be expired
        $this->assertTrue($this->cacheManager->hasExpired($key));
    }

    public function testDatabaseChangeDetection(): void
    {
        $schema = $this->createTestSchema();
        $key = 'db_change_test';
        
        $oldDbInfo = [
            'table_count' => 5,
            'last_modified' => time() - 3600
        ];
        
        $newDbInfo = [
            'table_count' => 6, // Changed!
            'last_modified' => time()
        ];
        
        $this->cacheManager->store($key, $schema, 3600, ['db_info' => $oldDbInfo]);
        
        // Should detect change
        $this->assertTrue($this->cacheManager->hasExpired($key, $newDbInfo));
    }

    public function testVersionsList(): void
    {
        $schema1 = $this->createTestSchema('v1');
        $schema2 = $this->createTestSchema('v2');
        $schema3 = $this->createTestSchema('v3');
        $key = 'versions_test';
        
        $this->cacheManager->store($key, $schema1, 3600);
        $this->cacheManager->store($key, $schema2, 3600);
        $this->cacheManager->store($key, $schema3, 3600);
        
        $versions = $this->cacheManager->getVersions($key);
        $this->assertIsArray($versions);
        $this->assertContains(3, $versions); // Latest version
        $this->assertContains(2, $versions);
        $this->assertContains(1, $versions);
    }

    public function testDifferentialStorage(): void
    {
        $changedTables = ['users', 'posts'];
        $key = 'differential_test';
        
        $stored = $this->cacheManager->storeDifferential($key, $changedTables, 3600);
        $this->assertTrue($stored);
    }

    protected function createTestSchema(string $tableName = 'test_table'): Schema
    {
        $schema = new Schema();
        $table = new Table($tableName);
        $schema->tables->{$tableName} = $table;
        
        return $schema;
    }
}
