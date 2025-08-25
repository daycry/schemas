<?php

declare(strict_types=1);

namespace Tests\Async;

use Daycry\Schemas\Async\AsyncJob;
use Daycry\Schemas\Async\AsyncHandlerInterface;
use Tests\Support\TestCase;

/**
 * Test AsyncJob class
 */
class AsyncJobTest extends TestCase
{
    public function testJobCreation(): void
    {
        $jobId = 'test_job_123';
        $type = 'test_operation';
        $data = ['key' => 'value'];
        $options = ['option1' => 'value1'];
        $callback = function() { return true; };
        $priority = AsyncHandlerInterface::PRIORITY_HIGH;

        $job = new AsyncJob($jobId, $type, $data, $options, $callback, $priority);

        $this->assertEquals($jobId, $job->getId());
        $this->assertEquals($type, $job->getType());
        $this->assertEquals($data, $job->getData());
        $this->assertEquals($options, $job->getOptions());
        $this->assertEquals($callback, $job->getCallback());
        $this->assertEquals($priority, $job->getPriority());
        $this->assertEquals(AsyncHandlerInterface::STATUS_PENDING, $job->getStatus());
        $this->assertTrue($job->isPending());
        $this->assertFalse($job->isRunning());
        $this->assertFalse($job->isCompleted());
    }

    public function testJobStatusChanges(): void
    {
        $job = new AsyncJob('test', 'type', []);

        // Test running status
        $job->setStatus(AsyncHandlerInterface::STATUS_RUNNING);
        $this->assertEquals(AsyncHandlerInterface::STATUS_RUNNING, $job->getStatus());
        $this->assertTrue($job->isRunning());
        $this->assertFalse($job->isPending());
        $this->assertFalse($job->isCompleted());
        $this->assertGreaterThan(0, $job->getStartedAt());

        // Test completed status
        $job->setStatus(AsyncHandlerInterface::STATUS_COMPLETED);
        $this->assertEquals(AsyncHandlerInterface::STATUS_COMPLETED, $job->getStatus());
        $this->assertTrue($job->isCompleted());
        $this->assertFalse($job->isRunning());
        $this->assertGreaterThan(0, $job->getCompletedAt());
        $this->assertGreaterThan(0, $job->getDuration());
    }

    public function testJobProgress(): void
    {
        $job = new AsyncJob('test', 'type', []);

        // Test initial progress
        $progress = $job->getProgress();
        $this->assertEquals(0, $progress['current']);
        $this->assertEquals(100, $progress['total']);
        $this->assertEquals(0.0, $progress['percentage']);
        $this->assertEquals('Waiting to start...', $progress['message']);

        // Test progress update
        $job->setProgress(50, 100, 'Halfway done');
        $progress = $job->getProgress();
        $this->assertEquals(50, $progress['current']);
        $this->assertEquals(100, $progress['total']);
        $this->assertEquals(50.0, $progress['percentage']);
        $this->assertEquals('Halfway done', $progress['message']);

        // Test partial progress update
        $job->setProgress(75, null, 'Almost done');
        $progress = $job->getProgress();
        $this->assertEquals(75, $progress['current']);
        $this->assertEquals(100, $progress['total']); // Should remain unchanged
        $this->assertEquals(75.0, $progress['percentage']);
        $this->assertEquals('Almost done', $progress['message']);
    }

    public function testJobResult(): void
    {
        $job = new AsyncJob('test', 'type', []);
        
        $result = ['success' => true, 'data' => 'test result'];
        $job->setResult($result);
        
        $this->assertEquals($result, $job->getResult());
    }

    public function testJobError(): void
    {
        $job = new AsyncJob('test', 'type', []);
        
        $error = [
            'message' => 'Test error',
            'code' => 500,
            'file' => __FILE__,
            'line' => __LINE__,
        ];
        
        $job->setError($error);
        
        $this->assertEquals($error, $job->getError());
    }

    public function testJobMetadata(): void
    {
        $job = new AsyncJob('test', 'type', []);
        
        // Test setting metadata
        $metadata = ['key1' => 'value1', 'key2' => 'value2'];
        $job->setMetadata($metadata);
        $this->assertEquals($metadata, $job->getMetadata());
        
        // Test adding metadata
        $job->addMetadata('key3', 'value3');
        $expected = array_merge($metadata, ['key3' => 'value3']);
        $this->assertEquals($expected, $job->getMetadata());
    }

    public function testJobPriority(): void
    {
        $job = new AsyncJob('test', 'type', []);
        
        $this->assertEquals(AsyncHandlerInterface::PRIORITY_NORMAL, $job->getPriority());
        
        $job->setPriority(AsyncHandlerInterface::PRIORITY_HIGH);
        $this->assertEquals(AsyncHandlerInterface::PRIORITY_HIGH, $job->getPriority());
    }

    public function testJobToArray(): void
    {
        $job = new AsyncJob('test_id', 'test_type', []);
        $job->setProgress(50, 100, 'Test progress');
        $job->addMetadata('test_key', 'test_value');
        
        $array = $job->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('test_id', $array['id']);
        $this->assertEquals('test_type', $array['type']);
        $this->assertEquals(AsyncHandlerInterface::STATUS_PENDING, $array['status']);
        $this->assertEquals(AsyncHandlerInterface::PRIORITY_NORMAL, $array['priority']);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('progress', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals(['test_key' => 'test_value'], $array['metadata']);
        $this->assertFalse($array['has_result']);
        $this->assertFalse($array['has_error']);
    }

    public function testJobWithResultAndError(): void
    {
        $job = new AsyncJob('test', 'type', []);
        
        $job->setResult(['data' => 'test']);
        $job->setError(['message' => 'error']);
        
        $array = $job->toArray();
        $this->assertTrue($array['has_result']);
        $this->assertTrue($array['has_error']);
    }

    public function testJobDuration(): void
    {
        $job = new AsyncJob('test', 'type', []);
        
        // Initially no duration
        $this->assertEquals(0.0, $job->getDuration());
        
        // Start the job
        $job->setStatus(AsyncHandlerInterface::STATUS_RUNNING);
        
        // Sleep a bit to get measurable duration
        usleep(10000); // 10ms
        
        // Duration should be positive
        $this->assertGreaterThan(0, $job->getDuration());
        
        // Complete the job
        $job->setStatus(AsyncHandlerInterface::STATUS_COMPLETED);
        
        // Duration should still be positive and fixed
        $duration1 = $job->getDuration();
        $this->assertGreaterThan(0, $duration1);
        
        // Sleep more and check duration doesn't change
        usleep(5000);
        $duration2 = $job->getDuration();
        $this->assertEqualsWithDelta($duration1, $duration2, 0.001); // Allow small floating point differences
    }
}
