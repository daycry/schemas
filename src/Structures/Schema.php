<?php

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Daycry\Schemas\Structures;

use Daycry\Schemas\Reader\ReaderInterface;

final class Schema extends Mergeable
{
    public Mergeable|ReaderInterface $tables;

    public function __construct(?ReaderInterface $reader = null)
    {
        $this->tables = $reader ?? new Mergeable();
    }
}
