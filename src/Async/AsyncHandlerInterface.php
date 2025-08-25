<?php

declare(strict_types=1);

namespace Daycry\Schemas\Async;

/**
 * Interface for asynchronous handlers
 */
interface AsyncHandlerInterface
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 1;
    public const PRIORITY_NORMAL = 5;
    public const PRIORITY_HIGH = 10;
    public const PRIORITY_URGENT = 15;

    /**
     * Process the operation asynchronously
     *
     * @param mixed $data The data to process
     * @param array $options Processing options
     * @param callable|null $callback Callback to execute when completed
     * @return string Job ID for tracking
     */
    public function processAsync($data, array $options = [], ?callable $callback = null): string;

    /**
     * Get the current status of a job
     *
     * @param string $jobId
     * @return array Status information
     */
    public function getStatus(string $jobId): array;

    /**
     * Get progress information for a job
     *
     * @param string $jobId
     * @return array Progress information (current, total, percentage, message)
     */
    public function getProgress(string $jobId): array;

    /**
     * Cancel a running job
     *
     * @param string $jobId
     * @return bool True if cancelled successfully
     */
    public function cancel(string $jobId): bool;

    /**
     * Wait for a job to complete
     *
     * @param string $jobId
     * @param int $timeout Timeout in seconds (0 = no timeout)
     * @return mixed Job result
     */
    public function waitFor(string $jobId, int $timeout = 0);

    /**
     * Get result of completed job
     *
     * @param string $jobId
     * @return mixed Job result
     */
    public function getResult(string $jobId);

    /**
     * Get error information for failed job
     *
     * @param string $jobId
     * @return array|null Error information
     */
    public function getError(string $jobId): ?array;

    /**
     * Set job priority
     *
     * @param string $jobId
     * @param int $priority
     * @return bool
     */
    public function setPriority(string $jobId, int $priority): bool;

    /**
     * Get handler configuration
     *
     * @return array
     */
    public function getConfig(): array;

    /**
     * Set handler configuration
     *
     * @param array $config
     * @return void
     */
    public function setConfig(array $config): void;

    /**
     * Check if handler supports the given operation type
     *
     * @param string $operationType
     * @return bool
     */
    public function supports(string $operationType): bool;

    /**
     * Get list of supported operation types
     *
     * @return array
     */
    public function getSupportedOperations(): array;

    /**
     * Clean up completed/failed jobs older than specified time
     *
     * @param int $olderThanSeconds
     * @return int Number of jobs cleaned up
     */
    public function cleanup(int $olderThanSeconds = 3600): int;
}
