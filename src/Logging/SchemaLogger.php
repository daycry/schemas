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

namespace Daycry\Schemas\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use CodeIgniter\Debug\Timer;

/**
 * Schema Logger and Metrics Collector
 * 
 * Provides comprehensive logging and performance metrics for schema operations
 */
class SchemaLogger
{
    protected ?LoggerInterface $logger;
    protected Timer $timer;
    protected array $metrics = [];
    protected array $operations = [];
    protected string $sessionId;

    public function __construct(?LoggerInterface $logger = null, ?Timer $timer = null)
    {
        $this->logger = $logger ?? service('logger', false);
        $this->timer = $timer ?? service('timer');
        $this->sessionId = uniqid('schema_', true);
    }

    /**
     * Log schema operation start
     */
    public function logOperationStart(string $operation, array $context = []): string
    {
        $operationId = uniqid($operation . '_', true);
        $timerName = "schema_{$operation}_{$operationId}";
        
        $this->timer->start($timerName);
        
        $this->operations[$operationId] = [
            'operation' => $operation,
            'timer_name' => $timerName,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context
        ];

        $this->log(LogLevel::INFO, "Schema operation started: {$operation}", [
            'operation_id' => $operationId,
            'session_id' => $this->sessionId,
            'context' => $context
        ]);

        return $operationId;
    }

    /**
     * Log schema operation completion
     */
    public function logOperationEnd(string $operationId, bool $success = true, array $context = []): void
    {
        if (!isset($this->operations[$operationId])) {
            $this->log(LogLevel::WARNING, "Attempted to end unknown operation: {$operationId}");
            return;
        }

        $operation = $this->operations[$operationId];
        $this->timer->stop($operation['timer_name']);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $metrics = [
            'operation' => $operation['operation'],
            'operation_id' => $operationId,
            'session_id' => $this->sessionId,
            'success' => $success,
            'duration' => $endTime - $operation['start_time'],
            'memory_used' => $endMemory - $operation['start_memory'],
            'peak_memory' => memory_get_peak_usage(true),
            'context' => array_merge($operation['context'], $context)
        ];

        // Store metrics
        $this->metrics[] = $metrics;

        $level = $success ? LogLevel::INFO : LogLevel::ERROR;
        $message = $success 
            ? "Schema operation completed: {$operation['operation']}"
            : "Schema operation failed: {$operation['operation']}";

        $this->log($level, $message, $metrics);

        unset($this->operations[$operationId]);
    }

    /**
     * Log error with context
     */
    public function logError(string $message, array $context = [], ?\Throwable $exception = null): void
    {
        $logContext = array_merge($context, [
            'session_id' => $this->sessionId
        ]);

        if ($exception) {
            $logContext['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        $this->log(LogLevel::ERROR, $message, $logContext);
    }

    /**
     * Log warning
     */
    public function logWarning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, array_merge($context, [
            'session_id' => $this->sessionId
        ]));
    }

    /**
     * Log debug information
     */
    public function logDebug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, array_merge($context, [
            'session_id' => $this->sessionId
        ]));
    }

    /**
     * Log performance metric
     */
    public function logPerformance(string $metric, float $value, string $unit = 'ms', array $context = []): void
    {
        $this->log(LogLevel::INFO, "Performance metric: {$metric}", [
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit,
            'session_id' => $this->sessionId,
            'context' => $context
        ]);
    }

    /**
     * Log schema change
     */
    public function logSchemaChange(string $changeType, string $tableName, array $details = []): void
    {
        $this->log(LogLevel::NOTICE, "Schema change detected: {$changeType} on {$tableName}", [
            'change_type' => $changeType,
            'table_name' => $tableName,
            'details' => $details,
            'session_id' => $this->sessionId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get collected metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get metrics summary
     */
    public function getMetricsSummary(): array
    {
        if (empty($this->metrics)) {
            return [];
        }

        $summary = [
            'total_operations' => count($this->metrics),
            'successful_operations' => 0,
            'failed_operations' => 0,
            'total_duration' => 0,
            'avg_duration' => 0,
            'max_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
            'total_memory' => 0,
            'avg_memory' => 0,
            'max_memory' => 0,
            'operations_by_type' => []
        ];

        foreach ($this->metrics as $metric) {
            // Count successes/failures
            if ($metric['success']) {
                $summary['successful_operations']++;
            } else {
                $summary['failed_operations']++;
            }

            // Duration stats
            $duration = $metric['duration'];
            $summary['total_duration'] += $duration;
            $summary['max_duration'] = max($summary['max_duration'], $duration);
            $summary['min_duration'] = min($summary['min_duration'], $duration);

            // Memory stats
            $memory = $metric['memory_used'];
            $summary['total_memory'] += $memory;
            $summary['max_memory'] = max($summary['max_memory'], $memory);

            // Operations by type
            $operation = $metric['operation'];
            if (!isset($summary['operations_by_type'][$operation])) {
                $summary['operations_by_type'][$operation] = [
                    'count' => 0,
                    'total_duration' => 0,
                    'avg_duration' => 0
                ];
            }
            $summary['operations_by_type'][$operation]['count']++;
            $summary['operations_by_type'][$operation]['total_duration'] += $duration;
        }

        // Calculate averages
        $summary['avg_duration'] = $summary['total_duration'] / $summary['total_operations'];
        $summary['avg_memory'] = $summary['total_memory'] / $summary['total_operations'];
        
        if ($summary['min_duration'] === PHP_FLOAT_MAX) {
            $summary['min_duration'] = 0;
        }

        // Calculate averages by operation type
        foreach ($summary['operations_by_type'] as $operation => &$stats) {
            $stats['avg_duration'] = $stats['total_duration'] / $stats['count'];
        }

        return $summary;
    }

    /**
     * Clear collected metrics
     */
    public function clearMetrics(): void
    {
        $this->metrics = [];
    }

    /**
     * Get current session ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Export metrics to array for storage or analysis
     */
    public function exportMetrics(): array
    {
        return [
            'session_id' => $this->sessionId,
            'timestamp' => date('Y-m-d H:i:s'),
            'metrics' => $this->metrics,
            'summary' => $this->getMetricsSummary()
        ];
    }

    /**
     * Log message with proper level
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
