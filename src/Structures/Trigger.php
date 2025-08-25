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
 * Database Trigger Structure
 * 
 * Represents a database trigger
 */
class Trigger extends Mergeable
{
    /**
     * The trigger name
     *
     * @var ?string
     */
    public $name;

    /**
     * The table this trigger is attached to
     *
     * @var ?string
     */
    public $table;

    /**
     * When the trigger fires (BEFORE, AFTER, INSTEAD OF)
     *
     * @var ?string
     */
    public $timing;

    /**
     * The events that fire the trigger (INSERT, UPDATE, DELETE)
     *
     * @var array<string>
     */
    public $events = [];

    /**
     * The trigger definition/SQL
     *
     * @var ?string
     */
    public $definition;

    /**
     * Trigger order/position
     *
     * @var ?int
     */
    public $order = null;

    /**
     * Condition for firing (WHEN clause)
     *
     * @var ?string
     */
    public $condition = null;

    /**
     * Whether the trigger is enabled
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * Comment/description
     *
     * @var ?string
     */
    public $comment = null;

    public function __construct($name = null)
    {
        $this->name = $name;
    }
}
