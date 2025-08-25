<?php

namespace Tests\Events;

use Daycry\Schemas\Events\SchemaEvents;
use Tests\Support\TestCase;

class SchemaEventsTest extends TestCase
{
    public function testEventConstants(): void
    {
        $this->assertIsString(SchemaEvents::SCHEMA_BEFORE_DRAFT);
        $this->assertIsString(SchemaEvents::SCHEMA_AFTER_DRAFT);
        $this->assertIsString(SchemaEvents::SCHEMA_BEFORE_ARCHIVE);
        $this->assertIsString(SchemaEvents::SCHEMA_AFTER_ARCHIVE);
        $this->assertIsString(SchemaEvents::SCHEMA_VALIDATION_SUCCESS);
        $this->assertIsString(SchemaEvents::SCHEMA_VALIDATION_FAILED);
    }

    public function testGetAllEvents(): void
    {
        $events = SchemaEvents::getAllEvents();
        
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
        
        // Check that all constant events are included
        $this->assertContains(SchemaEvents::SCHEMA_BEFORE_DRAFT, $events);
        $this->assertContains(SchemaEvents::SCHEMA_AFTER_DRAFT, $events);
        $this->assertContains(SchemaEvents::SCHEMA_BEFORE_ARCHIVE, $events);
        $this->assertContains(SchemaEvents::SCHEMA_AFTER_ARCHIVE, $events);
        $this->assertContains(SchemaEvents::SCHEMA_VALIDATION_SUCCESS, $events);
        $this->assertContains(SchemaEvents::SCHEMA_VALIDATION_FAILED, $events);
    }

    public function testGetEventsByCategory(): void
    {
        $schemaEvents = SchemaEvents::getEventsByCategory('schema');
        $this->assertIsArray($schemaEvents);
        $this->assertContains(SchemaEvents::SCHEMA_BEFORE_DRAFT, $schemaEvents);
        
        $tableEvents = SchemaEvents::getEventsByCategory('table');
        $this->assertIsArray($tableEvents);
        $this->assertContains(SchemaEvents::TABLE_DISCOVERED, $tableEvents);
        
        $validationEvents = SchemaEvents::getEventsByCategory('validation');
        $this->assertIsArray($validationEvents);
        $this->assertContains(SchemaEvents::SCHEMA_VALIDATION_SUCCESS, $validationEvents);
    }

    public function testGetEventInfo(): void
    {
        $info = SchemaEvents::getEventInfo(SchemaEvents::SCHEMA_BEFORE_DRAFT);
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('category', $info);
        $this->assertArrayHasKey('description', $info);
        
        $this->assertEquals(SchemaEvents::SCHEMA_BEFORE_DRAFT, $info['name']);
        $this->assertEquals('schema', $info['category']);
        $this->assertIsString($info['description']);
    }

    public function testGetEventInfoForNonExistentEvent(): void
    {
        $info = SchemaEvents::getEventInfo('non.existent.event');
        
        $this->assertNull($info);
    }

    public function testIsValidEvent(): void
    {
        $this->assertTrue(SchemaEvents::isValidEvent(SchemaEvents::SCHEMA_BEFORE_DRAFT));
        $this->assertTrue(SchemaEvents::isValidEvent(SchemaEvents::TABLE_DISCOVERED));
        $this->assertFalse(SchemaEvents::isValidEvent('invalid.event'));
        $this->assertFalse(SchemaEvents::isValidEvent(''));
    }

    public function testEventNamingConvention(): void
    {
        $events = SchemaEvents::getAllEvents();
        
        foreach ($events as $event) {
            // Events should follow lowercase dot notation
            $this->assertMatchesRegularExpression('/^[a-z]+(\.[a-z_]+)*$/', $event);
        }
    }

    public function testGetCategoriesReturnsAllCategories(): void
    {
        $categories = SchemaEvents::getCategories();
        
        $this->assertIsArray($categories);
        $this->assertContains('schema', $categories);
        $this->assertContains('table', $categories);
        $this->assertContains('validation', $categories);
        $this->assertContains('performance', $categories);
        $this->assertContains('cache', $categories);
    }

    public function testEventDescriptions(): void
    {
        $events = [
            SchemaEvents::SCHEMA_BEFORE_DRAFT,
            SchemaEvents::SCHEMA_AFTER_DRAFT,
            SchemaEvents::SCHEMA_BEFORE_ARCHIVE,
            SchemaEvents::SCHEMA_AFTER_ARCHIVE,
            SchemaEvents::SCHEMA_VALIDATION_SUCCESS,
            SchemaEvents::SCHEMA_VALIDATION_FAILED
        ];
        
        foreach ($events as $event) {
            $info = SchemaEvents::getEventInfo($event);
            $this->assertNotNull($info);
            $this->assertNotEmpty($info['description']);
            $this->assertIsString($info['description']);
        }
    }
}
