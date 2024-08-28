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

namespace Tests\Support\Schemas\Good;

use Daycry\Schemas\Structures\Relation;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;

// SCHEMA
$schema = new Schema();

// TABLES
$schema->tables->products = new Table('products');
$schema->tables->workers  = new Table('workers');

// RELATIONS
// Products->Workers
$relation         = new Relation();
$relation->type   = 'belongsTo';
$relation->table  = 'workers';
$relation->pivots = [
    ['products', 'worker_id', 'workers', 'id'],
];
$schema->tables->products->relations->workers = $relation;

// Workers->Products
$relation         = new Relation();
$relation->type   = 'hasMany';
$relation->table  = 'products';
$relation->pivots = [
    ['workers', 'id', 'products', 'worker_id'],
];
$schema->tables->workers->relations->products = $relation;

// CLEANUP
unset($relation);
