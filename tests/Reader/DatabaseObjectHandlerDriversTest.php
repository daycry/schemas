<?php

declare(strict_types=1);

namespace Tests\Reader;

use Daycry\Schemas\Config\Schemas;
use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;
use Daycry\Schemas\Structures\Mergeable;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DatabaseObjectHandlerDriversTest extends TestCase
{
    private function makeHandler(): DatabaseObjectHandler
    {
        return new class(new Schemas()) extends DatabaseObjectHandler {
            protected function fetchViews(): void
            {
                if (! $this->objects->views) { $this->objects->views = new Mergeable(); }
                $this->objects->views->v_demo = new \Daycry\Schemas\Structures\View('v_demo');
            }
            protected function fetchProcedures(): void
            {
                if (! $this->objects->procedures) { $this->objects->procedures = new Mergeable(); }
                $this->objects->procedures->p_demo = new \Daycry\Schemas\Structures\Procedure('p_demo');
            }
            protected function fetchTriggers(): void
            {
                if (! $this->objects->triggers) { $this->objects->triggers = new Mergeable(); }
                $this->objects->triggers->t_demo = new \Daycry\Schemas\Structures\Trigger('t_demo');
            }
        };
    }

    public function testFetchSpecificTypes(): void
    {
        $handler = $this->makeHandler();
        $handler->fetch(['views','procedures']);
        $this->assertSame(2, $handler->count());
        $handler->fetch('triggers');
        $this->assertSame(3, $handler->count());
    }
}
