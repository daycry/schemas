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

use Daycry\Schemas\Validators\SchemaValidator;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\ForeignKey;
use Daycry\Schemas\Structures\Index;
use Daycry\Schemas\Structures\Mergeable;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SchemaValidatorTest extends TestCase
{
    protected SchemaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SchemaValidator();
    }

    public function testValidateValidSchema(): void
    {
        $schema = $this->createValidSchema();
        
        $result = $this->validator->validate($schema);
        
        $this->assertTrue($result);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidateForeignKeyConsistency(): void
    {
        $schema = $this->createSchemaWithInvalidForeignKey();
        
        $result = $this->validator->validate($schema);
        
        $this->assertFalse($result);
        $errors = $this->validator->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("references non-existent table", $errors[0]);
    }

    public function testDetectCircularReferences(): void
    {
        $schema = $this->createSchemaWithCircularReference();
        
        $this->validator->validate($schema);
        
        $warnings = $this->validator->getWarnings();
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString("Circular reference detected", $warnings[0]);
    }

    public function testValidateDataTypes(): void
    {
        $schema = $this->createSchemaWithSuboptimalDataTypes();
        
        $this->validator->validate($schema);
        
        $suggestions = $this->validator->getSuggestions();
        $this->assertNotEmpty($suggestions);
        $this->assertStringContainsString("could be VARCHAR", $suggestions[0]);
    }

    public function testValidateIndexes(): void
    {
        $schema = $this->createSchemaWithDuplicateIndexes();
        
        $this->validator->validate($schema);
        
        $warnings = $this->validator->getWarnings();
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString("Duplicate index detected", $warnings[0]);
    }

    public function testMissingPrimaryKeySuggestion(): void
    {
        $schema = $this->createSchemaWithoutPrimaryKey();
        
        $this->validator->validate($schema);
        
        $suggestions = $this->validator->getSuggestions();
        $this->assertNotEmpty($suggestions);
        $this->assertStringContainsString("should have a primary key", $suggestions[0]);
    }

    public function testMissingIndexOnForeignKey(): void
    {
        $schema = $this->createSchemaWithUnindexedForeignKey();
        
        $this->validator->validate($schema);
        
        $suggestions = $this->validator->getSuggestions();
        $this->assertNotEmpty($suggestions);
        $this->assertStringContainsString("should have an index", $suggestions[0]);
    }

    protected function createValidSchema(): Schema
    {
        $schema = new Schema();
        
        // Create users table
        $usersTable = new Table('users');
        $idField = new Field((object)[
            'name' => 'id',
            'type' => 'int',
            'primary_key' => true
        ]);
        $usersTable->fields->id = $idField;
        $schema->tables->users = $usersTable;
        
        return $schema;
    }

    protected function createSchemaWithInvalidForeignKey(): Schema
    {
        $schema = new Schema();
        
        // Create posts table with FK to non-existent users table
        $postsTable = new Table('posts');
        
        $fk = new ForeignKey((object)[
            'constraint_name' => 'fk_posts_user',
            'table_name' => 'posts',
            'column_name' => 'user_id',
            'foreign_table_name' => 'users',
            'foreign_column_name' => 'id'
        ]);
        
        $postsTable->foreignKeys->fk_posts_user = $fk;
        $schema->tables->posts = $postsTable;
        
        return $schema;
    }

    protected function createSchemaWithCircularReference(): Schema
    {
        $schema = new Schema();
        
        // Create two tables with circular relations
        $table1 = new Table('table1');
        $table2 = new Table('table2');
        
        $relation1 = (object)[
            'type' => 'belongsTo',
            'table' => 'table2',
            'singleton' => true
        ];
        
        $relation2 = (object)[
            'type' => 'belongsTo', 
            'table' => 'table1',
            'singleton' => true
        ];
        
        $table1->relations->table2 = $relation1;
        $table2->relations->table1 = $relation2;
        
        $schema->tables->table1 = $table1;
        $schema->tables->table2 = $table2;
        
        return $schema;
    }

    protected function createSchemaWithSuboptimalDataTypes(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $field = new Field((object)[
            'name' => 'short_text',
            'type' => 'TEXT',
            'max_length' => 50
        ]);
        
        $table->fields->short_text = $field;
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithDuplicateIndexes(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $index1 = new Index((object)[
            'name' => 'idx_name_1',
            'fields' => ['name']
        ]);
        
        $index2 = new Index((object)[
            'name' => 'idx_name_2', 
            'fields' => ['name']
        ]);
        
        $table->indexes->idx_name_1 = $index1;
        $table->indexes->idx_name_2 = $index2;
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithoutPrimaryKey(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $field = new Field((object)[
            'name' => 'name',
            'type' => 'varchar',
            'primary_key' => false
        ]);
        
        $table->fields->name = $field;
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithUnindexedForeignKey(): Schema
    {
        $schema = new Schema();
        
        // Create referenced table
        $usersTable = new Table('users');
        $idField = new Field((object)[
            'name' => 'id',
            'type' => 'int',
            'primary_key' => true
        ]);
        $usersTable->fields->id = $idField;
        $schema->tables->users = $usersTable;
        
        // Create table with unindexed foreign key
        $postsTable = new Table('posts');
        
        $userIdField = new Field((object)[
            'name' => 'user_id',
            'type' => 'int'
        ]);
        $postsTable->fields->user_id = $userIdField;
        
        $fk = new ForeignKey((object)[
            'constraint_name' => 'fk_posts_user',
            'table_name' => 'posts',
            'column_name' => 'user_id',
            'foreign_table_name' => 'users',
            'foreign_column_name' => 'id'
        ]);
        
        $postsTable->foreignKeys->fk_posts_user = $fk;
        $schema->tables->posts = $postsTable;
        
        return $schema;
    }
}
