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

use Daycry\Schemas\Analyzers\PerformanceAnalyzer;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\ForeignKey;
use Daycry\Schemas\Structures\Index;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PerformanceAnalyzerTest extends TestCase
{
    protected PerformanceAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new PerformanceAnalyzer();
    }

    public function testPerfectSchemaScore(): void
    {
        $schema = $this->createOptimalSchema();
        
        $result = $this->analyzer->analyze($schema);
        
        $this->assertEquals(100.0, $result['score']);
        $this->assertEmpty($result['issues']);
    }

    public function testMissingForeignKeyIndex(): void
    {
        $schema = $this->createSchemaWithUnindexedForeignKey();
        
        $result = $this->analyzer->analyze($schema);
        
        $this->assertLessThan(100.0, $result['score']);
        $issues = $result['issues'];
        $this->assertNotEmpty($issues);
        
        $fkIssue = array_filter($issues, fn($issue) => $issue['type'] === 'missing_fk_index');
        $this->assertNotEmpty($fkIssue);
        
        $recommendations = $result['recommendations'];
        $addIndexRec = array_filter($recommendations, fn($rec) => $rec['type'] === 'add_index');
        $this->assertNotEmpty($addIndexRec);
    }

    public function testDuplicateIndexes(): void
    {
        $schema = $this->createSchemaWithDuplicateIndexes();
        
        $result = $this->analyzer->analyze($schema);
        
        $issues = $result['issues'];
        $duplicateIssue = array_filter($issues, fn($issue) => $issue['type'] === 'duplicate_index');
        $this->assertNotEmpty($duplicateIssue);
        
        $recommendations = $result['recommendations'];
        $removeRec = array_filter($recommendations, fn($rec) => $rec['type'] === 'remove_duplicate_index');
        $this->assertNotEmpty($removeRec);
    }

    public function testWideIndex(): void
    {
        $schema = $this->createSchemaWithWideIndex();
        
        $result = $this->analyzer->analyze($schema);
        
        $issues = $result['issues'];
        $wideIndexIssue = array_filter($issues, fn($issue) => $issue['type'] === 'wide_index');
        $this->assertNotEmpty($wideIndexIssue);
        
        $issue = array_values($wideIndexIssue)[0];
        $this->assertEquals(6, $issue['details']['column_count']);
    }

    public function testInoefficientDataTypes(): void
    {
        $schema = $this->createSchemaWithInoefficientDataTypes();
        
        $result = $this->analyzer->analyze($schema);
        
        $issues = $result['issues'];
        $dataTypeIssue = array_filter($issues, fn($issue) => $issue['type'] === 'inefficient_text_type');
        $this->assertNotEmpty($dataTypeIssue);
        
        $issue = array_values($dataTypeIssue)[0];
        $this->assertEquals('VARCHAR(50)', $issue['details']['suggested_type']);
    }

    public function testMissingPrimaryKey(): void
    {
        $schema = $this->createSchemaWithoutPrimaryKey();
        
        $result = $this->analyzer->analyze($schema);
        
        $issues = $result['issues'];
        $pkIssue = array_filter($issues, fn($issue) => $issue['type'] === 'missing_primary_key');
        $this->assertNotEmpty($pkIssue);
        
        $this->assertEquals('high', array_values($pkIssue)[0]['severity']);
    }

    public function testTooManyColumns(): void
    {
        $schema = $this->createSchemaWithTooManyColumns();
        
        $result = $this->analyzer->analyze($schema);
        
        $issues = $result['issues'];
        $columnIssue = array_filter($issues, fn($issue) => $issue['type'] === 'too_many_columns');
        $this->assertNotEmpty($columnIssue);
        
        $issue = array_values($columnIssue)[0];
        $this->assertEquals(60, $issue['details']['column_count']);
    }

    public function testIsolatedTable(): void
    {
        $schema = $this->createSchemaWithIsolatedTable();
        
        $result = $this->analyzer->analyze($schema);
        
        $issues = $result['issues'];
        $isolatedIssue = array_filter($issues, fn($issue) => $issue['type'] === 'isolated_table');
        $this->assertNotEmpty($isolatedIssue);
    }

    public function testPerformanceScoreCalculation(): void
    {
        $schema = $this->createSchemaWithMultipleIssues();
        
        $result = $this->analyzer->analyze($schema);
        
        // Should have multiple issues affecting the score
        $this->assertLessThan(100.0, $result['score']);
        $this->assertGreaterThan(0.0, $result['score']);
        $this->assertNotEmpty($result['issues']);
        $this->assertNotEmpty($result['recommendations']);
    }

    public function testGetters(): void
    {
        $schema = $this->createSchemaWithMultipleIssues();
        
        $this->analyzer->analyze($schema);
        
        $this->assertIsArray($this->analyzer->getIssues());
        $this->assertIsArray($this->analyzer->getRecommendations());
        $this->assertIsFloat($this->analyzer->getPerformanceScore());
    }

    protected function createOptimalSchema(): Schema
    {
        $schema = new Schema();
        
        // Users table with proper structure
        $usersTable = new Table('users');
        
        $idField = new Field((object)[
            'name' => 'id',
            'type' => 'INT',
            'primary_key' => true
        ]);
        $usersTable->fields->id = $idField;
        
        $nameField = new Field((object)[
            'name' => 'name',
            'type' => 'VARCHAR',
            'max_length' => 100
        ]);
        $usersTable->fields->name = $nameField;
        
        $schema->tables->users = $usersTable;
        
        // Posts table with proper FK and index
        $postsTable = new Table('posts');
        
        $postIdField = new Field((object)[
            'name' => 'id',
            'type' => 'INT',
            'primary_key' => true
        ]);
        $postsTable->fields->id = $postIdField;
        
        $userIdField = new Field((object)[
            'name' => 'user_id',
            'type' => 'INT'
        ]);
        $postsTable->fields->user_id = $userIdField;
        
        // Add FK
        $fk = new ForeignKey((object)[
            'constraint_name' => 'fk_posts_user',
            'column_name' => 'user_id',
            'foreign_table_name' => 'users',
            'foreign_column_name' => 'id'
        ]);
        $postsTable->foreignKeys->fk_posts_user = $fk;
        
        // Add index for FK
        $index = new Index((object)[
            'name' => 'idx_posts_user_id',
            'fields' => ['user_id']
        ]);
        $postsTable->indexes->idx_posts_user_id = $index;
        
        $schema->tables->posts = $postsTable;
        
        return $schema;
    }

    protected function createSchemaWithUnindexedForeignKey(): Schema
    {
        $schema = new Schema();
        
        // Users table
        $usersTable = new Table('users');
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $usersTable->fields->id = $idField;
        $schema->tables->users = $usersTable;
        
        // Posts table with FK but no index
        $postsTable = new Table('posts');
        
        $postIdField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $postsTable->fields->id = $postIdField;
        
        $userIdField = new Field((object)['name' => 'user_id', 'type' => 'INT']);
        $postsTable->fields->user_id = $userIdField;
        
        $fk = new ForeignKey((object)[
            'constraint_name' => 'fk_posts_user',
            'column_name' => 'user_id',
            'foreign_table_name' => 'users',
            'foreign_column_name' => 'id'
        ]);
        $postsTable->foreignKeys->fk_posts_user = $fk;
        
        $schema->tables->posts = $postsTable;
        
        return $schema;
    }

    protected function createSchemaWithDuplicateIndexes(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $table->fields->id = $idField;
        
        $nameField = new Field((object)['name' => 'name', 'type' => 'VARCHAR']);
        $table->fields->name = $nameField;
        
        // Duplicate indexes
        $index1 = new Index((object)['name' => 'idx_name_1', 'fields' => ['name']]);
        $index2 = new Index((object)['name' => 'idx_name_2', 'fields' => ['name']]);
        
        $table->indexes->idx_name_1 = $index1;
        $table->indexes->idx_name_2 = $index2;
        
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithWideIndex(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $table->fields->id = $idField;
        
        // Wide index with 6 columns
        $wideIndex = new Index((object)[
            'name' => 'idx_wide',
            'fields' => ['col1', 'col2', 'col3', 'col4', 'col5', 'col6']
        ]);
        
        $table->indexes->idx_wide = $wideIndex;
        
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithInoefficientDataTypes(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $table->fields->id = $idField;
        
        // TEXT field that should be VARCHAR
        $shortTextField = new Field((object)[
            'name' => 'short_text',
            'type' => 'TEXT',
            'max_length' => 50
        ]);
        $table->fields->short_text = $shortTextField;
        
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithoutPrimaryKey(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $nameField = new Field((object)[
            'name' => 'name',
            'type' => 'VARCHAR',
            'primary_key' => false
        ]);
        $table->fields->name = $nameField;
        
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithTooManyColumns(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('test_table');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $table->fields->id = $idField;
        
        // Add 59 more fields for a total of 60
        for ($i = 1; $i <= 59; $i++) {
            $field = new Field((object)['name' => "field_{$i}", 'type' => 'VARCHAR']);
            $table->fields->{"field_{$i}"} = $field;
        }
        
        $schema->tables->test_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithIsolatedTable(): Schema
    {
        $schema = new Schema();
        
        $table = new Table('isolated_table');
        
        $idField = new Field((object)['name' => 'id', 'type' => 'INT', 'primary_key' => true]);
        $table->fields->id = $idField;
        
        $nameField = new Field((object)['name' => 'name', 'type' => 'VARCHAR']);
        $table->fields->name = $nameField;
        
        // No relations or foreign keys
        
        $schema->tables->isolated_table = $table;
        
        return $schema;
    }

    protected function createSchemaWithMultipleIssues(): Schema
    {
        $schema = new Schema();
        
        // Table without primary key, with unindexed FK, and duplicate indexes
        $table = new Table('problematic_table');
        
        // No primary key
        $nameField = new Field((object)['name' => 'name', 'type' => 'VARCHAR']);
        $table->fields->name = $nameField;
        
        $userIdField = new Field((object)['name' => 'user_id', 'type' => 'INT']);
        $table->fields->user_id = $userIdField;
        
        // Unindexed foreign key
        $fk = new ForeignKey((object)[
            'constraint_name' => 'fk_user',
            'column_name' => 'user_id',
            'foreign_table_name' => 'users',
            'foreign_column_name' => 'id'
        ]);
        $table->foreignKeys->fk_user = $fk;
        
        // Duplicate indexes
        $index1 = new Index((object)['name' => 'idx_name_1', 'fields' => ['name']]);
        $index2 = new Index((object)['name' => 'idx_name_2', 'fields' => ['name']]);
        
        $table->indexes->idx_name_1 = $index1;
        $table->indexes->idx_name_2 = $index2;
        
        $schema->tables->problematic_table = $table;
        
        return $schema;
    }
}
