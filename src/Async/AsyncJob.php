<?php

declare(strict_types=1);

namespace Daycry\Schemas\Async;

/**
 * Represents an asynchronous job
 */
class AsyncJob
{
    private string $id;
    private string $type;
    private $data;
    private array $options;
    private $callback;
    private string $status;
    private int $priority;
    private float $createdAt;
    private float $startedAt;
    private float $completedAt;
    private $result;
    private ?array $error;
    private array $progress;
    private array $metadata;

    public function __construct(
        string $id,
        string $type,
        $data,
        array $options = [],
        ?callable $callback = null,
        int $priority = AsyncHandlerInterface::PRIORITY_NORMAL
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
        $this->options = $options;
        $this->callback = $callback;
        $this->status = AsyncHandlerInterface::STATUS_PENDING;
        $this->priority = $priority;
        $this->createdAt = microtime(true);
        $this->startedAt = 0.0;
        $this->completedAt = 0.0;
        $this->result = null;
        $this->error = null;
        $this->progress = [
            'current' => 0,
            'total' => 100,
            'percentage' => 0.0,
            'message' => 'Waiting to start...',
        ];
        $this->metadata = [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        
        switch ($status) {
            case AsyncHandlerInterface::STATUS_RUNNING:
                $this->startedAt = microtime(true);
                break;
            case AsyncHandlerInterface::STATUS_COMPLETED:
            case AsyncHandlerInterface::STATUS_FAILED:
            case AsyncHandlerInterface::STATUS_CANCELLED:
                $this->completedAt = microtime(true);
                break;
        }
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getCreatedAt(): float
    {
        return $this->createdAt;
    }

    public function getStartedAt(): float
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): float
    {
        return $this->completedAt;
    }

    public function getDuration(): float
    {
        if ($this->startedAt === 0.0) {
            return 0.0;
        }
        
        $endTime = $this->completedAt > 0 ? $this->completedAt : microtime(true);
        return $endTime - $this->startedAt;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result): void
    {
        $this->result = $result;
    }

    public function getError(): ?array
    {
        return $this->error;
    }

    public function setError(array $error): void
    {
        $this->error = $error;
    }

    public function getProgress(): array
    {
        return $this->progress;
    }

    public function setProgress(int $current, int $total = null, string $message = null): void
    {
        if ($total !== null) {
            $this->progress['total'] = $total;
        }
        
        $this->progress['current'] = $current;
        $this->progress['percentage'] = $this->progress['total'] > 0 
            ? ($current / $this->progress['total']) * 100 
            : 0;
            
        if ($message !== null) {
            $this->progress['message'] = $message;
        }
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            AsyncHandlerInterface::STATUS_COMPLETED,
            AsyncHandlerInterface::STATUS_FAILED,
            AsyncHandlerInterface::STATUS_CANCELLED,
        ]);
    }

    public function isRunning(): bool
    {
        return $this->status === AsyncHandlerInterface::STATUS_RUNNING;
    }

    public function isPending(): bool
    {
        return $this->status === AsyncHandlerInterface::STATUS_PENDING;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'created_at' => $this->createdAt,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'duration' => $this->getDuration(),
            'progress' => $this->progress,
            'metadata' => $this->metadata,
            'has_result' => $this->result !== null,
            'has_error' => $this->error !== null,
        ];
    }
}
