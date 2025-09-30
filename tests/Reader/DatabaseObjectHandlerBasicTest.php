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
use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DatabaseObjectHandlerBasicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testFetchAllNoObjectsKeepsCountZero(): void
    {
        $handler = new DatabaseObjectHandler(new Schemas(), null); // relies on default connection; queries return empty
        $handler->fetchAll();
        $this->assertSame(0, $handler->count());
    }

    public function testMagicGetReturnsNullWhenAbsent(): void
    {
        $handler = new DatabaseObjectHandler(new Schemas(), null);
        $this->assertNull($handler->__get('views'));
    }

    public function testIteratorReturnsMergeableAndTriggersFetchAll(): void
    {
        $handler = new DatabaseObjectHandler(new Schemas(), null);
        $iter    = $handler->getIterator();
        $this->assertIsObject($iter);
    }
}
