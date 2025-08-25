<?php

namespace Tests\Events;

use Daycry\Schemas\Events\BaseEvent;
use Daycry\Schemas\Events\SchemaEvents;
use Tests\Support\TestCase;

class BaseEventTest extends TestCase
{
    public function testCanCreateEvent(): void
    {
        $data = ['test' => 'data', 'number' => 123];
        $event = new BaseEvent(SchemaEvents::SCHEMA_BEFORE_DRAFT, $data);
        
        $this->assertInstanceOf(BaseEvent::class, $event);
        $this->assertEquals(SchemaEvents::SCHEMA_BEFORE_DRAFT, $event->getName());
        $this->assertEquals($data, $event->getData());
    }

    public function testEventName(): void
    {
        $event = new BaseEvent('test.event', []);
        
        $this->assertEquals('test.event', $event->getName());
    }

    public function testEventData(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $event = new BaseEvent('test', $data);
        
        $this->assertEquals($data, $event->getData());
        $this->assertEquals('value1', $event->get('key1'));
        $this->assertEquals('value2', $event->get('key2'));
        $this->assertNull($event->get('nonexistent'));
        $this->assertEquals('default', $event->get('nonexistent', 'default'));
    }

    public function testSetEventData(): void
    {
        $event = new BaseEvent('test', []);
        
        $event->set('new_key', 'new_value');
        $this->assertEquals('new_value', $event->get('new_key'));
        
        $event->setData(['replaced' => 'data']);
        $this->assertEquals(['replaced' => 'data'], $event->getData());
        $this->assertNull($event->get('new_key'));
    }

    public function testHasData(): void
    {
        $event = new BaseEvent('test', ['existing' => 'value']);
        
        $this->assertTrue($event->has('existing'));
        $this->assertFalse($event->has('nonexistent'));
    }

    public function testPropagationStopping(): void
    {
        $event = new BaseEvent('test', []);
        
        $this->assertFalse($event->isPropagationStopped());
        
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testEventCreationTime(): void
    {
        $beforeCreation = microtime(true);
        $event = new BaseEvent('test', []);
        $afterCreation = microtime(true);
        
        $createdAt = $event->getCreatedAt();
        
        $this->assertGreaterThanOrEqual($beforeCreation, $createdAt);
        $this->assertLessThanOrEqual($afterCreation, $createdAt);
    }

    public function testEventWithComplexData(): void
    {
        $complexData = [
            'array' => [1, 2, 3],
            'object' => (object) ['prop' => 'value'],
            'nested' => [
                'deep' => [
                    'value' => 'test'
                ]
            ]
        ];
        
        $event = new BaseEvent('complex', $complexData);
        
        $this->assertEquals($complexData, $event->getData());
        $this->assertEquals([1, 2, 3], $event->get('array'));
        $this->assertEquals('value', $event->get('object')->prop);
        $this->assertEquals(['deep' => ['value' => 'test']], $event->get('nested'));
    }

    public function testEventImmutableName(): void
    {
        $event = new BaseEvent('original_name', []);
        
        $this->assertEquals('original_name', $event->getName());
        
        // Name should not be changeable after creation
        // (BaseEvent doesn't have a setName method, so name is immutable)
        $this->assertEquals('original_name', $event->getName());
    }

    public function testEventDataOverwrite(): void
    {
        $event = new BaseEvent('test', ['original' => 'value']);
        
        $this->assertEquals('value', $event->get('original'));
        
        $event->set('original', 'new_value');
        $this->assertEquals('new_value', $event->get('original'));
    }
}
