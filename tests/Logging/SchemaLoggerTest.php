<?php

declare(strict_types=1);

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Daycry\Schemas\Logging\SchemaLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tests\Support\TestCase;

/**
 * Simple test logger for testing purposes
 */
class TestLogger implements LoggerInterface
{
    public array $records = [];

    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }

    public function hasInfoRecords(): bool
    {
        return $this->hasRecordsOfLevel(LogLevel::INFO);
    }

    public function hasErrorRecords(): bool
    {
        return $this->hasRecordsOfLevel(LogLevel::ERROR);
    }

    public function hasWarningRecords(): bool
    {
        return $this->hasRecordsOfLevel(LogLevel::WARNING);
    }

    public function hasDebugRecords(): bool
    {
        return $this->hasRecordsOfLevel(LogLevel::DEBUG);
    }

    public function hasNoticeRecords(): bool
    {
        return $this->hasRecordsOfLevel(LogLevel::NOTICE);
    }

    private function hasRecordsOfLevel(string $level): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === $level) {
                return true;
            }
        }
        return false;
    }
}

/**
 * @internal
 */
final class SchemaLoggerTest extends TestCase
{
    protected SchemaLogger $logger;
    protected TestLogger $testLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testLogger = new TestLogger();
        $this->logger = new SchemaLogger($this->testLogger);
    }

    public function testOperationLogging(): void
    {
        $operationId = $this->logger->logOperationStart('draft', ['handler' => 'database']);
        $this->assertIsString($operationId);
        
        $this->logger->logOperationEnd($operationId, true, ['tables_processed' => 5]);
        
        $this->assertTrue($this->testLogger->hasInfoRecords());
        $this->assertStringContainsString('Schema operation started: draft', $this->testLogger->records[0]['message']);
        $this->assertStringContainsString('Schema operation completed: draft', $this->testLogger->records[1]['message']);
    }

    public function testErrorLogging(): void
    {
        $exception = new \RuntimeException('Test error');
        $this->logger->logError('Schema validation failed', ['table' => 'users'], $exception);
        
        $this->assertTrue($this->testLogger->hasErrorRecords());
        $record = $this->testLogger->records[0];
        $this->assertEquals('Schema validation failed', $record['message']);
        $this->assertArrayHasKey('exception', $record['context']);
        $this->assertEquals('RuntimeException', $record['context']['exception']['class']);
    }

    public function testWarningLogging(): void
    {
        $this->logger->logWarning('Circular reference detected', ['table' => 'users']);
        
        $this->assertTrue($this->testLogger->hasWarningRecords());
        $this->assertStringContainsString('Circular reference detected', $this->testLogger->records[0]['message']);
    }

    public function testDebugLogging(): void
    {
        $this->logger->logDebug('Processing table structure', ['table' => 'users']);
        
        $this->assertTrue($this->testLogger->hasDebugRecords());
        $this->assertStringContainsString('Processing table structure', $this->testLogger->records[0]['message']);
    }

    public function testPerformanceLogging(): void
    {
        $this->logger->logPerformance('database_scan_time', 150.5, 'ms', ['tables' => 10]);
        
        $this->assertTrue($this->testLogger->hasInfoRecords());
        $record = $this->testLogger->records[0];
        $this->assertStringContainsString('Performance metric: database_scan_time', $record['message']);
        $this->assertEquals(150.5, $record['context']['value']);
        $this->assertEquals('ms', $record['context']['unit']);
    }

    public function testSchemaChangeLogging(): void
    {
        $this->logger->logSchemaChange('table_added', 'new_table', ['columns' => 5]);
        
        $this->assertTrue($this->testLogger->hasNoticeRecords());
        $record = $this->testLogger->records[0];
        $this->assertStringContainsString('Schema change detected: table_added on new_table', $record['message']);
        $this->assertEquals('table_added', $record['context']['change_type']);
        $this->assertEquals('new_table', $record['context']['table_name']);
    }

    public function testMetricsCollection(): void
    {
        $operationId1 = $this->logger->logOperationStart('draft');
        usleep(1000); // Small delay to ensure measurable duration
        $this->logger->logOperationEnd($operationId1, true);
        
        $operationId2 = $this->logger->logOperationStart('archive');
        usleep(1000);
        $this->logger->logOperationEnd($operationId2, false);
        
        $metrics = $this->logger->getMetrics();
        $this->assertCount(2, $metrics);
        
        $this->assertEquals('draft', $metrics[0]['operation']);
        $this->assertTrue($metrics[0]['success']);
        $this->assertGreaterThan(0, $metrics[0]['duration']);
        
        $this->assertEquals('archive', $metrics[1]['operation']);
        $this->assertFalse($metrics[1]['success']);
    }

    public function testMetricsSummary(): void
    {
        // Create several operations
        for ($i = 0; $i < 3; $i++) {
            $operationId = $this->logger->logOperationStart('draft');
            usleep(1000);
            $this->logger->logOperationEnd($operationId, $i !== 1); // Make second one fail
        }
        
        $summary = $this->logger->getMetricsSummary();
        
        $this->assertEquals(3, $summary['total_operations']);
        $this->assertEquals(2, $summary['successful_operations']);
        $this->assertEquals(1, $summary['failed_operations']);
        $this->assertGreaterThan(0, $summary['total_duration']);
        $this->assertGreaterThan(0, $summary['avg_duration']);
        $this->assertArrayHasKey('draft', $summary['operations_by_type']);
        $this->assertEquals(3, $summary['operations_by_type']['draft']['count']);
    }

    public function testSessionId(): void
    {
        $sessionId1 = $this->logger->getSessionId();
        $logger2 = new SchemaLogger($this->testLogger);
        $sessionId2 = $logger2->getSessionId();
        
        $this->assertNotEquals($sessionId1, $sessionId2);
        $this->assertStringStartsWith('schema_', $sessionId1);
    }

    public function testMetricsExport(): void
    {
        $operationId = $this->logger->logOperationStart('test');
        $this->logger->logOperationEnd($operationId, true);
        
        $export = $this->logger->exportMetrics();
        
        $this->assertArrayHasKey('session_id', $export);
        $this->assertArrayHasKey('timestamp', $export);
        $this->assertArrayHasKey('metrics', $export);
        $this->assertArrayHasKey('summary', $export);
        $this->assertCount(1, $export['metrics']);
    }

    public function testClearMetrics(): void
    {
        $operationId = $this->logger->logOperationStart('test');
        $this->logger->logOperationEnd($operationId, true);
        
        $this->assertCount(1, $this->logger->getMetrics());
        
        $this->logger->clearMetrics();
        $this->assertCount(0, $this->logger->getMetrics());
    }

    public function testOperationEndWithoutStart(): void
    {
        $this->logger->logOperationEnd('nonexistent_operation_id');
        
        $this->assertTrue($this->testLogger->hasWarningRecords());
        $this->assertStringContainsString('Attempted to end unknown operation', $this->testLogger->records[0]['message']);
    }
}
