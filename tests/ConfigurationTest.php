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

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    protected SchemasConfig $testConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testConfig = new SchemasConfig();
    }

    public function testCacheConfigurationExists(): void
    {
        $this->assertIsArray($this->testConfig->cache);
        $this->assertArrayHasKey('enabled', $this->testConfig->cache);
        $this->assertArrayHasKey('ttl', $this->testConfig->cache);
        $this->assertArrayHasKey('handler', $this->testConfig->cache);
        $this->assertArrayHasKey('prefix', $this->testConfig->cache);
    }

    public function testCacheConfigurationDefaults(): void
    {
        $this->assertFalse($this->testConfig->cache['enabled']);
        // TTL is constant now (environment-specific override removed)
        $this->assertSame(3600, $this->testConfig->cache['ttl']);

        $this->assertSame('file', $this->testConfig->cache['handler']);
        $this->assertSame('schemas_', $this->testConfig->cache['prefix']);
    }

    public function testDeprecatedTtlPropertyDoesNotExist(): void
    {
        $this->assertFalse(
            property_exists($this->testConfig, 'ttl'),
            'The deprecated $ttl property should not exist',
        );
    }

    public function testNewConfigurationOptionsExist(): void
    {
        // Test new configuration options
        $this->assertObjectHasProperty('defaultGroup', $this->testConfig);
        $this->assertObjectHasProperty('relationships', $this->testConfig);
        $this->assertObjectHasProperty('development', $this->testConfig);
    }

    public function testRelationshipsConfiguration(): void
    {
        $this->assertIsBool($this->testConfig->relationships);
        $this->assertTrue($this->testConfig->relationships);
    }

    public function testCacheConfigShape(): void
    {
        $c = $this->testConfig->cache;
        $this->assertArrayHasKey('enabled', $c);
        $this->assertArrayHasKey('ttl', $c);
        $this->assertArrayHasKey('prefix', $c);
        $this->assertIsBool($c['enabled']);
        $this->assertIsInt($c['ttl']);
        $this->assertIsString($c['prefix']);
    }

    public function testDevelopmentConfigShape(): void
    {
        $d = $this->testConfig->development;
        $this->assertArrayHasKey('schema_diff_tool', $d);
        $this->assertArrayHasKey('migration_generator', $d);
        $this->assertIsBool($d['schema_diff_tool']);
        $this->assertIsBool($d['migration_generator']);
    }

    public function testLegacyConfigurationStillExists(): void
    {
        // Verify legacy properties still exist for backward compatibility
        $this->assertObjectHasProperty('automate', $this->testConfig);
        $this->assertObjectHasProperty('readHandler', $this->testConfig);
        $this->assertObjectHasProperty('draftHandlers', $this->testConfig);
        $this->assertObjectHasProperty('archiveHandlers', $this->testConfig);
        $this->assertObjectHasProperty('schemasDirectory', $this->testConfig);
        $this->assertObjectHasProperty('ignoredNamespaces', $this->testConfig);
    }
}
