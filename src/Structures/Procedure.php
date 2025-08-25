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
 * Database Stored Procedure Structure
 * 
 * Represents a stored procedure or function
 */
class Procedure extends Mergeable
{
    /**
     * The procedure name
     *
     * @var ?string
     */
    public $name;

    /**
     * The procedure type (PROCEDURE, FUNCTION)
     *
     * @var string
     */
    public $type = 'PROCEDURE';

    /**
     * The procedure definition/SQL
     *
     * @var ?string
     */
    public $definition;

    /**
     * Input parameters
     *
     * @var array<array>
     */
    public $parameters = [];

    /**
     * Return type (for functions)
     *
     * @var ?string
     */
    public $returnType = null;

    /**
     * Security type (DEFINER, INVOKER)
     *
     * @var ?string
     */
    public $security = null;

    /**
     * Language (SQL, PLpgSQL, etc.)
     *
     * @var string
     */
    public $language = 'SQL';

    /**
     * Whether the procedure is deterministic
     *
     * @var bool
     */
    public $deterministic = false;

    /**
     * Data access characteristics
     *
     * @var ?string
     */
    public $dataAccess = null;

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
