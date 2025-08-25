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

namespace Daycry\Schemas\Events;

/**
 * Event constants for the Schemas system
 */
class SchemaEvents
{
    // Schema lifecycle events
    const SCHEMA_BEFORE_DRAFT = 'schema.before_draft';
    const SCHEMA_AFTER_DRAFT = 'schema.after_draft';
    const SCHEMA_BEFORE_ARCHIVE = 'schema.before_archive';
    const SCHEMA_AFTER_ARCHIVE = 'schema.after_archive';
    const SCHEMA_BEFORE_READ = 'schema.before_read';
    const SCHEMA_AFTER_READ = 'schema.after_read';
    const SCHEMA_BEFORE_PUBLISH = 'schema.before_publish';
    const SCHEMA_AFTER_PUBLISH = 'schema.after_publish';

    // Table events
    const TABLE_DISCOVERED = 'table.discovered';
    const TABLE_ANALYZED = 'table.analyzed';
    const TABLE_OPTIMIZED = 'table.optimized';

    // Relationship events
    const RELATIONSHIP_DETECTED = 'relationship.detected';
    const RELATIONSHIP_VALIDATED = 'relationship.validated';

    // Validation events
    const VALIDATION_STARTED = 'validation.started';
    const VALIDATION_COMPLETED = 'validation.completed';
    const VALIDATION_FAILED = 'validation.failed';
    const SCHEMA_VALIDATION_SUCCESS = 'schema.validation_success';
    const SCHEMA_VALIDATION_FAILED = 'schema.validation_failed';

    // Performance events
    const PERFORMANCE_ANALYSIS_STARTED = 'performance.analysis_started';
    const PERFORMANCE_ANALYSIS_COMPLETED = 'performance.analysis_completed';
    const PERFORMANCE_ISSUE_DETECTED = 'performance.issue_detected';

    // Cache events
    const CACHE_HIT = 'cache.hit';
    const CACHE_MISS = 'cache.miss';
    const CACHE_INVALIDATED = 'cache.invalidated';
    const CACHE_CLEARED = 'cache.cleared';

    // Error events
    const ERROR_OCCURRED = 'error.occurred';
    const WARNING_ISSUED = 'warning.issued';

    // Configuration events
    const CONFIG_LOADED = 'config.loaded';
    const CONFIG_CHANGED = 'config.changed';

    // Plugin events
    const PLUGIN_LOADED = 'plugin.loaded';
    const PLUGIN_UNLOADED = 'plugin.unloaded';
    const PLUGIN_ERROR = 'plugin.error';

    /**
     * Get all available event names
     *
     * @return array<string>
     */
    public static function getAllEvents(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return array_values($reflection->getConstants());
    }

    /**
     * Check if an event name is valid
     */
    public static function isValidEvent(string $eventName): bool
    {
        return in_array($eventName, self::getAllEvents(), true);
    }

    /**
     * Get events by category
     *
     * @return array<string, array<string>>
     */
    public static function getEventsByCategory(?string $category = null): array
    {
        $allEvents = [
            'schema' => [
                self::SCHEMA_BEFORE_DRAFT,
                self::SCHEMA_AFTER_DRAFT,
                self::SCHEMA_BEFORE_ARCHIVE,
                self::SCHEMA_AFTER_ARCHIVE,
                self::SCHEMA_BEFORE_READ,
                self::SCHEMA_AFTER_READ,
                self::SCHEMA_BEFORE_PUBLISH,
                self::SCHEMA_AFTER_PUBLISH,
            ],
            'table' => [
                self::TABLE_DISCOVERED,
                self::TABLE_ANALYZED,
                self::TABLE_OPTIMIZED,
            ],
            'relationship' => [
                self::RELATIONSHIP_DETECTED,
                self::RELATIONSHIP_VALIDATED,
            ],
            'validation' => [
                self::VALIDATION_STARTED,
                self::VALIDATION_COMPLETED,
                self::VALIDATION_FAILED,
                self::SCHEMA_VALIDATION_SUCCESS,
                self::SCHEMA_VALIDATION_FAILED,
            ],
            'performance' => [
                self::PERFORMANCE_ANALYSIS_STARTED,
                self::PERFORMANCE_ANALYSIS_COMPLETED,
                self::PERFORMANCE_ISSUE_DETECTED,
            ],
            'cache' => [
                self::CACHE_HIT,
                self::CACHE_MISS,
                self::CACHE_INVALIDATED,
                self::CACHE_CLEARED,
            ],
            'error' => [
                self::ERROR_OCCURRED,
                self::WARNING_ISSUED,
            ],
            'config' => [
                self::CONFIG_LOADED,
                self::CONFIG_CHANGED,
            ],
            'plugin' => [
                self::PLUGIN_LOADED,
                self::PLUGIN_UNLOADED,
                self::PLUGIN_ERROR,
            ],
        ];
        
        if ($category === null) {
            return $allEvents;
        }
        
        return $allEvents[$category] ?? [];
    }

    /**
     * Get event information
     *
     * @return array<string, mixed>|null
     */
    public static function getEventInfo(string $eventName): ?array
    {
        $eventDescriptions = [
            self::SCHEMA_BEFORE_DRAFT => [
                'name' => self::SCHEMA_BEFORE_DRAFT,
                'category' => 'schema',
                'description' => 'Fired before schema drafting process begins'
            ],
            self::SCHEMA_AFTER_DRAFT => [
                'name' => self::SCHEMA_AFTER_DRAFT,
                'category' => 'schema',
                'description' => 'Fired after schema drafting process completes'
            ],
            self::SCHEMA_BEFORE_ARCHIVE => [
                'name' => self::SCHEMA_BEFORE_ARCHIVE,
                'category' => 'schema',
                'description' => 'Fired before schema archiving process begins'
            ],
            self::SCHEMA_AFTER_ARCHIVE => [
                'name' => self::SCHEMA_AFTER_ARCHIVE,
                'category' => 'schema',
                'description' => 'Fired after schema archiving process completes'
            ],
            self::SCHEMA_VALIDATION_SUCCESS => [
                'name' => self::SCHEMA_VALIDATION_SUCCESS,
                'category' => 'validation',
                'description' => 'Fired when schema validation succeeds'
            ],
            self::SCHEMA_VALIDATION_FAILED => [
                'name' => self::SCHEMA_VALIDATION_FAILED,
                'category' => 'validation',
                'description' => 'Fired when schema validation fails'
            ],
            self::TABLE_DISCOVERED => [
                'name' => self::TABLE_DISCOVERED,
                'category' => 'table',
                'description' => 'Fired when a new table is discovered'
            ],
        ];
        
        return $eventDescriptions[$eventName] ?? null;
    }

    /**
     * Get all categories
     *
     * @return array<string>
     */
    public static function getCategories(): array
    {
        return array_keys(self::getEventsByCategory());
    }
}
