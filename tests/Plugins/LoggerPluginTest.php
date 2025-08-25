<?php

namespace Tests\Plugins;

use Daycry\Schemas\Plugins\Examples\LoggerPlugin;
use Daycry\Schemas\Events\BaseEvent;
use Daycry\Schemas\Events\SchemaEvents;
use Tests\Support\TestCase;

class LoggerPluginTest extends TestCase
{
    protected LoggerPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new LoggerPlugin([
            'enabled' => true,
            'log_level' => 'info',
            'include_events' => [],
            'exclude_events' => [],
            'log_sensitive_data' => false
        ]);
    }

    public function testCanCreatePlugin(): void
    {
        $this->assertInstanceOf(LoggerPlugin::class, $this->plugin);
    }

    public function testGetBasicInfo(): void
    {
        $this->assertEquals('Schema Logger', $this->plugin->getName());
        $this->assertIsString($this->plugin->getVersion());
        $this->assertIsString($this->plugin->getDescription());
        $this->assertIsArray($this->plugin->getAuthor());
    }

    public function testIsEnabled(): void
    {
        $enabledPlugin = new LoggerPlugin(['enabled' => true]);
        $this->assertTrue($enabledPlugin->isEnabled());
        
        $disabledPlugin = new LoggerPlugin(['enabled' => false]);
        $this->assertFalse($disabledPlugin->isEnabled());
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->plugin->getSubscribedEvents();
        
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        
        // Should include common schema events
        $this->assertContains(SchemaEvents::SCHEMA_BEFORE_DRAFT, $events);
        $this->assertContains(SchemaEvents::SCHEMA_AFTER_DRAFT, $events);
        $this->assertContains(SchemaEvents::SCHEMA_BEFORE_ARCHIVE, $events);
        $this->assertContains(SchemaEvents::SCHEMA_AFTER_ARCHIVE, $events);
    }

    public function testInitialize(): void
    {
        // Should not throw any exceptions
        $this->plugin->initialize();
        
        $this->assertTrue($this->plugin->isInitialized());
    }

    public function testHandleEvent(): void
    {
        $this->plugin->initialize();
        
        $event = new BaseEvent(SchemaEvents::SCHEMA_BEFORE_DRAFT, [
            'schema' => 'test_schema',
            'tables' => ['users', 'posts']
        ]);
        
        // Should not throw any exceptions
        $this->plugin->handleEvent($event);
        
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testEventFiltering(): void
    {
        // Test include events filter
        $includeOnlyPlugin = new LoggerPlugin([
            'enabled' => true,
            'include_events' => [SchemaEvents::SCHEMA_BEFORE_DRAFT]
        ]);
        
        $subscribedEvents = $includeOnlyPlugin->getSubscribedEvents();
        $this->assertContains(SchemaEvents::SCHEMA_BEFORE_DRAFT, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
        
        // Test exclude events filter
        $excludePlugin = new LoggerPlugin([
            'enabled' => true,
            'exclude_events' => [SchemaEvents::SCHEMA_BEFORE_DRAFT]
        ]);
        
        $subscribedEvents = $excludePlugin->getSubscribedEvents();
        $this->assertNotContains(SchemaEvents::SCHEMA_BEFORE_DRAFT, $subscribedEvents);
    }

    public function testConfiguration(): void
    {
        $config = [
            'enabled' => true,
            'log_level' => 'debug',
            'include_events' => [SchemaEvents::SCHEMA_BEFORE_DRAFT],
            'exclude_events' => [],
            'log_sensitive_data' => true
        ];
        
        $plugin = new LoggerPlugin($config);
        
        $this->assertTrue($plugin->isEnabled());
        
        // Test that configuration is properly stored
        $metadata = $plugin->getMetadata();
        $this->assertArrayHasKey('config', $metadata);
    }

    public function testGetDependencies(): void
    {
        $dependencies = $this->plugin->getDependencies();
        
        $this->assertIsArray($dependencies);
        // Logger plugin should have minimal dependencies
        $this->assertEmpty($dependencies);
    }

    public function testCheckCompatibility(): void
    {
        $this->assertTrue($this->plugin->checkCompatibility('1.0.0'));
        $this->assertTrue($this->plugin->checkCompatibility('2.0.0'));
        
        // Should be compatible with most versions
        $this->assertTrue($this->plugin->checkCompatibility('0.9.0'));
    }

    public function testValidateConfiguration(): void
    {
        // Valid configuration
        $validConfig = [
            'enabled' => true,
            'log_level' => 'info',
            'include_events' => [],
            'exclude_events' => [],
            'log_sensitive_data' => false
        ];
        
        $plugin = new LoggerPlugin($validConfig);
        $this->assertTrue($plugin->validateConfiguration($validConfig));
        
        // Invalid configuration (missing required field)
        $invalidConfig = [
            'log_level' => 'info'
        ];
        
        $this->assertFalse($plugin->validateConfiguration($invalidConfig));
    }

    public function testGetMetadata(): void
    {
        $metadata = $this->plugin->getMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('version', $metadata);
        $this->assertArrayHasKey('description', $metadata);
        $this->assertArrayHasKey('author', $metadata);
        $this->assertArrayHasKey('config', $metadata);
        $this->assertArrayHasKey('dependencies', $metadata);
        $this->assertArrayHasKey('subscribed_events', $metadata);
        $this->assertArrayHasKey('enabled', $metadata);
        $this->assertArrayHasKey('initialized', $metadata);
    }

    public function testShutdown(): void
    {
        $this->plugin->initialize();
        $this->assertTrue($this->plugin->isInitialized());
        
        $this->plugin->shutdown();
        $this->assertFalse($this->plugin->isInitialized());
    }
}
