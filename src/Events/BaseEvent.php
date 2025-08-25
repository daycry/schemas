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

namespace Daycry\Schemas\Events;

/**
 * Base event class for the Schemas system
 */
class BaseEvent implements EventInterface
{
    protected string $name;
    protected array $data;
    protected bool $propagationStopped = false;
    protected float $timestamp;
    protected ?object $target;

    public function __construct(string $name, array $data = [], ?object $target = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->target = $target;
        $this->timestamp = microtime(true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    public function getCreatedAt(): float
    {
        return $this->timestamp;
    }

    public function getTarget(): ?object
    {
        return $this->target;
    }

    public function setTarget(?object $target): void
    {
        $this->target = $target;
    }
}
