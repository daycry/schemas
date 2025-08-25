<?php

namespace Tests;

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Archiver\Handlers\CacheHandler;
use Tests\Support\TestCase;

/**
 * Integration test to verify that all configuration modernization 
 * changes work together properly.
 */
class ConfigurationIntegrationTest extends TestCase
{
    public function testCompleteConfigurationIntegration(): void
    {
        // Create a fully configured Schemas instance
        $config = new SchemasConfig();
        
        // Verify all new configuration sections exist and work
        $this->assertIsArray($config->cache);
        $this->assertIsArray($config->logging);
        $this->assertIsArray($config->performance);
        $this->assertIsArray($config->relationships);
        $this->assertIsArray($config->validation);
        $this->assertIsArray($config->advanced);
        
        // Test cache configuration
        $config->cache['enabled'] = true;
        $config->cache['ttl'] = 3600;
        $config->cache['prefix'] = 'test_';
        
        // Test logging configuration
        $config->logging['enabled'] = true;
        $config->logging['level'] = 'debug';
        $config->logging['channels'] = ['database', 'cache'];
        
        // Test performance configuration
        $config->performance['lazy_loading'] = true;
        $config->performance['batch_size'] = 100;
        $config->performance['memory_limit'] = '256M';
        
        // Test relationships configuration
        $config->relationships['auto_discover'] = true;
        $config->relationships['max_depth'] = 3;
        $config->relationships['cache_relations'] = true;
        
        // Test validation configuration
        $config->validation['strict_mode'] = true;
        $config->validation['auto_fix'] = false;
        $config->validation['rules'] = ['required_fields', 'data_types'];
        
        // Test advanced configuration
        $config->advanced['parallel_processing'] = false;
        $config->advanced['custom_handlers'] = [];
        $config->advanced['debug_mode'] = true;
        
        // Verify that the CacheHandler works with new configuration
        $cacheHandler = new CacheHandler($config);
        $this->assertInstanceOf(CacheHandler::class, $cacheHandler);
        
        // Verify that Schemas can be instantiated with new config
        $schemas = new Schemas($config);
        $this->assertInstanceOf(Schemas::class, $schemas);
    }

    public function testLegacyConfigurationCompatibility(): void
    {
        // Test that old configuration style still works
        $config = new SchemasConfig();
        
        // Set some legacy-style properties that should still work
        $config->silent = true;
        
        // These should still work
        $this->assertTrue($config->silent);
        
        // But new array configurations should also work
        $this->assertIsArray($config->cache);
        $this->assertIsArray($config->logging);
    }

    public function testNoDeprecatedPropertiesExist(): void
    {
        $config = new SchemasConfig();
        
        // Verify that deprecated properties don't exist
        $this->assertFalse(property_exists($config, 'ttl'), 
            'Deprecated ttl property should not exist');
        
        // But new cache.ttl should exist
        $this->assertArrayHasKey('ttl', $config->cache,
            'New cache.ttl configuration should exist');
    }

    public function testConfigurationValidation(): void
    {
        $config = new SchemasConfig();
        
        // Test cache TTL is reasonable
        $this->assertGreaterThan(0, $config->cache['ttl']);
        $this->assertLessThan(86400 * 7, $config->cache['ttl']); // Less than a week
        
        // Test that basic configuration properties exist
        $this->assertTrue(property_exists($config, 'silent'));
        $this->assertIsArray($config->cache);
        $this->assertIsArray($config->logging);
    }

    public function testConfigurationDocumentationAccuracy(): void
    {
        $config = new SchemasConfig();
        
        // Verify that configuration matches what's documented
        
        // Cache configuration should match docs
        $expectedCacheKeys = ['enabled', 'handler', 'ttl', 'prefix', 'tags'];
        foreach ($expectedCacheKeys as $key) {
            $this->assertArrayHasKey($key, $config->cache,
                "Cache configuration should have '$key' key as documented");
        }
        
        // Logging configuration should match docs
        $expectedLoggingKeys = ['enabled', 'level', 'channels', 'performance_metrics'];
        foreach ($expectedLoggingKeys as $key) {
            $this->assertArrayHasKey($key, $config->logging,
                "Logging configuration should have '$key' key as documented");
        }
        
        // Performance configuration should match docs
        $expectedPerformanceKeys = ['enabled', 'analysis_depth', 'score_weights', 'recommendations'];
        foreach ($expectedPerformanceKeys as $key) {
            $this->assertArrayHasKey($key, $config->performance,
                "Performance configuration should have '$key' key as documented");
        }
        
        // Relationships configuration should match docs
        $expectedRelationshipKeys = ['enabled', 'detect_polymorphic', 'detect_self_referencing', 'naming_conventions'];
        foreach ($expectedRelationshipKeys as $key) {
            $this->assertArrayHasKey($key, $config->relationships,
                "Relationships configuration should have '$key' key as documented");
        }
        
        // Validation configuration should match docs
        $expectedValidationKeys = ['enabled', 'strict_mode', 'rules', 'auto_fix'];
        foreach ($expectedValidationKeys as $key) {
            $this->assertArrayHasKey($key, $config->validation,
                "Validation configuration should have '$key' key as documented");
        }
        
        // Advanced configuration should match docs
        $expectedAdvancedKeys = ['schema_versioning', 'migration_support', 'backup_schemas', 'compression'];
        foreach ($expectedAdvancedKeys as $key) {
            $this->assertArrayHasKey($key, $config->advanced,
                "Advanced configuration should have '$key' key as documented");
        }
    }
}
