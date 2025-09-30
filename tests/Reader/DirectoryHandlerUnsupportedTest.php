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
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DirectoryHandlerUnsupportedTest extends TestCase
{
    public function testUnsupportedExtensionIgnored(): void
    {
        $dir = sys_get_temp_dir() . '/schemas_dir_' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/dummy.txt', 'not php');

        $handler = new DirectoryHandler(new Schemas());
        $schema  = $handler->read($dir);
        $this->assertNotNull($schema->tables);
        $this->assertCount(0, (array) $schema->tables);

        unlink($dir . '/dummy.txt');
        rmdir($dir);
    }
}
