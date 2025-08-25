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
        $this->assertObjectHasProperty('enableValidation', $this->testConfig);
        $this->assertObjectHasProperty('enablePerformanceAnalysis', $this->testConfig);
        $this->assertObjectHasProperty('enableIntelligentCache', $this->testConfig);
        $this->assertObjectHasProperty('enableRelationDetection', $this->testConfig);
        
        // Test new configuration arrays
        $this->assertObjectHasProperty('logging', $this->testConfig);
        $this->assertObjectHasProperty('performance', $this->testConfig);
        $this->assertObjectHasProperty('relationships', $this->testConfig);
        $this->assertObjectHasProperty('validation', $this->testConfig);
        $this->assertObjectHasProperty('advanced', $this->testConfig);
    }

    public function testLoggingConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->logging);
        $this->assertArrayHasKey('enabled', $this->testConfig->logging);
        $this->assertArrayHasKey('level', $this->testConfig->logging);
        $this->assertArrayHasKey('channels', $this->testConfig->logging);
        
        $this->assertFalse($this->testConfig->logging['enabled']);
        $this->assertEquals('info', $this->testConfig->logging['level']);
        
        // In testing environment, channels is ['memory'], otherwise ['file']
        $expectedChannels = ENVIRONMENT === 'testing' ? ['memory'] : ['file'];
        $this->assertEquals($expectedChannels, $this->testConfig->logging['channels']);
    }

    public function testPerformanceConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->performance);
        $this->assertArrayHasKey('enabled', $this->testConfig->performance);
        $this->assertArrayHasKey('analysis_depth', $this->testConfig->performance);
        $this->assertArrayHasKey('score_weights', $this->testConfig->performance);
        
        $this->assertFalse($this->testConfig->performance['enabled']);
        $this->assertEquals('full', $this->testConfig->performance['analysis_depth']);
        $this->assertIsArray($this->testConfig->performance['score_weights']);
    }

    public function testRelationshipsConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->relationships);
        $this->assertArrayHasKey('enabled', $this->testConfig->relationships);
        $this->assertArrayHasKey('detect_polymorphic', $this->testConfig->relationships);
        $this->assertArrayHasKey('naming_conventions', $this->testConfig->relationships);
        
        $this->assertTrue($this->testConfig->relationships['enabled']);
        $this->assertTrue($this->testConfig->relationships['detect_polymorphic']);
        $this->assertIsArray($this->testConfig->relationships['naming_conventions']);
    }

    public function testValidationConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->validation);
        $this->assertArrayHasKey('enabled', $this->testConfig->validation);
        $this->assertArrayHasKey('strict_mode', $this->testConfig->validation);
        $this->assertArrayHasKey('rules', $this->testConfig->validation);
        
        $this->assertFalse($this->testConfig->validation['enabled']);
        $this->assertFalse($this->testConfig->validation['strict_mode']);
        $this->assertIsArray($this->testConfig->validation['rules']);
    }

    public function testAdvancedConfiguration(): void
    {
        $this->assertIsArray($this->testConfig->advanced);
        $this->assertArrayHasKey('schema_versioning', $this->testConfig->advanced);
        $this->assertArrayHasKey('migration_support', $this->testConfig->advanced);
        $this->assertArrayHasKey('backup_schemas', $this->testConfig->advanced);
        
        $this->assertFalse($this->testConfig->advanced['schema_versioning']);
        $this->assertFalse($this->testConfig->advanced['migration_support']);
        $this->assertFalse($this->testConfig->advanced['backup_schemas']);
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

    public function testScoreWeightsAddUpToOne(): void
    {
        $weights = $this->testConfig->performance['score_weights'];
        $total = array_sum($weights);
        
        $this->assertEqualsWithDelta(1.0, $total, 0.001, 'Score weights should add up to 1.0');
    }

    public function testNamingConventionsAreCorrect(): void
    {
        $conventions = $this->testConfig->relationships['naming_conventions'];
        
        $this->assertEquals('_id', $conventions['foreign_key_suffix']);
        $this->assertEquals('{table1}_{table2}', $conventions['pivot_table_pattern']);
        $this->assertEquals('_type', $conventions['polymorphic_type_suffix']);
        $this->assertEquals('_id', $conventions['polymorphic_id_suffix']);
    }
}
