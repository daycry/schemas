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

namespace Tests\Structures;

use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\ForeignKey;
use Daycry\Schemas\Structures\Index;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class StructuresInstantiationTest extends TestCase
{
    public function testFieldFromArray(): void
    {
        $field = new Field([
            'name'           => 'id',
            'primary_key'    => '1', // string to test cast
            'type'           => 'INT',
            'max_length'     => 11,
            'nullable'       => 0,
            'default'        => null,
            'auto_increment' => 1,
            'comment'        => 'Primary key',
        ]);
        $this->assertSame('id', $field->name);
        $this->assertTrue($field->primary_key);
        $this->assertSame('INT', $field->type);
        $this->assertSame(11, $field->max_length);
        $this->assertFalse($field->nullable);
        $this->assertTrue($field->auto_increment);
    }

    public function testForeignKeyFromArrayWithArrays(): void
    {
        $fk = new ForeignKey([
            'constraint_name'     => 'fk_user_role',
            'column_name'         => ['role_id'],
            'foreign_table_name'  => 'roles',
            'foreign_column_name' => ['id'],
            'on_delete'           => 'CASCADE',
            'on_update'           => 'RESTRICT',
        ]);
        $this->assertSame('fk_user_role', $fk->constraint_name);
        $this->assertSame('role_id', $fk->column_name);
        $this->assertSame('id', $fk->foreign_column_name);
    }

    public function testIndexFromArray(): void
    {
        $index = new Index([
            'name'   => 'idx_users_email',
            'fields' => ['email'],
            'type'   => 'BTREE',
            'unique' => true,
        ]);
        $this->assertSame('idx_users_email', $index->name);
        $this->assertSame(['email'], $index->fields);
        $this->assertSame('BTREE', $index->type);
        $this->assertTrue($index->unique);
    }
}
