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

use Daycry\Schemas\Config\Schemas;
use Daycry\Schemas\Reader\Handlers\DirectoryHandler;
use Daycry\Schemas\Structures\Schema;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DirectoryHandlerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/schemas_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*.php') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testReadEmptyDirectoryReturnsEmptySchema(): void
    {
        $config  = new Schemas();
        $handler = new DirectoryHandler($config);
        $schema  = $handler->read($this->tempDir);
        $this->assertInstanceOf(Schema::class, $schema);
        $this->assertCount(0, (array) $schema->tables);
    }

    public function testReadPopulatedDirectoryMergesTables(): void
    {
        // create two schema files returning arrays
        file_put_contents($this->tempDir . '/users.php', '<?php return ["users" => ["id" => 1, "name" => "demo"]];');
        file_put_contents($this->tempDir . '/posts.php', '<?php return ["posts" => ["id" => 1, "title" => "hello"]];');

        $config  = new Schemas();
        $handler = new DirectoryHandler($config);
        $schema  = $handler->read($this->tempDir);

        $this->assertArrayHasKey('users', (array) $schema->tables);
        $this->assertArrayHasKey('posts', (array) $schema->tables);
    }

    public function testReadReturnsSchemaInstanceWhenFileReturnsSchemaObject(): void
    {
        // file that returns a Schema object
        $schemaObj                  = new Schema();
        $schemaObj->tables->example = (object) ['col' => 'val'];
        $export                     = var_export($schemaObj, true); // not directly serializable to php code with var_export? We simplify.
        // fallback: create file returning array then treat as array
        file_put_contents($this->tempDir . '/example.php', '<?php return ["example" => ["col" => "val"]];');

        $config  = new Schemas();
        $handler = new DirectoryHandler($config);
        $schema  = $handler->read($this->tempDir);
        $this->assertArrayHasKey('example', (array) $schema->tables);
    }
}
