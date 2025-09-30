<?php

declare(strict_types=1);

namespace Tests\Reader;

use Daycry\Schemas\Config\Schemas;
use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\View;
use Daycry\Schemas\Structures\Procedure;
use Daycry\Schemas\Structures\Trigger;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DatabaseObjectHandlerExtendedTest extends TestCase
{
    /**
     * Provide a fake connection by leveraging driver name heuristics.
     * We simulate three sequential query() calls for views, procedures, triggers.
     */
    public function testFetchAllPopulatesContainers(): void
    {
        // Subclass handler to override fetch* methods to populate objects deterministically
        $handler = new class(new Schemas()) extends DatabaseObjectHandler {
            protected function fetchViews(): void
            {
                if (! $this->objects->views) {
                    $this->objects->views = new Mergeable();
                }
                $view = new View('users_view');
                $view->definition = 'SELECT * FROM users';
                $this->objects->views->users_view = $view;
            }
            protected function fetchProcedures(): void
            {
                if (! $this->objects->procedures) {
                    $this->objects->procedures = new Mergeable();
                }
                $proc = new Procedure('do_thing');
                $proc->type = 'PROCEDURE';
                $this->objects->procedures->do_thing = $proc;
            }
            protected function fetchTriggers(): void
            {
                if (! $this->objects->triggers) {
                    $this->objects->triggers = new Mergeable();
                }
                $tr = new Trigger('users_trigger');
                $tr->table = 'users';
                $this->objects->triggers->users_trigger = $tr;
            }
        };

        $handler->fetchAll();

        $objects = $handler->getObjects();
        $this->assertInstanceOf(Mergeable::class, $objects);
        $this->assertTrue(isset($objects->views));
        $this->assertTrue(isset($objects->procedures));
        $this->assertTrue(isset($objects->triggers));
        $this->assertInstanceOf(View::class, $objects->views->users_view ?? null);
        $this->assertInstanceOf(Procedure::class, $objects->procedures->do_thing ?? null);
        $this->assertInstanceOf(Trigger::class, $objects->triggers->users_trigger ?? null);
        $this->assertGreaterThanOrEqual(3, $handler->count());

        // Magic getter & isset
        $this->assertNotNull($handler->views);
        $this->assertTrue(isset($handler->procedures));
    }
}
