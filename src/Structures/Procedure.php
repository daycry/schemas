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
final class Procedure extends Mergeable
{
    public ?string $name       = null;
    public string $type        = 'PROCEDURE';
    public ?string $definition = null;

    /**
     * @var list<array<string,mixed>>
     */
    public array $parameters = [];

    public ?string $returnType = null;
    public ?string $security   = null;
    public string $language    = 'SQL';
    public bool $deterministic = false;
    public ?string $dataAccess = null;
    public ?string $comment    = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
