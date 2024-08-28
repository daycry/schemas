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

use Daycry\Schemas\Drafter\Handlers\DirectoryHandlers\PhpHandler;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PhpDrafterTest extends TestCase
{
    public function testSuccessReturnsSchemaFromFile()
    {
        $path    = SUPPORTPATH . 'Schemas/Good/Products.php';
        $handler = new PhpHandler($this->config, $path);
        $schema  = $handler->draft();

        $this->assertSame('hasMany', $schema->tables->workers->relations->products->type);
        $this->assertCount(0, $handler->getErrors());
    }

    public function testEmptyFileReturnsNull()
    {
        $path    = SUPPORTPATH . 'Schemas/Empty/NothingToSee.php';
        $handler = new PhpHandler($this->config, $path);

        $this->assertNull($handler->draft());
    }

    public function testMissingVariableReturnsNull()
    {
        $path    = SUPPORTPATH . 'Schemas/Invalid/NoSchemaVariable.php';
        $handler = new PhpHandler($this->config, $path);

        $this->assertNull($handler->draft());
    }
}
