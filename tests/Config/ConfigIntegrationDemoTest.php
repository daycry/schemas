<?php

declare(strict_types=1);

namespace Tests\Config;

use Daycry\Schemas\Schemas;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
class ConfigIntegrationDemoTest extends TestCase
{
    private SchemasConfig $testConfig;
    private Schemas $schemas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testConfig = new SchemasConfig();
        $this->schemas = new Schemas($this->testConfig);
    }

    public function testCompleteConfigurationWorkflow(): void
    {
        // 1. Demonstrate environment-specific configuration
        \$this->testConfig->switchEnvironment('development');
        $this->assertTrue(\$this->testConfig->get('debug', false));
        
        \$this->testConfig->switchEnvironment('production');
        $this->assertFalse(\$this->testConfig->get('debug', true));
        
        // 2. Create custom profile for staging environment
        $stagingProfile = [
            'debug' => false,
            'cache' => ['enabled' => true, 'ttl' => 1800],
            'async' => ['enabled' => true, 'max_concurrent_jobs' => 2],
            'plugins' => ['enabled' => true, 'auto_discover' => false],
            'logging' => ['level' => 'warning', 'channels' => ['file']],
            'performance' => ['profiling' => true, 'memory_threshold' => '256M']
        ];
        
        \$this->testConfig->createProfile('staging', $stagingProfile);
        \$this->testConfig->switchEnvironment('staging');
        
        // Verify staging configuration is applied
        $this->assertFalse(\$this->testConfig->get('debug'));
        $this->assertEquals(1800, \$this->testConfig->get('cache.ttl'));
        $this->assertEquals(2, \$this->testConfig->get('async.max_concurrent_jobs'));
        $this->assertEquals('warning', \$this->testConfig->get('logging.level'));
        
        // 3. Runtime configuration overrides
        \$this->testConfig->setRuntimeConfig('async.max_concurrent_jobs', 5);
        \$this->testConfig->setRuntimeConfig('cache.ttl', 7200);
        \$this->testConfig->setRuntimeConfig('logging.level', 'debug');
        
        // Verify runtime overrides take precedence
        $this->assertEquals(5, \$this->testConfig->get('async.max_concurrent_jobs'));
        $this->assertEquals(7200, \$this->testConfig->get('cache.ttl'));
        $this->assertEquals('debug', \$this->testConfig->get('logging.level'));
        
        // 4. Configuration snapshots for rollback
        $snapshot = \$this->testConfig->createSnapshot();
        
        // Make more changes
        \$this->testConfig->setRuntimeConfig('plugins.enabled', false);
        \$this->testConfig->setRuntimeConfig('security.enabled', false);
        
        $this->assertFalse(\$this->testConfig->get('plugins.enabled'));
        $this->assertFalse(\$this->testConfig->get('security.enabled'));
        
        // Restore from snapshot
        \$this->testConfig->restoreSnapshot($snapshot);
        
        $this->assertTrue(\$this->testConfig->get('plugins.enabled'));
        $this->assertTrue(\$this->testConfig->get('security.enabled'));
        
        // 5. Configuration statistics and monitoring
        $stats = \$this->testConfig->getConfigStats();
        
        $this->assertGreaterThan(0, $stats['total_config_keys']);
        $this->assertEquals('staging', $stats['environment']);
        $this->assertContains('staging', $stats['profiles_available']);
        
        // 6. Export/Import configuration
        $tempFile = tempnam(sys_get_temp_dir(), 'schemas_demo');
        
        $exportResult = \$this->testConfig->exportConfig($tempFile, 'json');
        $this->assertTrue($exportResult);
        
        // Modify configuration
        \$this->testConfig->setRuntimeConfig('demo.exported', 'original');
        
        // Import should restore exported state
        $importResult = \$this->testConfig->importConfig($tempFile, 'json');
        $this->assertTrue($importResult);
        
        unlink($tempFile);
    }

    public function testConfigurationEventSystem(): void
    {
        $eventLog = [];
        
        // Register event listeners
        \$this->testConfig->addConfigListener('runtime_override', function($data) use (&$eventLog) {
            $eventLog[] = "Runtime override: {$data['key']} = {$data['value']}";
        });
        
        \$this->testConfig->addConfigListener('environment_changed', function($data) use (&$eventLog) {
            $eventLog[] = "Environment changed to: {$data['environment']}";
        });
        
        \$this->testConfig->addConfigListener('profile_updated', function($data) use (&$eventLog) {
            $eventLog[] = "Profile updated: {$data['profile']}";
        });
        
        // Trigger events
        \$this->testConfig->setRuntimeConfig('test.value', 'test');
        \$this->testConfig->switchEnvironment('production');
        \$this->testConfig->createProfile('test_profile', ['test' => 'config']);
        
        // Verify events were captured
        $this->assertCount(3, $eventLog);
        $this->assertStringContainsString('Runtime override: test.value = test', $eventLog[0]);
        $this->assertStringContainsString('Environment changed to: production', $eventLog[1]);
        $this->assertStringContainsString('Profile updated: test_profile', $eventLog[2]);
    }

    public function testAdvancedConfigurationFeatures(): void
    {
        // Test security configuration
        $allowedOps = \$this->testConfig->security['allowed_operations'];
        $this->assertContains('read', $allowedOps);
        $this->assertContains('validate', $allowedOps);
        $this->assertNotContains('delete', $allowedOps);
        
        // Test performance configuration
        $this->assertIsString(\$this->testConfig->performance['memory_threshold']);
        $this->assertIsFloat(\$this->testConfig->performance['time_threshold']);
        
        // Test development tools
        $this->assertIsBool(\$this->testConfig->development['schema_diff_tool']);
        $this->assertIsBool(\$this->testConfig->development['migration_generator']);
        
        // Test advanced cache configuration
        $this->assertArrayHasKey('invalidation', \$this->testConfig->advancedCache);
        $this->assertArrayHasKey('auto', \$this->testConfig->advancedCache['invalidation']);
        $this->assertArrayHasKey('events', \$this->testConfig->advancedCache['invalidation']);
    }

    public function testConfigurationHierarchyPrecedence(): void
    {
        // Base configuration
        \$this->testConfig->setRuntimeConfig('test.hierarchy', 'base');
        
        // Environment profile should override base
        $envProfile = ['test' => ['hierarchy' => 'environment']];
        \$this->testConfig->createProfile('hierarchy_test', $envProfile);
        \$this->testConfig->switchEnvironment('hierarchy_test');
        
        $this->assertEquals('environment', \$this->testConfig->get('test.hierarchy'));
        
        // Runtime override should take highest precedence
        \$this->testConfig->setRuntimeConfig('test.hierarchy', 'runtime');
        $this->assertEquals('runtime', \$this->testConfig->get('test.hierarchy'));
    }

    public function testDynamicConfigurationUpdates(): void
    {
        // Start with async disabled
        \$this->testConfig->setRuntimeConfig('async.enabled', false);
        $this->assertFalse(\$this->testConfig->async['enabled']);
        
        // Enable async at runtime
        \$this->testConfig->setRuntimeConfig('async.enabled', true);
        $this->assertTrue(\$this->testConfig->async['enabled']);
        
        // Update concurrent job limit
        \$this->testConfig->setRuntimeConfig('async.max_concurrent_jobs', 10);
        $this->assertEquals(10, \$this->testConfig->async['max_concurrent_jobs']);
        
        // Update cache settings
        \$this->testConfig->setRuntimeConfig('cache.enabled', true);
        \$this->testConfig->setRuntimeConfig('cache.ttl', 5400);
        
        $this->assertTrue(\$this->testConfig->cache['enabled']);
        $this->assertEquals(5400, \$this->testConfig->cache['ttl']);
    }

    public function testConfigurationValidationAndSafety(): void
    {
        // Test that configuration updates are properly applied
        $originalAsyncEnabled = \$this->testConfig->async['enabled'];
        
        \$this->testConfig->setRuntimeConfig('async.enabled', !$originalAsyncEnabled);
        $this->assertEquals(!$originalAsyncEnabled, \$this->testConfig->async['enabled']);
        
        // Test configuration snapshot for safety
        $safetySnapshot = \$this->testConfig->createSnapshot();
        
        // Make potentially unsafe changes
        \$this->testConfig->setRuntimeConfig('async.max_concurrent_jobs', 100);
        \$this->testConfig->setRuntimeConfig('cache.ttl', -1);
        
        // Restore to safe state
        \$this->testConfig->restoreSnapshot($safetySnapshot);
        
        // Verify restoration
        $this->assertEquals(!$originalAsyncEnabled, \$this->testConfig->async['enabled']);
        $this->assertNotEquals(100, \$this->testConfig->async['max_concurrent_jobs']);
        $this->assertNotEquals(-1, \$this->testConfig->cache['ttl']);
    }
}
