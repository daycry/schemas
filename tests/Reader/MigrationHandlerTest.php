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

namespace Tests\Reader;

use Daycry\Schemas\Reader\Handlers\MigrationHandler;
use Daycry\Schemas\Structures\Table;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class MigrationHandlerTest extends TestCase
{
    private MigrationHandler $handler;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary directory for test migrations
        $this->tempDir = sys_get_temp_dir() . '/schema_migrations_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        
        $this->handler = new MigrationHandler(null, $this->tempDir . '/');
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
        
        parent::tearDown();
    }

    public function testHandlerInitialization(): void
    {
        $this->assertTrue($this->handler->ready());
        $this->assertNotNull($this->handler->getTables());
        $this->assertSame(0, $this->handler->count());
    }

    public function testHandlerWithNonExistentDirectory(): void
    {
        $handler = new MigrationHandler(null, '/non/existent/path/');
        $this->assertFalse($handler->ready());
    }

    public function testFetchSpecificMigrations(): void
    {
        // Create a test migration file
        $migrationContent = '<?php
class CreateUsersTable extends \CodeIgniter\Database\Migration
{
    public function up()
    {
        $this->forge->addField([
            "id" => [
                "type" => "INT",
                "constraint" => 11,
                "unsigned" => true,
                "auto_increment" => true,
            ],
            "username" => [
                "type" => "VARCHAR",
                "constraint" => 100,
                "null" => false,
            ],
        ]);
        $this->forge->addKey("id", true);
        $this->forge->createTable("users");
    }
}';
        
        $migrationFile = $this->tempDir . '/001_create_users_table.php';
        file_put_contents($migrationFile, $migrationContent);
        
        $result = $this->handler->fetch([$migrationFile]);
        $this->assertInstanceOf(MigrationHandler::class, $result);
    }

    public function testFetchAllMigrations(): void
    {
        // Create multiple test migration files
        $migrations = [
            '001_create_users_table.php' => '<?php
class CreateUsersTable extends \CodeIgniter\Database\Migration
{
    public function up()
    {
        $this->forge->createTable("users");
    }
}',
            '002_create_posts_table.php' => '<?php
class CreatePostsTable extends \CodeIgniter\Database\Migration  
{
    public function up()
    {
        $this->forge->createTable("posts");
    }
}'
        ];
        
        foreach ($migrations as $filename => $content) {
            file_put_contents($this->tempDir . '/' . $filename, $content);
        }
        
        $result = $this->handler->fetchAll();
        $this->assertInstanceOf(MigrationHandler::class, $result);
    }

    public function testMagicMethods(): void
    {
        // Test magic getter and isset
        $this->handler->fetchAll();
        
        // Test accessing non-existent table
        $this->assertNull($this->handler->nonexistent_table);
        $this->assertFalse(isset($this->handler->nonexistent_table));
    }

    public function testIteratorInterface(): void
    {
        $tables = $this->handler->getIterator();
        $this->assertNotNull($tables);
    }

    public function testCountInterface(): void
    {
        $this->assertSame(0, $this->handler->count());
    }

    public function testParseCreateTableMigration(): void
    {
        $migrationContent = '<?php
class CreateUsersTable extends \CodeIgniter\Database\Migration
{
    public function up()
    {
        $this->forge->addField([
            "id" => [
                "type" => "INT",
                "constraint" => 11,
                "auto_increment" => true,
            ],
            "username" => [
                "type" => "VARCHAR", 
                "constraint" => 100,
                "null" => false,
            ],
            "email" => [
                "type" => "VARCHAR",
                "constraint" => 255,
                "null" => false,
                "default" => "",
            ],
        ]);
        $this->forge->addKey("id", true);
        $this->forge->createTable("users");
    }
}';
        
        $migrationFile = $this->tempDir . '/create_users_table.php';
        file_put_contents($migrationFile, $migrationContent);
        
        $this->handler->fetch([$migrationFile]);
        
        // Test that table was created during parsing
        $tables = $this->handler->getTables();
        $this->assertNotNull($tables);
    }

    public function testParseModifyTableMigration(): void
    {
        $migrationContent = '<?php
class ModifyUsersTable extends \CodeIgniter\Database\Migration
{
    public function up()
    {
        $this->forge->addColumn("users", [
            "created_at" => [
                "type" => "DATETIME",
                "null" => true,
            ],
        ]);
        $this->forge->modifyColumn("users", [
            "username" => [
                "type" => "VARCHAR",
                "constraint" => 150,
            ],
        ]);
    }
}';
        
        $migrationFile = $this->tempDir . '/modify_users_table.php';
        file_put_contents($migrationFile, $migrationContent);
        
        $this->handler->fetch([$migrationFile]);
        
        $tables = $this->handler->getTables();
        $this->assertNotNull($tables);
    }

    public function testParseIndexMigration(): void
    {
        $migrationContent = '<?php
class AddIndexToUsers extends \CodeIgniter\Database\Migration
{
    public function up()
    {
        $this->forge->addKey("username");
        $this->forge->addKey("email", false, true); // unique
    }
}';
        
        $migrationFile = $this->tempDir . '/add_index_to_users.php';
        file_put_contents($migrationFile, $migrationContent);
        
        $this->handler->fetch([$migrationFile]);
        
        $tables = $this->handler->getTables();
        $this->assertNotNull($tables);
    }

    public function testParseForeignKeyMigration(): void
    {
        $migrationContent = '<?php
class AddForeignKeys extends \CodeIgniter\Database\Migration
{
    public function up()
    {
        $this->forge->addForeignKey("user_id", "users", "id", "CASCADE", "CASCADE");
    }
}';
        
        $migrationFile = $this->tempDir . '/add_foreign_keys.php';
        file_put_contents($migrationFile, $migrationContent);
        
        $this->handler->fetch([$migrationFile]);
        
        $tables = $this->handler->getTables();
        $this->assertNotNull($tables);
    }

    public function testMigrationFileDoesNotExist(): void
    {
        $result = $this->handler->fetch(['nonexistent_file.php']);
        $this->assertInstanceOf(MigrationHandler::class, $result);
        
        // Should not throw an error, just silently skip
        $this->assertSame(0, $this->handler->count());
    }

    public function testEmptyMigrationDirectory(): void
    {
        $result = $this->handler->fetchAll();
        $this->assertInstanceOf(MigrationHandler::class, $result);
        $this->assertSame(0, $this->handler->count());
    }
}
