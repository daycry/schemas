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

use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Schema;
use Tests\Support\TestCase;
use Tests\Support\Traits\CacheTrait;
use Tests\Support\Traits\MockSchemaTrait;

/**
 * @internal
 */
final class CacheArchiverTest extends TestCase
{
    use CacheTrait;
    use MockSchemaTrait;

    public function testGetKeyUsesEnvironment()
    {
        $this->assertSame('schema-testing', $this->archiver->getKey());
    }

    public function testSetKeyChangesKey()
    {
        $this->archiver->setKey('testKey');

        $this->assertSame('testKey', $this->archiver->getKey());
    }

    public function testArchiveReturnsTrueOnSuccess()
    {
        $this->assertTrue($this->archiver->archive($this->schema));
    }

    public function testArchiveStoresScaffold()
    {
        $key = $this->archiver->getKey();
        $this->archiver->archive($this->schema);

        $expected         = new Schema();
        $expected->tables = new Mergeable();

        foreach ($this->schema->tables as $tableName => $table) {
            $expected->tables->{$tableName} = true;
        }

        $this->assertSame($this->schema, $this->cache->get($key));
    }

    public function testArchiveStoresEachTable()
    {
        $key    = $this->archiver->getKey();
        $tables = $this->schema->tables;

        $this->archiver->archive($this->schema);

        foreach ($tables as $tableName => $table) {
            $this->assertSame($table, $this->cache->get($key . '-' . $tableName));
        }
    }
}
