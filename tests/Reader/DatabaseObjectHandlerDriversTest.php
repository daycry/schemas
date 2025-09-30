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

/**
 * @internal
 */
final class DatabaseObjectHandlerDriversTest extends TestCase
{
    private function makeHandler(): DatabaseObjectHandler
    {
        return new class (new Schemas()) extends DatabaseObjectHandler {
            protected function fetchViews(): void
            {
                if (! $this->objects->views) {
                    $this->objects->views = new Mergeable();
                }
                $this->objects->views->v_demo = new View('v_demo');
            }

            protected function fetchProcedures(): void
            {
                if (! $this->objects->procedures) {
                    $this->objects->procedures = new Mergeable();
                }
                $this->objects->procedures->p_demo = new Procedure('p_demo');
            }

            protected function fetchTriggers(): void
            {
                if (! $this->objects->triggers) {
                    $this->objects->triggers = new Mergeable();
                }
                $this->objects->triggers->t_demo = new Trigger('t_demo');
            }
        };
    }

    public function testFetchSpecificTypes(): void
    {
        $handler = $this->makeHandler();
        $handler->fetch(['views', 'procedures']);
        $this->assertSame(2, $handler->count());
        $handler->fetch('triggers');
        $this->assertSame(3, $handler->count());
    }
}
