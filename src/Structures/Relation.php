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

final class Relation extends Mergeable
{
    public string $table   = '';
    public string $type    = '';
    public bool $singleton = false;

    /**
     * Pivot definitions between tables.
     * Each pivot is either a 3 or 4 element array of table/field pairs.
     * Allow nullable where some drivers don't supply column names.
     *
     * @var list<array{0:string|null,1:string|null,2?:string|null,3?:string|null}>
     */
    public array $pivots = [];

    public ?string $pivot = null; // for some handlers referencing pivot
    public ?string $field = null; // referencing field name in some relations
}
