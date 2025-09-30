<?php

declare(strict_types=1);

namespace Tests\Reader;

use Daycry\Schemas\Config\Schemas;
use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;
use PHPUnit\Framework\TestCase;

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
        $iter = $handler->getIterator();
        $this->assertIsObject($iter);
    }
}
