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

use CodeIgniter\Test\CIUnitTestCase;
use Daycry\Schemas\Structures\Mergeable;

/**
 * @internal
 */
final class MergeableTest extends CIUnitTestCase
{
    public function testMergeHasItem()
    {
        $object1      = new Mergeable();
        $object1->foo = 'yes';

        $object2      = new Mergeable();
        $object2->bar = 'no thanks';

        $object1->merge($object2);

        $this->assertSame('no thanks', $object1->bar);
    }

    public function testMergeOverwrites()
    {
        $object1      = new Mergeable();
        $object1->foo = 'yes';
        $object1->ra  = [
            'hot',
            'diggity',
        ];

        $object2      = new Mergeable();
        $object2->foo = 'no thanks';
        $object2->ra  = [1 => 'dog'];

        $object1->merge($object2);

        $this->assertSame('no thanks', $object1->foo);
        $this->assertContains('dog', $object1->ra);
    }

    public function testMergeNestedMergeables()
    {
        $object1      = new Mergeable();
        $object1->foo = 'yes';

        $object2       = new Mergeable();
        $object2->bar  = 'no thanks';
        $object2->nest = $object1;

        $object3 = new Mergeable();
        $object3->merge($object2);

        $this->assertSame($object3->nest->foo, 'yes');
    }

    public function testMergeableIsCountable()
    {
        $object      = new Mergeable();
        $object->foo = 'yes';
        $object->bar = 'no thanks';

        $this->assertCount(2, $object);
    }

    public function testMergeableIsIterable()
    {
        $object      = new Mergeable();
        $object->foo = 'yes';
        $object->bar = 'no thanks';

        $counted = 0;

        foreach ($object as $key => $value) {
            $this->assertSame($value, $object->{$key});
            $counted++;
        }

        $this->assertSame(2, $counted);
    }
}
