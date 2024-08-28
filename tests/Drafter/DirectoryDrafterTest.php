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

use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DirectoryDrafterTest extends TestCase
{
    public function testEmptyDirectoryReturnsNull()
    {
        $config                   = $this->config;
        $config->schemasDirectory = SUPPORTPATH . 'Schemas/NoFiles';
        $handler                  = new DirectoryHandler($config);

        $this->assertNull($handler->draft());
    }

    public function testNoHandlersReturnsNull()
    {
        $config                   = $this->config;
        $config->schemasDirectory = SUPPORTPATH . 'Schemas/NoHandler';
        $handler                  = new DirectoryHandler($config);

        $this->assertNull($handler->draft());
    }

    public function testSuccessReturnsSchemaNoErrors()
    {
        $config                   = $this->config;
        $config->schemasDirectory = SUPPORTPATH . 'Schemas/Good';
        $handler                  = new DirectoryHandler($config);

        $schema = $handler->draft();

        $this->assertSame('hasMany', $schema->tables->workers->relations->products->type);
        $this->assertCount(0, $handler->getErrors());
    }
}
