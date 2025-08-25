<?php

namespace Tests\Plugins;

use Daycry\Schemas\Plugins\PluginManager;
use Daycry\Schemas\Plugins\Examples\LoggerPlugin;
use Daycry\Schemas\Events\BaseEvent;
use Daycry\Schemas\Events\SchemaEvents;
use Daycry\Schemas\Exceptions\SchemasException;
use Tests\Support\TestCase;

class PluginManagerTest extends TestCase
{
    protected PluginManager $pluginManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pluginManager = new PluginManager(null, '1.0.0');
    }

    public function testCanCreatePluginManager(): void
    {
        $this->assertInstanceOf(PluginManager::class, $this->pluginManager);
    }

    public function testCanRegisterPlugin(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        
        $this->pluginManager->registerPlugin($plugin);
        
        $this->assertTrue($this->pluginManager->hasPlugin($plugin->getName()));
        $this->assertSame($plugin, $this->pluginManager->getPlugin($plugin->getName()));
    }

    public function testCannotRegisterSamePluginTwice(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        
        $this->pluginManager->registerPlugin($plugin);
        
        $this->expectException(SchemasException::class);
        $this->expectExceptionMessage("Plugin 'Schema Logger' is already registered");
        
        $this->pluginManager->registerPlugin($plugin);
    }

    public function testCanUnregisterPlugin(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        
        $this->pluginManager->registerPlugin($plugin);
        $this->assertTrue($this->pluginManager->hasPlugin($plugin->getName()));
        
        $this->pluginManager->unregisterPlugin($plugin->getName());
        $this->assertFalse($this->pluginManager->hasPlugin($plugin->getName()));
    }

    public function testCannotUnregisterNonExistentPlugin(): void
    {
        $this->expectException(SchemasException::class);
        $this->expectExceptionMessage("Plugin 'NonExistent' not found");
        
        $this->pluginManager->unregisterPlugin('NonExistent');
    }

    public function testCanDispatchEvent(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        $this->pluginManager->registerPlugin($plugin);
        
        $event = new BaseEvent(SchemaEvents::SCHEMA_BEFORE_DRAFT, ['test' => 'data']);
        
        // Should not throw any exceptions
        $this->pluginManager->dispatchEvent($event);
        
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testCanEmitEvent(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        $this->pluginManager->registerPlugin($plugin);
        
        $event = $this->pluginManager->emit(SchemaEvents::SCHEMA_BEFORE_DRAFT, ['test' => 'data']);
        
        $this->assertInstanceOf(BaseEvent::class, $event);
        $this->assertEquals(SchemaEvents::SCHEMA_BEFORE_DRAFT, $event->getName());
        $this->assertEquals(['test' => 'data'], $event->getData());
    }

    public function testGetStatistics(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        $this->pluginManager->registerPlugin($plugin);
        
        $stats = $this->pluginManager->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_plugins', $stats);
        $this->assertArrayHasKey('enabled_plugins', $stats);
        $this->assertArrayHasKey('disabled_plugins', $stats);
        $this->assertArrayHasKey('event_listeners', $stats);
        $this->assertArrayHasKey('discovery_paths', $stats);
        
        $this->assertEquals(1, $stats['total_plugins']);
        $this->assertEquals(1, $stats['enabled_plugins']);
        $this->assertEquals(0, $stats['disabled_plugins']);
    }

    public function testValidatePlugins(): void
    {
        $plugin = new LoggerPlugin(['enabled' => true]);
        $this->pluginManager->registerPlugin($plugin);
        
        $results = $this->pluginManager->validatePlugins();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey($plugin->getName(), $results);
        
        $result = $results[$plugin->getName()];
        $this->assertArrayHasKey('compatible', $result);
        $this->assertArrayHasKey('dependencies_met', $result);
        $this->assertArrayHasKey('config_valid', $result);
        $this->assertArrayHasKey('enabled', $result);
        
        $this->assertTrue($result['compatible']);
        $this->assertTrue($result['dependencies_met']);
        $this->assertTrue($result['enabled']);
    }

    public function testDiscoveryPaths(): void
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'schemas_test_plugins';
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $this->pluginManager->addDiscoveryPath($tempDir);
        
        $stats = $this->pluginManager->getStatistics();
        $this->assertEquals(1, $stats['discovery_paths']);
        
        // Clean up
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }

    public function testInvalidDiscoveryPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Directory '/non/existent/path' does not exist");
        
        $this->pluginManager->addDiscoveryPath('/non/existent/path');
    }

    public function testGetAllPlugins(): void
    {
        $plugin1 = new LoggerPlugin(['enabled' => true]);
        $this->pluginManager->registerPlugin($plugin1);
        
        $plugins = $this->pluginManager->getPlugins();
        
        $this->assertIsArray($plugins);
        $this->assertCount(1, $plugins);
        $this->assertArrayHasKey($plugin1->getName(), $plugins);
        $this->assertSame($plugin1, $plugins[$plugin1->getName()]);
    }
}
