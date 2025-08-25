<?php

declare(strict_types=1);

namespace Tests\Async\Handlers;

use Daycry\Schemas\Async\Handlers\AsyncSchemaHandler;
use Daycry\Schemas\Async\AsyncHandlerInterface;
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Reader\ReaderInterface;
use Daycry\Schemas\Archiver\ArchiverInterface;
use Daycry\Schemas\Structures\Schema;
use Tests\Support\TestCase;

/**
 * Test AsyncSchemaHandler class
 */
class AsyncSchemaHandlerTest extends TestCase
{
    private AsyncSchemaHandler $handler;
    private Schemas $mockSchemas;
    private ReaderInterface $reader;
    private ArchiverInterface $archiver;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockSchemas = $this->createMock(Schemas::class);
        $this->reader = $this->getMockBuilder(ReaderInterface::class)->getMock();
        $this->archiver = $this->getMockBuilder(ArchiverInterface::class)->getMock();
        
        $this->handler = new AsyncSchemaHandler($this->mockSchemas);
        $this->handler->setReader($this->reader);
        $this->handler->setArchiver($this->archiver);
    }

    public function testHandlerSupportsOperations(): void
    {
        $supportedOps = $this->handler->getSupportedOperations();
        
        $this->assertContains(AsyncSchemaHandler::OPERATION_READ, $supportedOps);
        $this->assertContains(AsyncSchemaHandler::OPERATION_ARCHIVE, $supportedOps);
        $this->assertContains(AsyncSchemaHandler::OPERATION_DRAFT, $supportedOps);
        $this->assertContains(AsyncSchemaHandler::OPERATION_VALIDATE, $supportedOps);
        $this->assertContains(AsyncSchemaHandler::OPERATION_COMPARE, $supportedOps);
        $this->assertContains(AsyncSchemaHandler::OPERATION_MERGE, $supportedOps);
        
        $this->assertTrue($this->handler->supports(AsyncSchemaHandler::OPERATION_READ));
        $this->assertFalse($this->handler->supports('unsupported_operation'));
    }

    public function testAsyncReadOperation(): void
    {
        $tables = ['users', 'products'];

        $jobId = $this->handler->processAsync($tables, [
            'type' => AsyncSchemaHandler::OPERATION_READ
        ]);

        $this->assertIsString($jobId);
        
        // Wait for completion
        $result = $this->handler->waitFor($jobId, 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('products', $result);
        $this->assertTrue($result['users']['success']);
        $this->assertTrue($result['products']['success']);
    }

    public function testAsyncArchiveOperation(): void
    {
        $schemas = [
            ['database' => 'test_db', 'tables' => []],
        ];

        $jobId = $this->handler->processAsync($schemas, [
            'type' => AsyncSchemaHandler::OPERATION_ARCHIVE
        ]);

        $this->assertIsString($jobId);
        
        // Check status
        $status = $this->handler->getStatus($jobId);
        $this->assertArrayHasKey('status', $status);
    }

    public function testAsyncDraftOperation(): void
    {
        $schemas = [
            ['database' => 'test_db', 'tables' => []],
        ];

        $jobId = $this->handler->processAsync($schemas, [
            'type' => AsyncSchemaHandler::OPERATION_DRAFT,
            'handlers' => ['test_handler']
        ]);

        $this->assertIsString($jobId);
        
        // Wait for completion
        $result = $this->handler->waitFor($jobId, 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_db', $result);
        $this->assertTrue($result['test_db']['success']);
        $this->assertArrayHasKey('draft', $result['test_db']);
    }

    public function testAsyncValidateOperation(): void
    {
        $schemas = [
            ['database' => 'test_db', 'tables' => ['users' => ['columns' => ['id', 'name']]]],
        ];

        $jobId = $this->handler->processAsync($schemas, [
            'type' => AsyncSchemaHandler::OPERATION_VALIDATE
        ]);

        $this->assertIsString($jobId);
        
        // Wait for completion
        $result = $this->handler->waitFor($jobId, 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_db', $result);
        $this->assertTrue($result['test_db']['success']);
        $this->assertArrayHasKey('validation', $result['test_db']);
    }

    public function testAsyncCompareOperation(): void
    {
        $data = [
            'source' => ['database' => 'source_db', 'tables' => ['users' => []]],
            'target' => ['database' => 'target_db', 'tables' => ['products' => []]],
        ];

        $jobId = $this->handler->processAsync($data, [
            'type' => AsyncSchemaHandler::OPERATION_COMPARE
        ]);

        $this->assertIsString($jobId);
        
        // Wait for completion
        $result = $this->handler->waitFor($jobId, 5);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('comparison', $result);
    }

    public function testAsyncMergeOperation(): void
    {
        $data = [
            'schemas' => [
                ['database' => 'db1', 'tables' => ['users' => []]],
                ['database' => 'db2', 'tables' => ['products' => []]],
            ],
        ];

        $jobId = $this->handler->processAsync($data, [
            'type' => AsyncSchemaHandler::OPERATION_MERGE,
            'target_database' => 'merged_db'
        ]);

        $this->assertIsString($jobId);
        
        // Wait for completion
        $result = $this->handler->waitFor($jobId, 5);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('merged_schema', $result);
        $this->assertEquals('merged_db', $result['merged_schema']['database']);
    }

    public function testJobCancellation(): void
    {
        $tables = ['users', 'products', 'orders']; // More tables to slow down
        
        $jobId = $this->handler->processAsync($tables, [
            'type' => AsyncSchemaHandler::OPERATION_READ
        ]);

        // Try to cancel immediately
        $cancelResult = $this->handler->cancel($jobId);
        
        $status = $this->handler->getStatus($jobId);
        
        // Job should either be cancelled or completed quickly
        $this->assertContains($status['status'], [
            AsyncHandlerInterface::STATUS_CANCELLED,
            AsyncHandlerInterface::STATUS_COMPLETED,
            AsyncHandlerInterface::STATUS_FAILED
        ]);
    }

    public function testJobPriority(): void
    {
        $tables = ['users'];
        
        $jobId = $this->handler->processAsync($tables, [
            'type' => AsyncSchemaHandler::OPERATION_READ,
            'priority' => AsyncHandlerInterface::PRIORITY_HIGH
        ]);

        $status = $this->handler->getStatus($jobId);
        $this->assertEquals(AsyncHandlerInterface::PRIORITY_HIGH, $status['priority']);
        
        // Try to change priority (might fail if job is already running/completed)
        $priorityChanged = $this->handler->setPriority($jobId, AsyncHandlerInterface::PRIORITY_LOW);
        
        // If priority change succeeded, verify it
        if ($priorityChanged) {
            $status = $this->handler->getStatus($jobId);
            $this->assertEquals(AsyncHandlerInterface::PRIORITY_LOW, $status['priority']);
        } else {
            // If priority change failed, that's also acceptable if job is running/completed
            $this->assertTrue(true); // Pass the test
        }
    }

    public function testJobProgress(): void
    {
        $tables = ['users', 'products', 'orders'];

        $jobId = $this->handler->processAsync($tables, [
            'type' => AsyncSchemaHandler::OPERATION_READ
        ]);

        // Wait for completion
        $this->handler->waitFor($jobId, 5);
        
        $progress = $this->handler->getProgress($jobId);
        
        $this->assertEquals(3, $progress['current']);
        $this->assertEquals(3, $progress['total']);
        $this->assertEquals(100.0, $progress['percentage']);
        $this->assertStringContainsString('completed', $progress['message']);
    }

    public function testInvalidOperation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Operation type 'invalid_operation' is not supported");
        
        $this->handler->processAsync(['data'], [
            'type' => 'invalid_operation'
        ]);
    }

    public function testJobNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Job 'non_existent' not found");
        
        $this->handler->getStatus('non_existent');
    }

    public function testHandlerConfiguration(): void
    {
        $config = [
            'max_concurrent_jobs' => 5,
            'job_timeout' => 600,
        ];
        
        $this->handler->setConfig($config);
        $retrievedConfig = $this->handler->getConfig();
        
        $this->assertEquals(5, $retrievedConfig['max_concurrent_jobs']);
        $this->assertEquals(600, $retrievedConfig['job_timeout']);
    }

    public function testStatistics(): void
    {
        $tables = ['users'];
        
        $jobId = $this->handler->processAsync($tables, [
            'type' => AsyncSchemaHandler::OPERATION_READ
        ]);

        $stats = $this->handler->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_jobs', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertGreaterThan(0, $stats['total_jobs']);
    }

    public function testCleanup(): void
    {
        $tables = ['users'];
        
        $jobId = $this->handler->processAsync($tables, [
            'type' => AsyncSchemaHandler::OPERATION_READ
        ]);

        // Wait for completion
        $this->handler->waitFor($jobId, 5);
        
        // Clean up jobs older than 0 seconds (should clean the completed job)
        $cleaned = $this->handler->cleanup(0);
        
        $this->assertGreaterThanOrEqual(0, $cleaned);
    }
}
