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

use Daycry\Schemas\Drafter\BaseDrafter;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Table;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class BaseDrafterTest extends TestCase
{
    protected BaseDrafter $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new class ($this->config) extends BaseDrafter {};
    }

    public function testFindKeyToForeignTable()
    {
        $table  = new Table('machines');
        $method = $this->getPrivateMethodInvoker($this->handler, 'findKeyToForeignTable');

        $fields          = new Mergeable();
        $fields->factory = new Field();
        $fields->serial  = new Field();
        $table->fields   = $fields;

        $this->assertSame('factory', $method($table, 'factories'));

        $fields             = new Mergeable();
        $fields->factory_id = new Field();
        $fields->serial     = new Field();
        $table->fields      = $fields;

        $this->assertSame('factory_id', $method($table, 'factories'));

        $fields            = new Mergeable();
        $fields->factories = new Field();
        $fields->serial    = new Field();
        $table->fields     = $fields;

        $this->assertSame('factories', $method($table, 'factories'));

        $fields               = new Mergeable();
        $fields->factories_id = new Field();
        $fields->serial       = new Field();
        $table->fields        = $fields;

        $this->assertSame('factories_id', $method($table, 'factories'));
    }

    public function testNotFindKeyToForeignTable()
    {
        $table  = new Table('machines');
        $method = $this->getPrivateMethodInvoker($this->handler, 'findKeyToForeignTable');

        $fields            = new Mergeable();
        $fields->factories = new Field();
        $fields->serial    = new Field();
        $table->fields     = $fields;

        $this->assertNull($method($table, 'lawyers'));
    }

    public function testFindPrimaryKeyActual()
    {
        $table  = new Table('machines');
        $method = $this->getPrivateMethodInvoker($this->handler, 'findPrimaryKey');

        $field              = new Field('machine_id');
        $field->primary_key = true;

        $fields             = new Mergeable();
        $fields->machine_id = $field;
        $fields->serial     = new Field();
        $table->fields      = $fields;

        $this->assertSame('machine_id', $method($table));
    }

    public function testFindPrimaryKeyImplied()
    {
        $table  = new Table('machines');
        $method = $this->getPrivateMethodInvoker($this->handler, 'findPrimaryKey');

        $fields         = new Mergeable();
        $fields->id     = new Field('id');
        $fields->serial = new Field();
        $table->fields  = $fields;

        $this->assertSame('id', $method($table));
    }

    public function testNotFindPrimaryKey()
    {
        $table  = new Table('machines');
        $method = $this->getPrivateMethodInvoker($this->handler, 'findPrimaryKey');

        $fields          = new Mergeable();
        $fields->primary = new Field('primary');
        $fields->serial  = new Field();
        $table->fields   = $fields;

        $this->assertNull($method($table));
    }
}
