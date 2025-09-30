<?php

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests;

use Daycry\Schemas\Archiver\Handlers\CacheHandler;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Schemas;
use Tests\Support\TestCase;

/**
 * Integration test to verify that all configuration modernization
 * changes work together properly.
 *
 * @internal
 */
final class ConfigurationIntegrationTest extends TestCase
{
    public function testCompleteConfigurationIntegration(): void
    {
        // Create a fully configured Schemas instance
        $config = new SchemasConfig();

        // Verify all new configuration sections exist and work
        $this->assertIsArray($config->cache);
        $this->assertIsBool($config->relationships);
        $this->assertIsArray($config->development);

        // Test cache configuration
        $config->cache['enabled'] = true;
        $config->cache['ttl']     = 3600;
        $config->cache['prefix']  = 'test_';

        // Test relationships configuration
        // Relationships now a simple boolean
        $this->assertTrue($config->relationships);

        // Test development configuration
        $config->development['schema_diff_tool']    = true;
        $config->development['migration_generator'] = false;

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
        // Relationships now a simple boolean flag (no detailed keys to assert)
        $this->assertFalse(
            property_exists($config, 'ttl'),
            'Deprecated ttl property should not exist',
        );

        // But new cache.ttl should exist
        $this->assertArrayHasKey(
            'ttl',
            $config->cache,
            'New cache.ttl configuration should exist',
        );
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
    }

    public function testConfigurationDocumentationAccuracy(): void
    {
        $config = new SchemasConfig();

        // Verify that configuration matches what's documented

        // Cache configuration should match docs
        $expectedCacheKeys = ['enabled', 'handler', 'ttl', 'prefix'];

        foreach ($expectedCacheKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $config->cache,
                "Cache configuration should have '{$key}' key as documented",
            );
        }

        // Relationships configuration should match docs

        // Validation configuration should match docs

        // Development configuration should match docs
        $expectedDevelopmentKeys = ['schema_diff_tool', 'migration_generator'];

        foreach ($expectedDevelopmentKeys as $key) {
            $this->assertArrayHasKey(
                $key,
                $config->development,
                "Development configuration should have '{$key}' key as documented",
            );
        }
    }
}
