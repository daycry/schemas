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
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Procedure;
use Daycry\Schemas\Structures\Trigger;
use Daycry\Schemas\Structures\View;
use PHPUnit\Framework\TestCase;

/** @internal */
final class DatabaseObjectHandlerDriversSeamTest extends TestCase
{
    private function make(string $driverFragment, array $sets): DatabaseObjectHandler
    {
        return new class (new Schemas(), null, $driverFragment, $sets) extends DatabaseObjectHandler {
            private string $fragment;
            private array $sets;

            public function __construct($config, $db, string $fragment, array $sets)
            {
                parent::__construct($config, $db);
                $this->fragment = $fragment;
                $this->sets     = $sets;
            }

            protected function getDriverClass(): string
            {
                return $this->fragment; // override for branch selection
            }

            protected function fetchMySQLViews(): void
            {
                $this->inject('views', $this->sets['views'] ?? []);
            }

            protected function fetchMySQLProcedures(): void
            {
                $this->inject('procedures', $this->sets['procedures'] ?? []);
            }

            protected function fetchMySQLTriggers(): void
            {
                $this->inject('triggers', $this->sets['triggers'] ?? []);
            }

            protected function fetchPostgreViews(): void
            {
                $this->inject('views', $this->sets['views'] ?? []);
            }

            protected function fetchPostgreProcedures(): void
            {
                $this->inject('procedures', $this->sets['procedures'] ?? []);
            }

            protected function fetchPostgreTriggers(): void
            {
                $this->inject('triggers', $this->sets['triggers'] ?? []);
            }

            protected function fetchSQLiteViews(): void
            {
                $this->inject('views', $this->sets['views'] ?? []);
            }

            protected function fetchSQLiteTriggers(): void
            {
                $this->inject('triggers', $this->sets['triggers'] ?? []);
            }

            protected function fetchGenericViews(): void
            {
                $this->inject('views', $this->sets['views'] ?? []);
            }

            protected function fetchGenericProcedures(): void
            {
                $this->inject('procedures', $this->sets['procedures'] ?? []);
            }

            protected function fetchGenericTriggers(): void
            {
                $this->inject('triggers', $this->sets['triggers'] ?? []);
            }

            private function inject(string $type, array $names): void
            {
                if (! $this->objects->{$type}) {
                    $this->objects->{$type} = new Mergeable();
                }

                foreach ($names as $n) {
                    $obj = match ($type) {
                        'views'      => new View($n),
                        'procedures' => new Procedure($n),
                        'triggers'   => new Trigger($n),
                    };
                    $this->objects->{$type}->{$n} = $obj;
                }
            }
        };
    }

    public function testMySQLDriverBranches(): void
    {
        $h = $this->make('MySQLi\Foo', ['views' => ['v1'], 'procedures' => ['p1'], 'triggers' => ['t1']]);
        $h->fetchAll();
        $this->assertSame(3, $h->count());
    }

    public function testPostgreDriverBranches(): void
    {
        $h = $this->make('Postgre\Bar', ['views' => ['v2'], 'procedures' => ['p2'], 'triggers' => ['t2']]);
        $h->fetchAll();
        $this->assertSame(3, $h->count());
    }

    public function testSQLiteDriverBranches(): void
    {
        $h = $this->make('SQLite3\Baz', ['views' => ['v3'], 'triggers' => ['t3']]);
        $h->fetchAll();
        // SQLite path does not fetch procedures; count = 2
        $this->assertSame(2, $h->count());
    }

    public function testGenericDriverBranches(): void
    {
        $h = $this->make('OtherDriver', ['views' => ['v4'], 'procedures' => ['p4'], 'triggers' => ['t4']]);
        $h->fetchAll();
        // Generic fetchAll calls fetchViews/fetchProcedures/fetchTriggers -> count 3
        $this->assertSame(3, $h->count());
    }
}
