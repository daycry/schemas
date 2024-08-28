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

namespace Tests\Support\Schemas;

use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\Relation;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;

// SCHEMA
$schema = new Schema();

// TABLES
$schema->tables->factories = new Table('factories');
$schema->tables->machines  = new Table('machines');
$schema->tables->workers   = new Table('workers');

// Factories
foreach (['id', 'name', 'uid'] as $field) {
    $schema->tables->factories->fields->{$field} = new Field($field);
}

// Machines
foreach (['id', 'type', 'serial', 'factory_id'] as $field) {
    $schema->tables->machines->fields->{$field} = new Field($field);
}

// Workers
foreach (['id', 'firstname', 'lastname', 'role'] as $field) {
    $schema->tables->workers->fields->{$field} = new Field($field);
}

// RELATIONS

// Factories->Machines
$relation         = new Relation();
$relation->type   = 'hasMany';
$relation->table  = 'machines';
$relation->pivots = [
    ['factories', 'id', 'machines', 'factory_id'],
];
$schema->tables->factories->relations->machines = $relation;

// Machines->Factories
$relation         = new Relation();
$relation->type   = 'belongsTo';
$relation->table  = 'factories';
$relation->pivots = [
    ['machines', 'factory_id', 'factories', 'id'],
];
$schema->tables->machines->relations->factories = $relation;

// Factories->Workers
$relation         = new Relation();
$relation->type   = 'manyToMany';
$relation->table  = 'workers';
$relation->pivots = [
    ['factories', 'id', 'factories_workers', 'factory_id'],
    ['factories_workers', 'worker_id', 'workers', 'id'],
];
$schema->tables->factories->relations->workers = $relation;

// Workers->Factories
$relation         = new Relation();
$relation->type   = 'manyToMany';
$relation->table  = 'factories';
$relation->pivots = [
    ['workers', 'id', 'factories_workers', 'worker_id'],
    ['factories_workers', 'factory_id', 'factories', 'id'],
];
$schema->tables->workers->relations->factories = $relation;

// CLEANUP
unset($relation);
