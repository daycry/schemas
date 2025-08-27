<?php

declare(strict_types=1);

namespace Daycry\Schemas\Async;

use Daycry\Schemas\Async\Handlers\AsyncSchemaHandler;
use Daycry\Schemas\Async\AsyncHandlerInterface;
use Daycry\Schemas\Schemas;

/**
 * Manager for asynchronous operations
 */
class AsyncManager
{
    private array $handlers = [];
    private array $config;
    private Schemas $schemas;

    public function __construct(Schemas $schemas, array $config = [])
    {
        $this->schemas = $schemas;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->initializeHandlers();
    }

    /**
     * Get default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'default_handler' => 'schema',
            'handlers' => [
                'schema' => [
                    'class' => AsyncSchemaHandler::class,
                    'config' => [
                        'max_concurrent_jobs' => 3,
                        'job_timeout' => 300,
                    ],
                ],
            ],
            'cleanup_interval' => 3600,
            'enable_monitoring' => true,
        ];
    }

    /**
     * Initialize handlers
     */
    protected function initializeHandlers(): void
    {
        foreach ($this->config['handlers'] as $name => $handlerConfig) {
            $this->registerHandler($name, $handlerConfig);
        }
    }

    /**
     * Register a new handler
     */
    public function registerHandler(string $name, array $config): void
    {
        $className = $config['class'] ?? null;
        
        if (!$className || !class_exists($className)) {
            throw new \InvalidArgumentException("Handler class '{$className}' not found");
        }

        $handlerInstance = new $className($this->schemas, $config['config'] ?? []);
        
        if (!$handlerInstance instanceof AsyncHandlerInterface) {
            throw new \InvalidArgumentException("Handler must implement AsyncHandlerInterface");
        }

        $this->handlers[$name] = $handlerInstance;
    }

    /**
     * Get a handler by name
     */
    public function getHandler(string $name): AsyncHandlerInterface
    {
        if (!isset($this->handlers[$name])) {
            throw new \InvalidArgumentException("Handler '{$name}' not found");
        }

        return $this->handlers[$name];
    }

    /**
     * Get all registered handlers
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Process operation asynchronously
     */
    public function processAsync(
        string $operation,
        $data,
        array $options = [],
        ?callable $callback = null,
        string $handler = null
    ): string {
        $handlerName = $handler ?? $this->config['default_handler'];
        $asyncHandler = $this->getHandler($handlerName);

        $options['type'] = $operation;
        
        return $asyncHandler->processAsync($data, $options, $callback);
    }

    /**
     * Get job status from appropriate handler
     */
    public function getJobStatus(string $jobId, string $handler = null): array
    {
        if ($handler) {
            return $this->getHandler($handler)->getStatus($jobId);
        }

        // Search all handlers for the job
        foreach ($this->handlers as $asyncHandler) {
            try {
                return $asyncHandler->getStatus($jobId);
            } catch (\InvalidArgumentException $e) {
                // Job not found in this handler, continue searching
                continue;
            }
        }

        throw new \InvalidArgumentException("Job '{$jobId}' not found in any handler");
    }

    /**
     * Cancel a job
     */
    public function cancelJob(string $jobId, string $handler = null): bool
    {
        if ($handler) {
            return $this->getHandler($handler)->cancel($jobId);
        }

        // Try to cancel in all handlers
        foreach ($this->handlers as $asyncHandler) {
            try {
                if ($asyncHandler->cancel($jobId)) {
                    return true;
                }
            } catch (\InvalidArgumentException $e) {
                // Job not found in this handler, continue trying
                continue;
            }
        }

        return false;
    }

    /**
     * Wait for a job to complete
     */
    public function waitForJob(string $jobId, int $timeout = 0, string $handler = null)
    {
        if ($handler) {
            return $this->getHandler($handler)->waitFor($jobId, $timeout);
        }

        // Search all handlers for the job
        foreach ($this->handlers as $asyncHandler) {
            try {
                return $asyncHandler->waitFor($jobId, $timeout);
            } catch (\InvalidArgumentException $e) {
                // Job not found in this handler, continue searching
                continue;
            }
        }

        throw new \InvalidArgumentException("Job '{$jobId}' not found in any handler");
    }

    /**
     * Get job result
     */
    public function getJobResult(string $jobId, string $handler = null)
    {
        if ($handler) {
            return $this->getHandler($handler)->getResult($jobId);
        }

        // Search all handlers for the job
        foreach ($this->handlers as $asyncHandler) {
            try {
                return $asyncHandler->getResult($jobId);
            } catch (\InvalidArgumentException $e) {
                // Job not found in this handler, continue searching
                continue;
            }
        }

        throw new \InvalidArgumentException("Job '{$jobId}' not found in any handler");
    }

    /**
     * Get comprehensive statistics from all handlers
     */
    public function getStatistics(): array
    {
        $totalStats = [
            'handlers' => count($this->handlers),
            'total_jobs' => 0,
            'total_queued' => 0,
            'total_active' => 0,
            'by_handler' => [],
            'by_status' => [],
            'by_type' => [],
        ];

        foreach ($this->handlers as $name => $handler) {
            $handlerStats = $handler->getStatistics();
            $totalStats['by_handler'][$name] = $handlerStats;
            
            $totalStats['total_jobs'] += $handlerStats['total_jobs'];
            $totalStats['total_queued'] += $handlerStats['queued_jobs'];
            $totalStats['total_active'] += $handlerStats['active_jobs'];

            // Merge status counts
            foreach ($handlerStats['by_status'] as $status => $count) {
                $totalStats['by_status'][$status] = ($totalStats['by_status'][$status] ?? 0) + $count;
            }

            // Merge type counts
            foreach ($handlerStats['by_type'] as $type => $count) {
                $totalStats['by_type'][$type] = ($totalStats['by_type'][$type] ?? 0) + $count;
            }
        }

        return $totalStats;
    }

    /**
     * Cleanup old jobs from all handlers
     */
    public function cleanup(int $olderThanSeconds = null): int
    {
        $olderThanSeconds = $olderThanSeconds ?? $this->config['cleanup_interval'];
        $totalCleaned = 0;

        foreach ($this->handlers as $handler) {
            $totalCleaned += $handler->cleanup($olderThanSeconds);
        }

        return $totalCleaned;
    }

    /**
     * Check if operation is supported by any handler
     */
    public function isOperationSupported(string $operation): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($operation)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get handlers that support a specific operation
     */
    public function getHandlersForOperation(string $operation): array
    {
        $supportedHandlers = [];

        foreach ($this->handlers as $name => $handler) {
            if ($handler->supports($operation)) {
                $supportedHandlers[$name] = $handler;
            }
        }

        return $supportedHandlers;
    }

    /**
     * Get all supported operations across all handlers
     */
    public function getSupportedOperations(): array
    {
        $allOperations = [];

        foreach ($this->handlers as $name => $handler) {
            $operations = $handler->getSupportedOperations();
            foreach ($operations as $operation) {
                if (!in_array($operation, $allOperations)) {
                    $allOperations[] = $operation;
                }
            }
        }

        return $allOperations;
    }

    /**
     * Add event listener to all handlers
     */
    public function addEventListener(callable $listener): void
    {
        foreach ($this->handlers as $handler) {
            if (method_exists($handler, 'addEventListener')) {
                $handler->addEventListener($listener);
            }
        }
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        
        // Update handler configurations
        foreach ($this->handlers as $name => $handler) {
            if (isset($config['handlers'][$name]['config'])) {
                $handler->setConfig($config['handlers'][$name]['config']);
            }
        }
    }
}
