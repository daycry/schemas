<?php

declare(strict_types=1);

namespace Daycry\Schemas\Async;

use Daycry\Schemas\Events\BaseEvent;
use Daycry\Schemas\Events\EventInterface;

/**
 * Base class for asynchronous handlers
 */
abstract class BaseAsyncHandler implements AsyncHandlerInterface
{
    protected array $config;
    protected array $jobs = [];
    protected array $queue = [];
    protected bool $isProcessing = false;
    protected int $maxConcurrentJobs = 3;
    protected int $activeJobs = 0;
    protected array $eventListeners = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->maxConcurrentJobs = $this->config['max_concurrent_jobs'] ?? 3;
    }

    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'max_concurrent_jobs' => 3,
            'job_timeout' => 300, // 5 minutes
            'max_retries' => 3,
            'retry_delay' => 5, // seconds
            'cleanup_interval' => 3600, // 1 hour
            'enable_events' => true,
        ];
    }

    /**
     * Abstract method to process the actual work
     */
    abstract protected function doProcess(AsyncJob $job): void;

    public function processAsync($data, array $options = [], ?callable $callback = null): string
    {
        $jobId = $this->generateJobId();
        $operationType = $options['type'] ?? 'default';
        $priority = $options['priority'] ?? self::PRIORITY_NORMAL;

        if (!$this->supports($operationType)) {
            throw new \InvalidArgumentException("Operation type '{$operationType}' is not supported");
        }

        $job = new AsyncJob($jobId, $operationType, $data, $options, $callback, $priority);
        $this->jobs[$jobId] = $job;
        $this->addToQueue($job);

        $this->emitEvent('job.created', ['job' => $job]);

        if (!$this->isProcessing) {
            $this->processQueue();
        }

        return $jobId;
    }

    public function getStatus(string $jobId): array
    {
        if (!isset($this->jobs[$jobId])) {
            throw new \InvalidArgumentException("Job '{$jobId}' not found");
        }

        return $this->jobs[$jobId]->toArray();
    }

    public function getProgress(string $jobId): array
    {
        if (!isset($this->jobs[$jobId])) {
            throw new \InvalidArgumentException("Job '{$jobId}' not found");
        }

        return $this->jobs[$jobId]->getProgress();
    }

    public function cancel(string $jobId): bool
    {
        if (!isset($this->jobs[$jobId])) {
            return false;
        }

        $job = $this->jobs[$jobId];

        if ($job->isCompleted()) {
            return false;
        }

        // Remove from queue if pending
        if ($job->isPending()) {
            $this->removeFromQueue($job);
        }

        $job->setStatus(self::STATUS_CANCELLED);
        $this->emitEvent('job.cancelled', ['job' => $job]);

        return true;
    }

    public function waitFor(string $jobId, int $timeout = 0)
    {
        if (!isset($this->jobs[$jobId])) {
            throw new \InvalidArgumentException("Job '{$jobId}' not found");
        }

        $job = $this->jobs[$jobId];
        $startTime = time();

        while (!$job->isCompleted()) {
            if ($timeout > 0 && (time() - $startTime) >= $timeout) {
                throw new \RuntimeException("Timeout waiting for job '{$jobId}'");
            }

            usleep(100000); // Sleep 100ms
            $this->processQueue();
        }

        if ($job->getStatus() === self::STATUS_FAILED) {
            throw new \RuntimeException("Job '{$jobId}' failed: " . json_encode($job->getError()));
        }

        return $job->getResult();
    }

    public function getResult(string $jobId)
    {
        if (!isset($this->jobs[$jobId])) {
            throw new \InvalidArgumentException("Job '{$jobId}' not found");
        }

        $job = $this->jobs[$jobId];

        if (!$job->isCompleted()) {
            throw new \RuntimeException("Job '{$jobId}' is not completed yet");
        }

        if ($job->getStatus() === self::STATUS_FAILED) {
            throw new \RuntimeException("Job '{$jobId}' failed: " . json_encode($job->getError()));
        }

        return $job->getResult();
    }

    public function getError(string $jobId): ?array
    {
        if (!isset($this->jobs[$jobId])) {
            throw new \InvalidArgumentException("Job '{$jobId}' not found");
        }

        return $this->jobs[$jobId]->getError();
    }

    public function setPriority(string $jobId, int $priority): bool
    {
        if (!isset($this->jobs[$jobId])) {
            return false;
        }

        $job = $this->jobs[$jobId];

        if ($job->isRunning() || $job->isCompleted()) {
            return false;
        }

        $job->setPriority($priority);
        $this->sortQueue();

        return true;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        $this->maxConcurrentJobs = $this->config['max_concurrent_jobs'] ?? 3;
    }

    public function cleanup(int $olderThanSeconds = 3600): int
    {
        $cutoffTime = time() - $olderThanSeconds;
        $cleaned = 0;

        foreach ($this->jobs as $jobId => $job) {
            if ($job->isCompleted() && $job->getCompletedAt() < $cutoffTime) {
                unset($this->jobs[$jobId]);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Process the job queue
     */
    protected function processQueue(): void
    {
        if ($this->isProcessing) {
            return;
        }

        $this->isProcessing = true;

        try {
            while (!empty($this->queue) && $this->activeJobs < $this->maxConcurrentJobs) {
                $job = array_shift($this->queue);
                $this->processJob($job);
            }
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Process a single job
     */
    protected function processJob(AsyncJob $job): void
    {
        $this->activeJobs++;
        $job->setStatus(self::STATUS_RUNNING);
        $this->emitEvent('job.started', ['job' => $job]);

        try {
            $this->doProcess($job);
            $job->setStatus(self::STATUS_COMPLETED);
            $this->emitEvent('job.completed', ['job' => $job]);

            // Execute callback if provided
            $callback = $job->getCallback();
            if ($callback && is_callable($callback)) {
                call_user_func($callback, $job->getResult(), null, $job);
            }
        } catch (\Throwable $e) {
            $error = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];

            $job->setError($error);
            $job->setStatus(self::STATUS_FAILED);
            $this->emitEvent('job.failed', ['job' => $job, 'error' => $error]);

            // Execute callback with error if provided
            $callback = $job->getCallback();
            if ($callback && is_callable($callback)) {
                call_user_func($callback, null, $e, $job);
            }
        } finally {
            $this->activeJobs--;
        }
    }

    /**
     * Add job to queue and sort by priority
     */
    protected function addToQueue(AsyncJob $job): void
    {
        $this->queue[] = $job;
        $this->sortQueue();
    }

    /**
     * Remove job from queue
     */
    protected function removeFromQueue(AsyncJob $job): void
    {
        foreach ($this->queue as $index => $queuedJob) {
            if ($queuedJob->getId() === $job->getId()) {
                array_splice($this->queue, $index, 1);
                break;
            }
        }
    }

    /**
     * Sort queue by priority (highest first)
     */
    protected function sortQueue(): void
    {
        usort($this->queue, function (AsyncJob $a, AsyncJob $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    /**
     * Generate unique job ID
     */
    protected function generateJobId(): string
    {
        return uniqid('job_', true);
    }

    /**
     * Emit an event
     */
    protected function emitEvent(string $eventName, array $data = []): void
    {
        if (!$this->config['enable_events']) {
            return;
        }

        $event = new BaseEvent($eventName, $data);
        
        foreach ($this->eventListeners as $listener) {
            if (is_callable($listener)) {
                call_user_func($listener, $event);
            }
        }
    }

    /**
     * Add event listener
     */
    public function addEventListener(callable $listener): void
    {
        $this->eventListeners[] = $listener;
    }

    /**
     * Get statistics about jobs
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_jobs' => count($this->jobs),
            'queued_jobs' => count($this->queue),
            'active_jobs' => $this->activeJobs,
            'by_status' => [],
            'by_type' => [],
        ];

        foreach ($this->jobs as $job) {
            $status = $job->getStatus();
            $type = $job->getType();

            $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
        }

        return $stats;
    }
}
