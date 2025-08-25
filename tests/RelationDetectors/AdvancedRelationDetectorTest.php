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

use Daycry\Schemas\RelationDetectors\AdvancedRelationDetector;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\ForeignKey;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class AdvancedRelationDetectorTest extends TestCase
{
    protected AdvancedRelationDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AdvancedRelationDetector();
    }

    public function testDetectPolymorphicRelations(): void
    {
        $schema = $this->createSchemaWithPolymorphicRelation();
        
        $result = $this->detector->detectRelations($schema);
        
        $commentsTable = $result->tables->comments;
        $this->assertObjectHasProperty('commentable', $commentsTable->relations);
        
        $relation = $commentsTable->relations->commentable;
        $this->assertEquals('morphTo', $relation->type);
        $this->assertTrue($relation->polymorphic);
        $this->assertEquals('commentable_type', $relation->morphType);
        $this->assertEquals('commentable_id', $relation->morphId);
    }

    public function testDetectSelfReferencingRelations(): void
    {
        $schema = $this->createSchemaWithSelfReferencingRelation();
        
        $result = $this->detector->detectRelations($schema);
        
        $categoriesTable = $result->tables->categories;
        $this->assertObjectHasProperty('parent', $categoriesTable->relations);
        $this->assertObjectHasProperty('children', $categoriesTable->relations);
        
        $parentRelation = $categoriesTable->relations->parent;
        $this->assertEquals('belongsTo', $parentRelation->type);
        $this->assertTrue($parentRelation->selfReferencing);
        
        $childrenRelation = $categoriesTable->relations->children;
        $this->assertEquals('hasMany', $childrenRelation->type);
        $this->assertTrue($childrenRelation->selfReferencing);
    }

    public function testDetectImplicitManyToMany(): void
    {
        $schema = $this->createSchemaWithImplicitManyToMany();
        
        $result = $this->detector->detectRelations($schema);
        
        $usersTable = $result->tables->users;
        $rolesTable = $result->tables->roles;
        $pivotTable = $result->tables->user_roles;
        
        $this->assertTrue($pivotTable->pivot);
        $this->assertObjectHasProperty('roles', $usersTable->relations);
        $this->assertObjectHasProperty('users', $rolesTable->relations);
        
        $userRolesRelation = $usersTable->relations->roles;
        $this->assertEquals('manyToMany', $userRolesRelation->type);
        $this->assertEquals('user_roles', $userRolesRelation->pivot);
    }

    public function testDetectHierarchicalRelations(): void
    {
        $schema = $this->createSchemaWithHierarchicalStructure();
        
        $result = $this->detector->detectRelations($schema);
        
        $menuTable = $result->tables->menu_items;
        $this->assertObjectHasProperty('metadata', $menuTable);
        $this->assertObjectHasProperty('hierarchical', $menuTable->metadata);
        $this->assertEquals('nested_set', $menuTable->metadata->hierarchical['type']);
        $this->assertContains('lft', $menuTable->metadata->hierarchical['fields']);
        $this->assertContains('rgt', $menuTable->metadata->hierarchical['fields']);
    }

    public function testDetectValueObjects(): void
    {
        $schema = $this->createSchemaWithValueObjects();
        
        $result = $this->detector->detectRelations($schema);
        
        $customersTable = $result->tables->customers;
        $this->assertObjectHasProperty('metadata', $customersTable);
        $this->assertObjectHasProperty('value_objects', $customersTable->metadata);
        
        $valueObjects = $customersTable->metadata->value_objects;
        $this->assertArrayHasKey('billing_address', $valueObjects);
        $this->assertArrayHasKey('full_name', $valueObjects);
        
        $this->assertContains('billing_street', $valueObjects['billing_address']);
        $this->assertContains('billing_city', $valueObjects['billing_address']);
        $this->assertContains('first_name', $valueObjects['full_name']);
        $this->assertContains('last_name', $valueObjects['full_name']);
    }

    protected function createSchemaWithPolymorphicRelation(): Schema
    {
        $schema = new Schema();
        
        // Comments table with polymorphic relation
        $commentsTable = new Table('comments');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $commentsTable->fields->id = $idField;
        
        $commentableTypeField = new Field((object)['name' => 'commentable_type', 'type' => 'VARCHAR']);
        $commentsTable->fields->commentable_type = $commentableTypeField;
        
        $commentableIdField = new Field((object)['name' => 'commentable_id', 'type' => 'INT']);
        $commentsTable->fields->commentable_id = $commentableIdField;
        
        $textField = new Field((object)['name' => 'text', 'type' => 'TEXT']);
        $commentsTable->fields->text = $textField;
        
        $schema->tables->comments = $commentsTable;
        
        // Add some target tables
        $postsTable = new Table('posts');
        $postIdField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $postsTable->fields->id = $postIdField;
        $schema->tables->posts = $postsTable;
        
        $photosTable = new Table('photos');
        $photoIdField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $photosTable->fields->id = $photoIdField;
        $schema->tables->photos = $photosTable;
        
        return $schema;
    }

    protected function createSchemaWithSelfReferencingRelation(): Schema
    {
        $schema = new Schema();
        
        $categoriesTable = new Table('categories');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $categoriesTable->fields->id = $idField;
        
        $parentIdField = new Field((object)['name' => 'parent_id', 'type' => 'INT']);
        $categoriesTable->fields->parent_id = $parentIdField;
        
        $nameField = new Field((object)['name' => 'name', 'type' => 'VARCHAR']);
        $categoriesTable->fields->name = $nameField;
        
        $schema->tables->categories = $categoriesTable;
        
        return $schema;
    }

    protected function createSchemaWithImplicitManyToMany(): Schema
    {
        $schema = new Schema();
        
        // Users table
        $usersTable = new Table('users');
        $userIdField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $usersTable->fields->id = $userIdField;
        $schema->tables->users = $usersTable;
        
        // Roles table
        $rolesTable = new Table('roles');
        $roleIdField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $rolesTable->fields->id = $roleIdField;
        $schema->tables->roles = $rolesTable;
        
        // Pivot table (but not named as traditional pivot)
        $userRolesTable = new Table('user_roles');
        
        $userRefField = new Field((object)['name' => 'user_id', 'type' => 'INT']);
        $userRolesTable->fields->user_id = $userRefField;
        
        $roleRefField = new Field((object)['name' => 'role_id', 'type' => 'INT']);
        $userRolesTable->fields->role_id = $roleRefField;
        
        // Add foreign keys
        $userFk = new ForeignKey((object)[
            'constraint_name' => 'fk_user_roles_user',
            'column_name' => 'user_id',
            'foreign_table_name' => 'users',
            'foreign_column_name' => 'id'
        ]);
        $userRolesTable->foreignKeys->fk_user_roles_user = $userFk;
        
        $roleFk = new ForeignKey((object)[
            'constraint_name' => 'fk_user_roles_role',
            'column_name' => 'role_id',
            'foreign_table_name' => 'roles',
            'foreign_column_name' => 'id'
        ]);
        $userRolesTable->foreignKeys->fk_user_roles_role = $roleFk;
        
        $schema->tables->user_roles = $userRolesTable;
        
        return $schema;
    }

    protected function createSchemaWithHierarchicalStructure(): Schema
    {
        $schema = new Schema();
        
        $menuTable = new Table('menu_items');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $menuTable->fields->id = $idField;
        
        $lftField = new Field((object)['name' => 'lft', 'type' => 'INT']);
        $menuTable->fields->lft = $lftField;
        
        $rgtField = new Field((object)['name' => 'rgt', 'type' => 'INT']);
        $menuTable->fields->rgt = $rgtField;
        
        $titleField = new Field((object)['name' => 'title', 'type' => 'VARCHAR']);
        $menuTable->fields->title = $titleField;
        
        $schema->tables->menu_items = $menuTable;
        
        return $schema;
    }

    protected function createSchemaWithValueObjects(): Schema
    {
        $schema = new Schema();
        
        $customersTable = new Table('customers');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $customersTable->fields->id = $idField;
        
        // Name value object
        $firstNameField = new Field((object)['name' => 'first_name', 'type' => 'VARCHAR']);
        $customersTable->fields->first_name = $firstNameField;
        
        $lastNameField = new Field((object)['name' => 'last_name', 'type' => 'VARCHAR']);
        $customersTable->fields->last_name = $lastNameField;
        
        // Address value object
        $billingStreetField = new Field((object)['name' => 'billing_street', 'type' => 'VARCHAR']);
        $customersTable->fields->billing_street = $billingStreetField;
        
        $billingCityField = new Field((object)['name' => 'billing_city', 'type' => 'VARCHAR']);
        $customersTable->fields->billing_city = $billingCityField;
        
        $billingStateField = new Field((object)['name' => 'billing_state', 'type' => 'VARCHAR']);
        $customersTable->fields->billing_state = $billingStateField;
        
        $schema->tables->customers = $customersTable;
        
        return $schema;
    }
}
