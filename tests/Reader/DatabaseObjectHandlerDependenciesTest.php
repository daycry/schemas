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
use Daycry\Schemas\Structures\View;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DatabaseObjectHandlerDependenciesTest extends TestCase
{
    public function testParseViewDependenciesAndIsset(): void
    {
        $handler = new class (new Schemas()) extends DatabaseObjectHandler {
            protected function fetchViews(): void
            {
                if (! $this->objects->views) {
                    $this->objects->views = new Mergeable();
                }
                $v                              = new View('v_report');
                $v->definition                  = 'SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id';
                $this->objects->views->v_report = $v;
                // Force parsing call
                $this->parseViewDependencies($v);
            }
        };

        $handler->fetch('views');
        $this->assertTrue(isset($handler->views));
        $objects = $handler->getObjects();
        $this->assertTrue(property_exists($objects->views, 'v_report'));
        $view = $objects->views->v_report;
        $this->assertContains('users', $view->dependencies);
        $this->assertContains('roles', $view->dependencies);

        // Idempotent second fetch
        $handler->fetch('views');
        $this->assertSame(1, $handler->count());
    }
}
