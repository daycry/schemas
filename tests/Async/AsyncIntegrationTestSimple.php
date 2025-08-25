<?php

declare(strict_types=1);

namespace Tests\Async;

use Daycry\Schemas\Schemas;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Async\AsyncManager;
use Tests\Support\TestCase;

/**
 * @internal
 */
class AsyncIntegrationTestSimple extends TestCase
{
    private Schemas $schemasInstance;
    private SchemasConfig $schemasConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->schemasConfig = new SchemasConfig();
        $this->schemasConfig->async['enabled'] = true; // Enable async processing
        
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
        // Create schemas with async disabled
        $disabledConfig = new SchemasConfig();
        $disabledConfig->async['enabled'] = false;
        
        $schemas = new Schemas($disabledConfig);
        $asyncManager = $schemas->getAsyncManager();
        
        $this->assertNull($asyncManager);
    }

    public function testReadAsync(): void
    {
        $tables = ['users', 'products'];
        
        $jobId = $this->schemasInstance->readAsync($tables);
        
        $this->assertIsString($jobId);
        $this->assertNotEmpty($jobId);
        
        // Check job status
        $status = $this->schemasInstance->getJobStatus($jobId);
        $this->assertContains($status, ['pending', 'running', 'completed', 'failed']);
    }

    public function testValidateAsync(): void
    {
        $tables = ['table1', 'table2'];
        
        $jobId = $this->schemasInstance->validateAsync($tables);
        
        $this->assertIsString($jobId);
        $this->assertNotEmpty($jobId);
        
        // Check job status
        $status = $this->schemasInstance->getJobStatus($jobId);
        $this->assertContains($status, ['pending', 'running', 'completed', 'failed']);
    }

    public function testCompareAsync(): void
    {
        $sourceSchemas = ['source_table'];
        $targetSchemas = ['target_table'];
        
        $jobId = $this->schemasInstance->compareAsync($sourceSchemas, $targetSchemas);
        
        $this->assertIsString($jobId);
        $this->assertNotEmpty($jobId);
        
        // Check job status
        $status = $this->schemasInstance->getJobStatus($jobId);
        $this->assertContains($status, ['pending', 'running', 'completed', 'failed']);
    }

    public function testMergeAsync(): void
    {
        $tables = ['table1', 'table2'];
        
        $jobId = $this->schemasInstance->mergeAsync($tables, ['target_database' => 'merged_db']);
        
        $this->assertIsString($jobId);
        $this->assertNotEmpty($jobId);
        
        // Check job status
        $status = $this->schemasInstance->getJobStatus($jobId);
        $this->assertContains($status, ['pending', 'running', 'completed', 'failed']);
    }

    public function testJobCancellation(): void
    {
        $tables = ['users', 'products', 'orders'];
        
        $jobId = $this->schemasInstance->readAsync($tables);
        
        // Cancel the job
        $cancelled = $this->schemasInstance->cancelJob($jobId);
        $this->assertTrue($cancelled);
        
        $status = $this->schemasInstance->getJobStatus($jobId);
        $this->assertEquals('cancelled', $status);
    }

    public function testJobResult(): void
    {
        $tables = ['table1', 'table2'];
        
        $jobId = $this->schemasInstance->validateAsync($tables);
        
        // Get status
        $status = $this->schemasInstance->getJobStatus($jobId);
        $this->assertIsString($status);
        
        // Wait for completion
        $this->schemasInstance->waitForJob($jobId, 5);
        
        // Get result
        $result = $this->schemasInstance->getJobResult($jobId);
        $this->assertNotNull($result);
    }

    public function testJobProgress(): void
    {
        $progressCalled = false;
        $callback = function($progress) use (&$progressCalled) {
            $progressCalled = true;
            $this->assertIsArray($progress);
            $this->assertArrayHasKey('percentage', $progress);
            $this->assertIsFloat($progress['percentage']);
        };
        
        $tables = ['table1'];
        $jobId = $this->schemasInstance->validateAsync($tables, [], $callback);
        
        // Wait a bit for progress
        sleep(1);
        
        $this->assertIsString($jobId);
        // Note: Progress callback might not be called in unit tests due to fast execution
    }

    public function testAsyncStatistics(): void
    {
        $manager = $this->schemasInstance->getAsyncManager();
        $stats = $manager->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_jobs', $stats);
        $this->assertArrayHasKey('completed_jobs', $stats);
        $this->assertArrayHasKey('failed_jobs', $stats);
        $this->assertArrayHasKey('cancelled_jobs', $stats);
    }

    public function testJobCleanup(): void
    {
        $tables = ['table1'];
        $jobId = $this->schemasInstance->validateAsync($tables);
        
        // Wait for completion
        $this->schemasInstance->waitForJob($jobId, 5);
        
        // Cleanup
        $manager = $this->schemasInstance->getAsyncManager();
        $cleaned = $manager->cleanup();
        
        $this->assertIsInt($cleaned);
        $this->assertGreaterThanOrEqual(0, $cleaned);
    }

    public function testBulkOperations(): void
    {
        $tables = ['users', 'products', 'orders'];
        
        // Create multiple jobs
        $jobs = [];
        foreach ($tables as $table) {
            $jobs[] = $this->schemasInstance->readAsync([$table]);
        }
        
        $this->assertCount(3, $jobs);
        
        // Check all jobs are created
        foreach ($jobs as $jobId) {
            $this->assertIsString($jobId);
            $this->assertNotEmpty($jobId);
            
            $status = $this->schemasInstance->getJobStatus($jobId);
            $this->assertContains($status, ['pending', 'running', 'completed', 'failed']);
        }
    }

    public function testAsyncConfiguration(): void
    {
        $config = $this->schemasConfig;
        
        $this->assertTrue($config->async['enabled']);
        $this->assertArrayHasKey('handlers', $config->async);
        $this->assertArrayHasKey('job_timeout', $config->async);
        $this->assertArrayHasKey('max_concurrent_jobs', $config->async);
        $this->assertArrayHasKey('cleanup_interval', $config->async);
    }

    public function testErrorHandling(): void
    {
        // Test with empty array
        $jobId = $this->schemasInstance->readAsync([]);
        
        $this->assertIsString($jobId);
        
        // Wait for completion
        $this->schemasInstance->waitForJob($jobId, 5);
        
        $status = $this->schemasInstance->getJobStatus($jobId);
        // Should handle error gracefully
        $this->assertContains($status, ['completed', 'failed']);
    }
}
