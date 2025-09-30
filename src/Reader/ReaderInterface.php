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

namespace Daycry\Schemas\Reader;

use Countable;
use IteratorAggregate;

/**
 * @extends IteratorAggregate<string,mixed>
 */
interface ReaderInterface extends Countable, IteratorAggregate
{
    /**
     * Indicate whether the reader is in a state to be used
     */
    public function ready(): bool;

    /**
     * Fetch specified tables into the scaffold
     *
     * @param list<string>|string $tables
     */
    public function fetch(array|string $tables): static;

    /**
     * Fetch all available tables into the scaffold
     */
    public function fetchAll(): static;
}
