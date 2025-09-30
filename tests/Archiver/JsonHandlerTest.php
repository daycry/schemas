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

namespace Tests\Archiver;

use Daycry\Schemas\Archiver\Handlers\JsonHandler;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\ForeignKey;
use Daycry\Schemas\Structures\Index;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class JsonHandlerTest extends TestCase
{
    private JsonHandler $handler;
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempFile = sys_get_temp_dir() . '/schema_test_' . uniqid() . '.json';
        $this->handler  = new JsonHandler(null, $this->tempFile, true);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        parent::tearDown();
    }

    public function testHandlerInitialization(): void
    {
        $this->assertInstanceOf(JsonHandler::class, $this->handler);
    }

    public function testArchiveEmptySchema(): void
    {
        $schema = new Schema();
        $result = $this->handler->archive($schema);

        $this->assertTrue($result);
        $this->assertFileExists($this->tempFile);

        $content = file_get_contents($this->tempFile);
        $this->assertJson($content);

        $data = json_decode($content, true);
        $this->assertArrayHasKey('schema', $data);
        $this->assertArrayHasKey('version', $data['schema']);
        $this->assertArrayHasKey('exported_at', $data['schema']);
        $this->assertArrayHasKey('tables', $data['schema']);
        $this->assertEmpty($data['schema']['tables']);
    }

    public function testArchiveSchemaWithTable(): void
    {
        $schema         = new Schema();
        $schema->tables = new Mergeable();

        // Create a test table
        $table            = new Table('users');
        $table->comment   = 'Users table';
        $table->engine    = 'InnoDB';
        $table->collation = 'utf8mb4_unicode_ci';

        // Add fields
        $table->fields         = new Mergeable();
        $field                 = new Field('id');
        $field->type           = 'INT';
        $field->max_length     = 11;
        $field->auto_increment = true;
        $field->primary_key    = true;
        $table->fields->id     = $field;

        $field               = new Field('name');
        $field->type         = 'VARCHAR';
        $field->max_length   = 255;
        $field->nullable     = false;
        $table->fields->name = $field;

        // Add index
        $table->indexes           = new Mergeable();
        $index                    = new Index('idx_name');
        $index->fields            = ['name'];
        $index->unique            = false;
        $table->indexes->idx_name = $index;

        $schema->tables->users = $table;

        $result = $this->handler->archive($schema);
        $this->assertTrue($result);

        $content = file_get_contents($this->tempFile);
        $data    = json_decode($content, true);

        $this->assertArrayHasKey('users', $data['schema']['tables']);
        $this->assertSame('users', $data['schema']['tables']['users']['name']);
        $this->assertSame('Users table', $data['schema']['tables']['users']['comment']);
        $this->assertSame('InnoDB', $data['schema']['tables']['users']['engine']);

        // Check fields
        $this->assertArrayHasKey('id', $data['schema']['tables']['users']['fields']);
        $this->assertArrayHasKey('name', $data['schema']['tables']['users']['fields']);
        $this->assertSame('INT', $data['schema']['tables']['users']['fields']['id']['type']);
        $this->assertTrue($data['schema']['tables']['users']['fields']['id']['auto_increment']);

        // Check indexes
        $this->assertArrayHasKey('idx_name', $data['schema']['tables']['users']['indexes']);
        $this->assertSame(['name'], $data['schema']['tables']['users']['indexes']['idx_name']['fields']);
    }

    public function testLoadSchema(): void
    {
        // Create a JSON file first
        $jsonData = [
            'schema' => [
                'version'     => '1.0',
                'exported_at' => '2023-01-01T00:00:00+00:00',
                'tables'      => [
                    'users' => [
                        'name'      => 'users',
                        'comment'   => 'Users table',
                        'engine'    => 'InnoDB',
                        'collation' => 'utf8mb4_unicode_ci',
                        'fields'    => [
                            'id' => [
                                'name'           => 'id',
                                'type'           => 'INT',
                                'max_length'     => 11,
                                'nullable'       => false,
                                'default'        => null,
                                'auto_increment' => true,
                                'primary_key'    => true,
                                'comment'        => null,
                            ],
                            'name' => [
                                'name'           => 'name',
                                'type'           => 'VARCHAR',
                                'max_length'     => 255,
                                'nullable'       => false,
                                'default'        => null,
                                'auto_increment' => false,
                                'primary_key'    => false,
                                'comment'        => null,
                            ],
                        ],
                        'indexes'      => [],
                        'foreign_keys' => [],
                        'relations'    => [],
                    ],
                ],
            ],
        ];

        file_put_contents($this->tempFile, json_encode($jsonData, JSON_PRETTY_PRINT));

        $schema = $this->handler->load();
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertNotNull($schema->tables);
        $this->assertTrue(property_exists($schema->tables, 'users'));

        $table = $schema->tables->users;
        $this->assertInstanceOf(Table::class, $table);
        $this->assertSame('users', $table->name);
        $this->assertSame('Users table', $table->comment);
        $this->assertSame('InnoDB', $table->engine);

        // Check fields
        $this->assertTrue(property_exists($table->fields, 'id'));
        $this->assertTrue(property_exists($table->fields, 'name'));

        $idField = $table->fields->id;
        $this->assertSame('id', $idField->name);
        $this->assertSame('INT', $idField->type);
        $this->assertTrue($idField->auto_increment);
        $this->assertTrue($idField->primary_key);
    }

    public function testLoadNonExistentFile(): void
    {
        $handler = new JsonHandler(null, '/non/existent/file.json');
        $schema  = $handler->load();

        $this->assertNull($schema);
        $this->assertNotEmpty($handler->getErrors());
    }

    public function testLoadInvalidJson(): void
    {
        file_put_contents($this->tempFile, 'invalid json content');

        $schema = $this->handler->load();
        $this->assertNull($schema);
        $this->assertNotEmpty($this->handler->getErrors());
    }

    public function testExportSchema(): void
    {
        $schema         = new Schema();
        $schema->tables = new Mergeable();

        $table                 = new Table('users');
        $schema->tables->users = $table;

        $json = $this->handler->export($schema);
        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertArrayHasKey('schema', $data);
        $this->assertArrayHasKey('users', $data['schema']['tables']);
    }

    public function testImportSchema(): void
    {
        $jsonData = [
            'schema' => [
                'version' => '1.0',
                'tables'  => [
                    'posts' => [
                        'name'      => 'posts',
                        'comment'   => null,
                        'engine'    => null,
                        'collation' => null,
                        'fields'    => [
                            'id' => [
                                'name'           => 'id',
                                'type'           => 'INT',
                                'max_length'     => null,
                                'nullable'       => false,
                                'default'        => null,
                                'auto_increment' => true,
                                'primary_key'    => true,
                                'comment'        => null,
                            ],
                        ],
                        'indexes'      => [],
                        'foreign_keys' => [],
                        'relations'    => [],
                    ],
                ],
            ],
        ];

        $json   = json_encode($jsonData);
        $schema = $this->handler->import($json);

        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertTrue(property_exists($schema->tables, 'posts'));

        $table = $schema->tables->posts;
        $this->assertSame('posts', $table->name);
        $this->assertTrue(property_exists($table->fields, 'id'));
    }

    public function testImportInvalidJson(): void
    {
        $schema = $this->handler->import('invalid json');
        $this->assertNull($schema);
        $this->assertNotEmpty($this->handler->getErrors());
    }

    public function testArchiveWithForeignKeys(): void
    {
        $schema         = new Schema();
        $schema->tables = new Mergeable();

        $table              = new Table('posts');
        $table->foreignKeys = new Mergeable();

        $foreignKey                      = new ForeignKey();
        $foreignKey->constraint_name     = 'fk_posts_user_id';
        $foreignKey->column_name         = 'user_id';
        $foreignKey->foreign_table_name  = 'users';
        $foreignKey->foreign_column_name = 'id';
        $foreignKey->on_delete           = 'CASCADE';
        $foreignKey->on_update           = 'RESTRICT';

        $table->foreignKeys->fk_posts_user_id = $foreignKey;
        $schema->tables->posts                = $table;

        $result = $this->handler->archive($schema);
        $this->assertTrue($result);

        $content = file_get_contents($this->tempFile);
        $data    = json_decode($content, true);

        $foreignKeys = $data['schema']['tables']['posts']['foreign_keys'];
        $this->assertArrayHasKey('fk_posts_user_id', $foreignKeys);
        $this->assertSame('users', $foreignKeys['fk_posts_user_id']['foreign_table_name']);
        $this->assertSame('CASCADE', $foreignKeys['fk_posts_user_id']['on_delete']);
    }

    public function testCreateDirectoryIfNotExists(): void
    {
        $nestedPath = sys_get_temp_dir() . '/nested/directory/schema.json';
        $handler    = new JsonHandler(null, $nestedPath);

        $schema = new Schema();
        $result = $handler->archive($schema);

        $this->assertTrue($result);
        $this->assertFileExists($nestedPath);

        // Cleanup
        unlink($nestedPath);
        rmdir(dirname($nestedPath));
        rmdir(dirname($nestedPath, 2));
    }
}
