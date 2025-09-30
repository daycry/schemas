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
final class View extends Mergeable
{
    public ?string $name       = null;
    public ?string $definition = null;

    /**
     * @var list<string>
     */
    public array $dependencies = [];

    public bool $updatable      = false;
    public string $type         = 'VIEW';
    public ?string $security    = null;
    public ?string $checkOption = null;
    public Mergeable $fields;
    public ?string $comment = null;

    public function __construct(?string $name = null)
    {
        $this->name   = $name;
        $this->fields = new Mergeable();
    }
}
