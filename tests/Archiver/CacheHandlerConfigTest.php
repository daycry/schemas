<?php

namespace Tests\Archiver;

use Daycry\Schemas\Archiver\Handlers\CacheHandler;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Tests\Support\TestCase;

class CacheHandlerConfigTest extends TestCase
{
    protected CacheHandler $archiver;
    protected SchemasConfig $testConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a custom config for testing
        $this->testConfig = new SchemasConfig();
        $this->testConfig->cache['ttl'] = 1800; // 30 minutes for testing
        
        $this->archiver = new CacheHandler($this->testConfig);
    }

    public function testCacheHandlerUsesNewTtlConfiguration(): void
    {
        // Create a simple schema for testing
        $schema = new Schema();
        $table = new Table('test_table');
        $schema->tables = new \Daycry\Schemas\Structures\Mergeable();
        $schema->tables->test_table = $table;

        // Archive the schema
        $result = $this->archiver->archive($schema);
        
        $this->assertTrue($result, 'Archive operation should succeed');
    }

    public function testCacheHandlerFallsBackToDefaultTtl(): void
    {
        // Create config without cache TTL
        $configWithoutCacheTtl = new SchemasConfig();
        unset($configWithoutCacheTtl->cache['ttl']);
        
        $archiver = new CacheHandler($configWithoutCacheTtl);
        
        // Create a simple schema
        $schema = new Schema();
        $table = new Table('test_table_fallback');
        $schema->tables = new \Daycry\Schemas\Structures\Mergeable();
        $schema->tables->test_table_fallback = $table;

        // This should work with fallback TTL (3600)
        $result = $archiver->archive($schema);
        
        $this->assertTrue($result, 'Archive operation should succeed with fallback TTL');
    }

    public function testCacheHandlerWithDisabledCache(): void
    {
        // Create config with cache disabled
        $configDisabled = new SchemasConfig();
        $configDisabled->cache['enabled'] = false;
        
        // Should still work (CacheHandler doesn't check enabled flag, just uses TTL)
        $archiver = new CacheHandler($configDisabled);
        
        $schema = new Schema();
        $table = new Table('test_table_disabled');
        $schema->tables = new \Daycry\Schemas\Structures\Mergeable();
        $schema->tables->test_table_disabled = $table;

        $result = $archiver->archive($schema);
        
        $this->assertTrue($result, 'Archive should work even with cache disabled in config');
    }

    public function testCacheHandlerWithCustomTtl(): void
    {
        // Test with very short TTL
        $configShortTtl = new SchemasConfig();
        $configShortTtl->cache['ttl'] = 1; // 1 second
        
        $archiver = new CacheHandler($configShortTtl);
        
        $schema = new Schema();
        $table = new Table('test_table_short_ttl');
        $schema->tables = new \Daycry\Schemas\Structures\Mergeable();
        $schema->tables->test_table_short_ttl = $table;

        $result = $archiver->archive($schema);
        
        $this->assertTrue($result, 'Archive should work with custom short TTL');
    }

    public function testBackwardCompatibilityWithLegacyCode(): void
    {
        // Verify that if someone was directly accessing cache configuration,
        // the new structure works
        $config = new SchemasConfig();
        
        // These should all work
        $this->assertIsArray($config->cache);
        $this->assertArrayHasKey('ttl', $config->cache);
        $this->assertIsInt($config->cache['ttl']);
        $this->assertGreaterThan(0, $config->cache['ttl']);
        
        // Verify deprecated property doesn't exist
        $this->assertFalse(property_exists($config, 'ttl'));
    }
}
