<?php

namespace Tests;

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Tests\Support\TestCase;

class ConfigurationTest extends TestCase
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
        
        // In testing environment, TTL is set to 60, otherwise 3600
        $expectedTtl = ENVIRONMENT === 'testing' ? 60 : 3600;
        $this->assertEquals($expectedTtl, $this->testConfig->cache['ttl']);
        
        $this->assertEquals('file', $this->testConfig->cache['handler']);
        $this->assertEquals('schemas_', $this->testConfig->cache['prefix']);
    }

    public function testDeprecatedTtlPropertyDoesNotExist(): void
    {
        $this->assertFalse(property_exists($this->testConfig, 'ttl'), 
            'The deprecated $ttl property should not exist');
    }

    public function testNewConfigurationOptionsExist(): void
    {
        // Test new configuration options
        $this->assertObjectHasProperty('defaultGroup', $this->testConfig);
        
        // Test new configuration arrays
        $this->assertObjectHasProperty('logging', $this->testConfig);
        $this->assertObjectHasProperty('relationships', $this->testConfig);
        $this->assertObjectHasProperty('validation', $this->testConfig);
        $this->assertObjectHasProperty('development', $this->testConfig);
    }

    public function testLoggingConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->logging);
        $this->assertArrayHasKey('enabled', $this->testConfig->logging);
        $this->assertArrayHasKey('level', $this->testConfig->logging);
        
        $this->assertFalse($this->testConfig->logging['enabled']);
        $this->assertEquals('info', $this->testConfig->logging['level']);
    }

    public function testRelationshipsConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->relationships);
        $this->assertArrayHasKey('enabled', $this->testConfig->relationships);
        $this->assertArrayHasKey('detect_polymorphic', $this->testConfig->relationships);
        $this->assertArrayHasKey('detect_many_to_many', $this->testConfig->relationships);
        
        $this->assertTrue($this->testConfig->relationships['enabled']);
        $this->assertTrue($this->testConfig->relationships['detect_polymorphic']);
        $this->assertTrue($this->testConfig->relationships['detect_many_to_many']);
    }

    public function testValidationConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->validation);
        $this->assertArrayHasKey('enabled', $this->testConfig->validation);
        $this->assertArrayHasKey('strict_mode', $this->testConfig->validation);
        
        $this->assertFalse($this->testConfig->validation['enabled']);
        $this->assertFalse($this->testConfig->validation['strict_mode']);
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
