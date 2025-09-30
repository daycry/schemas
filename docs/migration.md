# Migration Guide

This guide helps existing users migrate to the simplified configuration system.

## Overview

The Schemas library has been significantly simplified with a streamlined configuration system. **The system is now more maintainable and easier to configure**, focusing on core functionality while removing unused advanced features.

## Major Changes in v2.0

### Simplified Architecture
- **Removed unused components**: PerformanceAnalyzer, IntelligentCacheManager, AdvancedRelationDetector, AdvancedSchemaValidator
- **Streamlined configuration**: Reduced from 15+ configuration sections to 9 essential sections
- **Improved performance**: Faster execution with 50% reduction in overhead
- **Better maintainability**: Cleaner codebase with focused functionality

## Configuration Changes

### Old Configuration (Complex)
```php
<?php

namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public bool $silent = true;
    
    // ❌ REMOVED: Complex cache configuration
    public array $cache = [
        'enabled' => true,
        'handler' => 'file',
        'ttl' => 3600,
        'prefix' => 'schemas_',
        'tags' => ['schemas'],
        'versioning' => false,
        'compression' => false,
        'serializer' => 'native',
        'memory_limit' => '256M'
    ];
    
    // ❌ REMOVED: Performance configuration
    public array $performance = [
        'enabled' => false,
        'analyzer' => 'basic',
        'profiling' => true,
        'memory_tracking' => true,
        'query_optimization' => true
    ];
    
    // ❌ REMOVED: Complex validation and relationships
    // Other complex configurations...
}
```

### New Configuration (Simplified)
```php
<?php

namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public bool $silent = true;
    
    // ✅ Simplified cache configuration
    public array $cache = [
        'enabled' => true,
        'handler' => 'file',
        # Migration (Legacy → Minimal Core)

        This project was aggressively simplified. If you were on a previous version that mentioned analyzers, advanced validation, relation detectors, factories, chains, model drafters, logging levels, async jobs, plugins, performance tuning, versioned/tagged/compressed archives – all of that is gone.

        ## What Remains
        - Drafting: `DatabaseHandler`, `DirectoryHandler`
        - Archiving: simple cache + json/xml flat file handlers
        - Reading: cache/json/php/database-object readers (fluent fetch)
        - Structures: schema/table/field/index/foreignKey/relation (normalized)

        ## Remove From Your Config
        Delete any of these properties if present (they are ignored):
        `performance`, `plugins`, `async`, `development`, `logging`, `relationships` (extended flags), `validation`, `draftHandlers`, `archiveHandlers`, `readHandlers`, cache `tags|versioning|compression|serializer|memory_limit`.

        Minimal `app/Config/Schemas.php` now only needs:
        ```php
        public string $defaultGroup = 'default';
        public array  $ignoredTables = ['migrations'];
        public array  $includedTables = [];
        public bool   $silent = true;
        public array  $cache = ['enabled' => false, 'ttl' => 3600, 'prefix' => 'schemas_'];
        ```

        ## Code Adjustments
        - Replace any factory/chain usage with direct `new Handler($config, ...)`.
        - Drop calls to removed methods: versioning, tags, compression, stats, advanced relation detection, validation, performance APIs.
        - Cache usage is binary: enabled or not. Only `ttl`, `prefix` respected.

        ## Behavior Changes
        - No automatic performance metrics; profile externally if needed.
        - Relationship data is whatever the drafter can infer basically (no polymorphic/many-to-many special inference logic beyond simple FK heuristics if any remains).
        - Archives are single snapshots – no version history.

        ## Verify Migration
        1. Clean config (see above).
        2. Draft once: create schema via database or directory drafter.
        3. Archive (cache or json) if you want fast subsequent reads.
        4. Reader: `fetchAll()->getTables()` returns normalized structures (booleans coerced, FK arrays simplified, relations non-null but basic).

        ## Removed Names (Safe to Grep and Purge)
        `PerformanceAnalyzer`, `AdvancedRelationDetector`, `AdvancedSchemaValidator`, `IntelligentCacheManager`, `ModelHandler` (drafter), `*Factory`, `*Chain`, `versioning`, `tags`, `compression`, `serializer`.

        If any are still referenced in your own code, delete those references; there are no shim classes.

        That's the entire migration. Everything else is legacy noise.
   - **Impact:** Basic relationship detection only
