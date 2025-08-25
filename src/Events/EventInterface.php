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
 * Base interface for all events in the Schemas system
 */
interface EventInterface
{
    /**
     * Get the event name
     */
    public function getName(): string;

    /**
     * Get event data
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Set event data
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void;

    /**
     * Get specific data value
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set specific data value
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if data key exists
     */
    public function has(string $key): bool;

    /**
     * Check if event is stoppable
     */
    public function isPropagationStopped(): bool;

    /**
     * Stop event propagation
     */
    public function stopPropagation(): void;

    /**
     * Get event timestamp
     */
    public function getTimestamp(): float;

    /**
     * Get event creation time (alias for getTimestamp)
     */
    public function getCreatedAt(): float;

    /**
     * Get event target (optional)
     */
    public function getTarget(): ?object;
}
