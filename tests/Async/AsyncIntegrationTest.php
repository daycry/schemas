<?php

declare(strict_types=1);

namespace Tests\Async;

use Daycry\Schemas\Schemas;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Async\AsyncManager;
use Tests\Support\TestCase;

/**
 * Test async integration with Schemas class
 */
class AsyncIntegrationTest extends TestCase
{
    private Schemas $schemasInstance;
    private SchemasConfig $schemasConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->schemasConfig = new SchemasConfig();
        // Enable plugins first (required for async manager)
        $this->schemasConfig->plugins = [
            'enabled' => true,
            'auto_discover' => false,
            'auto_load' => false,
            'discovery_paths' => []
        ];
        
        // Enable async functionality
        $this->schemasConfig->async = [
            'enabled' => true,
            'max_jobs' => 10,
            'timeout' => 30
        ];
        
        $this->schemasInstance = new Schemas($this->schemasConfig);
    }

    public function testAsyncManagerInitialization(): void
    {
        $asyncManager = $this->schemasInstance->getAsyncManager();
        
        $this->assertInstanceOf(AsyncManager::class, $asyncManager);
        $this->assertNotNull($asyncManager);
    }

    public function testAsyncManagerDisabled(): void
    {
        $disabledConfig = new SchemasConfig();
        // Async manager won't be created without plugins enabled
        $disabledConfig->plugins = [
            'enabled' => false
        ];
        $disabledConfig->async = [
            'enabled' => false
        ];
        
        $schemas = new Schemas($disabledConfig);
        $asyncManager = $schemas->getAsyncManager();
        
        $this->assertNull($asyncManager);
    }

    public function testAsyncHandlerRegistration(): void
    {
        $asyncManager = $this->schemasInstance->getAsyncManager();
        $this->assertNotNull($asyncManager);
        
        $handlers = $asyncManager->getHandlers();
        $this->assertIsArray($handlers);
    }

    public function testAsyncStatistics(): void
    {
        $asyncManager = $this->schemasInstance->getAsyncManager();
        $this->assertNotNull($asyncManager);
        
        $stats = $asyncManager->getStatistics();
        $this->assertIsArray($stats);
    }
}
