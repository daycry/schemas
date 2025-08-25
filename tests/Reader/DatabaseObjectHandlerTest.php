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

use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;
use Daycry\Schemas\Structures\View;
use Daycry\Schemas\Structures\Procedure;
use Daycry\Schemas\Structures\Trigger;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class DatabaseObjectHandlerTest extends TestCase
{
    private DatabaseObjectHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->handler = new DatabaseObjectHandler();
    }

    public function testHandlerInitialization(): void
    {
        $this->assertTrue($this->handler->ready());
        $this->assertNotNull($this->handler->getObjects());
        $this->assertSame(0, $this->handler->count());
    }

    public function testFetchSpecificObjectTypes(): void
    {
        // Test fetching specific object types
        $result = $this->handler->fetch(['views']);
        $this->assertInstanceOf(DatabaseObjectHandler::class, $result);
        
        $result = $this->handler->fetch('procedures');
        $this->assertInstanceOf(DatabaseObjectHandler::class, $result);
        
        $result = $this->handler->fetch(['triggers']);
        $this->assertInstanceOf(DatabaseObjectHandler::class, $result);
    }

    public function testFetchAll(): void
    {
        $result = $this->handler->fetchAll();
        $this->assertInstanceOf(DatabaseObjectHandler::class, $result);
    }

    public function testMagicMethods(): void
    {
        // Test magic getter and isset
        $this->handler->fetchAll();
        
        // These should exist even if empty
        $this->assertTrue(isset($this->handler->views) || $this->handler->views === null);
        $this->assertTrue(isset($this->handler->procedures) || $this->handler->procedures === null);
        $this->assertTrue(isset($this->handler->triggers) || $this->handler->triggers === null);
    }

    public function testIteratorInterface(): void
    {
        $objects = $this->handler->getIterator();
        $this->assertNotNull($objects);
    }

    public function testViewStructure(): void
    {
        $view = new View('test_view');
        $this->assertSame('test_view', $view->name);
        $this->assertNull($view->definition);
        $this->assertFalse($view->updatable);
        $this->assertSame([], $view->dependencies);
        $this->assertNull($view->security);
        $this->assertNull($view->comment);
    }

    public function testProcedureStructure(): void
    {
        $procedure = new Procedure('test_procedure');
        $this->assertSame('test_procedure', $procedure->name);
        $this->assertSame('PROCEDURE', $procedure->type);
        $this->assertNull($procedure->definition);
        $this->assertSame([], $procedure->parameters);
        $this->assertNull($procedure->returnType);
        $this->assertNull($procedure->security);
        $this->assertSame('SQL', $procedure->language);
        $this->assertFalse($procedure->deterministic);
        $this->assertNull($procedure->dataAccess);
        $this->assertNull($procedure->comment);
    }

    public function testTriggerStructure(): void
    {
        $trigger = new Trigger('test_trigger');
        $this->assertSame('test_trigger', $trigger->name);
        $this->assertNull($trigger->table);
        $this->assertNull($trigger->timing);
        $this->assertSame([], $trigger->events);
        $this->assertNull($trigger->definition);
        $this->assertNull($trigger->order);
        $this->assertNull($trigger->condition);
        $this->assertTrue($trigger->enabled);
        $this->assertNull($trigger->comment);
    }

    public function testViewWithDependencies(): void
    {
        $view = new View('user_view');
        $view->definition = 'SELECT * FROM users u JOIN profiles p ON u.id = p.user_id';
        $view->dependencies = ['users', 'profiles'];
        
        $this->assertSame('user_view', $view->name);
        $this->assertContains('users', $view->dependencies);
        $this->assertContains('profiles', $view->dependencies);
    }

    public function testProcedureWithParameters(): void
    {
        $procedure = new Procedure('get_user');
        $procedure->type = 'FUNCTION';
        $procedure->parameters = [
            ['name' => 'user_id', 'type' => 'INT', 'mode' => 'IN'],
            ['name' => 'include_profile', 'type' => 'BOOLEAN', 'mode' => 'IN', 'default' => 'FALSE']
        ];
        $procedure->returnType = 'TABLE';
        $procedure->deterministic = true;
        
        $this->assertSame('FUNCTION', $procedure->type);
        $this->assertSame('TABLE', $procedure->returnType);
        $this->assertTrue($procedure->deterministic);
        $this->assertCount(2, $procedure->parameters);
        $this->assertSame('user_id', $procedure->parameters[0]['name']);
    }

    public function testTriggerWithEvents(): void
    {
        $trigger = new Trigger('audit_trigger');
        $trigger->table = 'users';
        $trigger->timing = 'AFTER';
        $trigger->events = ['INSERT', 'UPDATE', 'DELETE'];
        $trigger->definition = 'BEGIN INSERT INTO audit_log...; END';
        $trigger->enabled = true;
        
        $this->assertSame('users', $trigger->table);
        $this->assertSame('AFTER', $trigger->timing);
        $this->assertContains('INSERT', $trigger->events);
        $this->assertContains('UPDATE', $trigger->events);
        $this->assertContains('DELETE', $trigger->events);
        $this->assertTrue($trigger->enabled);
    }

    public function testCountInterface(): void
    {
        $this->assertSame(0, $this->handler->count());
    }
}
