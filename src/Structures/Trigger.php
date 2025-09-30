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
final class Trigger extends Mergeable
{
    public ?string $name   = null;
    public ?string $table  = null;
    public ?string $timing = null;

    /**
     * @var list<string>
     */
    public array $events = [];

    public ?string $definition = null;
    public ?int $order         = null;
    public ?string $condition  = null;
    public bool $enabled       = true;
    public ?string $comment    = null;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
