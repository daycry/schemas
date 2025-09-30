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

final class Table extends Mergeable
{
    public ?string $name = null;
    public bool $pivot   = false;
    public Mergeable $fields;
    public Mergeable $indexes;
    public Mergeable $foreignKeys;
    public Mergeable $relations;
    public ?string $comment   = null;
    public ?string $engine    = null;
    public ?string $collation = null;

    public function __construct(?string $name = null)
    {
        $this->name        = $name;
        $this->fields      = new Mergeable();
        $this->indexes     = new Mergeable();
        $this->foreignKeys = new Mergeable();
        $this->relations   = new Mergeable();
    }
}
