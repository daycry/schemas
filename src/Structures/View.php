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

/**
 * Database View Structure
 * 
 * Represents a database view with its definition and dependencies
 */
class View extends Mergeable
{
    /**
     * The view name
     *
     * @var ?string
     */
    public $name;

    /**
     * The view definition/SQL
     *
     * @var ?string
     */
    public $definition;

    /**
     * Tables this view depends on
     *
     * @var array<string>
     */
    public $dependencies = [];

    /**
     * Whether the view is updatable
     *
     * @var bool
     */
    public $updatable = false;

    /**
     * View type (e.g., 'VIEW', 'MATERIALIZED VIEW')
     *
     * @var string
     */
    public $type = 'VIEW';

    /**
     * Security type (DEFINER, INVOKER)
     *
     * @var ?string
     */
    public $security = null;

    /**
     * Check option (NONE, LOCAL, CASCADED)
     *
     * @var ?string
     */
    public $checkOption = null;

    /**
     * View columns information
     *
     * @var Mergeable of Field objects
     */
    public $fields;

    public function __construct($name = null)
    {
        $this->name = $name;
        $this->fields = new Mergeable();
    }
}
