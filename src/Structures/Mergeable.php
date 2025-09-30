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

namespace Daycry\Schemas\Structures;

use AllowDynamicProperties;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Dynamic merge container.
 *
 * @implements IteratorAggregate<int|string, mixed>
 */
#[AllowDynamicProperties]
class Mergeable implements Countable, IteratorAggregate
{
    /**
     * Merge two structures together.
     */
    public function merge(?Mergeable $object): Mergeable
    {
        if (null === $object) {
            return $this;
        }

        foreach ($object as $key => $item) {
            if (! isset($this->{$key})) {
                $this->{$key} = $item;
            } elseif ($item instanceof Mergeable && $this->{$key} instanceof Mergeable) {
                $this->{$key}->merge($item);
            } elseif (is_array($item) && is_array($this->{$key})) {
                $this->{$key} = array_merge($this->{$key}, $item);
            } elseif ($item instanceof Mergeable && $this->{$key} instanceof Mergeable) {
                // Nested mergeables handled above but keep branch explicit
                $this->{$key}->merge($item);
            } else {
                $this->{$key} = $item;
            }
        }

        return $this;
    }

    /**
     * Magic getter to prevent exceptions on missing property checks.
     *
     * @param mixed $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * Magic checker to match the getter.
     *
     * @param mixed $name
     */
    public function __isset($name): bool
    {
        return property_exists($this, $name);
    }

    /**
     * Specify count of public properties to satisfy Countable.
     *
     * @return int Number of public properties
     */
    public function count(): int
    {
        return count(get_object_vars($this));
    }

    /**
     * Use simple properties array to satisfy Iterable.
     *
     * @return ArrayIterator
     */
    /**
     * @return Traversable<int|string, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator(get_object_vars($this));
    }
}
