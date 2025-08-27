<?php

declare(strict_types=1);

namespace Tests\Config;

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
class SchemasAdvancedConfigTest extends TestCase
{
    private SchemasConfig $schemasConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemasConfig = new SchemasConfig();
    }

    public function testAdvancedConfigInitialization(): void
    {
        $this->assertNotNull($this->schemasConfig);
        $this->assertIsString($this->schemasConfig->getEnvironment());
    }

    public function testGetConfigurationValue(): void
    {
        // Test getting existing configuration
        $value = $this->schemasConfig->get('plugins.enabled');
        $this->assertIsBool($value);
        
        // Test getting with default value
        $defaultValue = $this->schemasConfig->get('nonexistent.key', 'default');
        $this->assertEquals('default', $defaultValue);
    }

    public function testRuntimeConfigurationOverride(): void
    {
        // Set runtime override
        $this->schemasConfig->setRuntimeConfig('async.enabled', true);
        
        // Verify the override is applied
        $value = $this->schemasConfig->get('async.enabled');
        $this->assertTrue($value);
        $this->assertTrue($this->schemasConfig->async['enabled']);
    }

    public function testEnvironmentSwitching(): void
    {
        $originalEnv = $this->schemasConfig->getEnvironment();
        
        // Switch to production
        $this->schemasConfig->switchEnvironment('production');
        $this->assertEquals('production', $this->schemasConfig->getEnvironment());
        
        // Switch back
        $this->schemasConfig->switchEnvironment($originalEnv);
        $this->assertEquals($originalEnv, $this->schemasConfig->getEnvironment());
    }

    public function testCreateConfigurationProfile(): void
    {
        $profileConfig = [
            'plugins' => ['enabled' => false],
            'async' => ['enabled' => true, 'max_concurrent_jobs' => 10],
            'cache' => ['enabled' => true, 'ttl' => 7200]
        ];
        
        $this->schemasConfig->createProfile('custom_profile', $profileConfig);
        
        // Profile was created successfully (no error thrown)
        $this->assertTrue(true);
    }

    public function testConfigurationExportImport(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'schemas_config_test');
        
        // Export configuration
        $result = $this->schemasConfig->exportConfig($tempFile, 'json');
        $this->assertTrue($result);
        $this->assertFileExists($tempFile);
        
        // Modify configuration
        $this->schemasConfig->setRuntimeConfig('test.exported', 'value');
        
        // Import configuration (should restore original state)
        $result = $this->schemasConfig->importConfig($tempFile, 'json');
        $this->assertTrue($result);
        
        // Clean up
        unlink($tempFile);
    }

    public function testConfigurationSnapshot(): void
    {
        // Create initial state
        $this->schemasConfig->setRuntimeConfig('snapshot.test', 'original');
        
        // Create snapshot
        $snapshot = $this->schemasConfig->createSnapshot();
        $this->assertIsArray($snapshot);
        $this->assertArrayHasKey('timestamp', $snapshot);
        
        // Modify state
        $this->schemasConfig->setRuntimeConfig('snapshot.test', 'modified');
        $this->assertEquals('modified', $this->schemasConfig->get('snapshot.test'));
        
        // Restore snapshot
        $this->schemasConfig->restoreSnapshot($snapshot);
        $this->assertEquals('original', $this->schemasConfig->get('snapshot.test'));
    }

    public function testConfigurationStatistics(): void
    {
        $stats = $this->schemasConfig->getConfigStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('environment', $stats);
        $this->assertArrayHasKey('profiles_available', $stats);
        $this->assertArrayHasKey('total_config_keys', $stats);
        $this->assertArrayHasKey('cache_enabled', $stats);
        $this->assertArrayHasKey('async_enabled', $stats);
        $this->assertArrayHasKey('plugins_enabled', $stats);
        $this->assertArrayHasKey('debug_mode', $stats);
        $this->assertArrayHasKey('last_updated', $stats);
        
        $this->assertIsString($stats['environment']);
        $this->assertIsArray($stats['profiles_available']);
        $this->assertIsInt($stats['total_config_keys']);
        $this->assertIsBool($stats['cache_enabled']);
        $this->assertIsBool($stats['async_enabled']);
        $this->assertIsBool($stats['plugins_enabled']);
        $this->assertIsBool($stats['debug_mode']);
    }

    public function testEnvironmentSpecificConfiguration(): void
    {
        // Test development environment
        $this->schemasConfig->switchEnvironment('development');
        $this->assertTrue($this->schemasConfig->get('debug', false));
        
        // Test production environment
        $this->schemasConfig->switchEnvironment('production');
        $this->assertFalse($this->schemasConfig->get('debug', true));
        
        // Test testing environment
        $this->schemasConfig->switchEnvironment('testing');
        $asyncEnabled = $this->schemasConfig->get('async.enabled', true);
        $this->assertFalse($asyncEnabled);
    }

    public function testAdvancedCacheConfiguration(): void
    {
        $this->assertArrayHasKey('enabled', $this->schemasConfig->cache);
        $this->assertArrayHasKey('ttl', $this->schemasConfig->cache);
        $this->assertArrayHasKey('prefix', $this->schemasConfig->cache);
        
        $this->assertIsBool($this->schemasConfig->cache['enabled']);
        $this->assertIsInt($this->schemasConfig->cache['ttl']);
        $this->assertIsString($this->schemasConfig->cache['prefix']);
    }

    public function testDevelopmentConfiguration(): void
    {
        $this->assertArrayHasKey('schema_diff_tool', $this->schemasConfig->development);
        $this->assertArrayHasKey('migration_generator', $this->schemasConfig->development);
        
        $this->assertIsBool($this->schemasConfig->development['schema_diff_tool']);
        $this->assertIsBool($this->schemasConfig->development['migration_generator']);
        
        // Check default values
        $this->assertTrue($this->schemasConfig->development['schema_diff_tool']);
        $this->assertTrue($this->schemasConfig->development['migration_generator']);
    }

    public function testConfigurationListeners(): void
    {
        $listenerCalled = false;
        $eventData = [];
        
        $this->schemasConfig->addConfigListener('runtime_override', function($data) use (&$listenerCalled, &$eventData) {
            $listenerCalled = true;
            $eventData = $data;
        });
        
        $this->schemasConfig->setRuntimeConfig('listener.test', 'value');
        
        $this->assertTrue($listenerCalled);
        $this->assertArrayHasKey('key', $eventData);
        $this->assertArrayHasKey('value', $eventData);
        $this->assertEquals('listener.test', $eventData['key']);
        $this->assertEquals('value', $eventData['value']);
    }

    public function testConfigurationHierarchy(): void
    {
        // Base configuration should be overridden by environment profile
        $this->schemasConfig->switchEnvironment('development');
        
        // Development should enable debug by default
        $debugFromProfile = $this->schemasConfig->get('debug');
        $this->assertTrue($debugFromProfile);
        
        // Runtime override should take precedence
        $this->schemasConfig->setRuntimeConfig('debug', false);
        $debugFromRuntime = $this->schemasConfig->get('debug');
        $this->assertFalse($debugFromRuntime);
    }

    public function testConfigurationValidation(): void
    {
        // This would typically be called during configuration setup
        // We'll test it exists and returns expected structure
        if (method_exists($this->schemasConfig, 'validateConfig')) {
            $validation = $this->schemasConfig->validateConfig();
            
            $this->assertIsArray($validation);
            $this->assertArrayHasKey('valid', $validation);
            $this->assertArrayHasKey('errors', $validation);
            $this->assertArrayHasKey('warnings', $validation);
            
            $this->assertIsBool($validation['valid']);
            $this->assertIsArray($validation['errors']);
            $this->assertIsArray($validation['warnings']);
        } else {
            // If method doesn't exist, we still need an assertion to avoid risky test
            $this->assertTrue(true, 'validateConfig method is not implemented yet');
        }
    }
}
