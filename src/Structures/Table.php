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

class Table extends Mergeable
{
    /**
     * The table name.
     *
     * @var ?string
     */
    public $name;

    /**
     * Whether the table is a pivot.
     *
     * @var bool
     */
    public $pivot = false;

    /**
     * The table's fields.
     *
     * @var Mergeable of Field objects
     */
    public $fields;

    /**
     * The table's indices.
     *
     * @var Mergeable of Index objects
     */
    public $indexes;

    /**
     * The table's foreign keys.
     *
     * @var Mergeable of ForeignKey objects
     */
    public $foreignKeys;

    /**
     * Relationships this table has with others
     *
     * @var Mergeable of Relations
     */
    public $relations;

    public function __construct($name = null)
    {
        $this->name        = $name;
        $this->fields      = new Mergeable();
        $this->indexes     = new Mergeable();
        $this->foreignKeys = new Mergeable();
        $this->relations   = new Mergeable();
    }
}
