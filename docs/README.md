# Daycry Schemas Documentation

Minimal, focused documentation for the current (lean) core of the Daycry Schemas library. All removed/legacy subsystems (validation engine, performance analyzer, advanced relation detector, intelligent cache manager, events/hooks, extended logging/metrics) have been dropped from the codebase and are intentionally absent here.

## Table of Contents

### Getting Started
- [Installation](installation.md)
- [Quick Start](quick-start.md)
- [Configuration](configuration.md)

### Core Concepts
- Reading Schemas (via `Schemas` orchestrator & Reader handlers)
- Drafting Schemas (database & directory drafters)
- Archiving Schemas (cache / file)
- [Structures](core/structures.md)

### Handlers (Current Core)
- Cache Reader / Archiver
- Database Drafter
- Directory (Migration) Drafter
- Database Object Reader (views / procedures / triggers) – if present in build
- JSON / File Archiver (basic format support)

### API Reference
- [Classes Overview](api/classes.md) (pruned to current core)
- Configuration (minimal options)

### Examples
- Basic usage (see `examples/basic-usage.md`)
- Draft → Archive → Read round‑trip
- Fluent Reader chaining

### Troubleshooting
- [Common Issues](troubleshooting/common-issues.md)

### Contributing
- [Contribution Guide](../contributing.md) (general project guidelines)

## Overview

The library provides a pragmatic way to introspect, serialize and reuse database schema metadata inside CodeIgniter 4 applications. It focuses on:

1. Drafting a schema from a live database (tables, fields, indexes, foreign keys, relations, and basic database objects when supported)
2. Archiving the drafted schema for fast, repeatable access (cache or file)
3. Reading the archived (or live) schema in a fluent, iterable structure model

Removed subsystems previously covering validation, performance scoring, polymorphic detection, and extended logging have been intentionally excluded to keep maintenance surface minimal.

## Core Architecture

Component types:

- Readers: Load tables/objects from cache or other persisted mediums (`fetch`, `fetchAll`) using the new fluent interface returning `$this`.
- Drafters: Introspect sources (database, migration directory, models) to build an in‑memory `Schema` object.
- Archivers: Persist a `Schema` for later retrieval (cache or file JSON). XML or other formats may exist only if corresponding handlers remain in `src/`.

All structural elements extend a `Mergeable` base enabling dynamic property hydration and merging.

## Quick Example

```php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$schema  = $schemas->get(); // Drafts (if no archive) then returns a populated Schema instance

foreach ($schema->tables as $table) {
    echo "Table: {$table->name}\n";
}
```

### Round Trip (Draft → Archive → Read)

```php
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Archiver\Handlers\CacheArchiver;
use Daycry\Schemas\Reader\Handlers\CacheHandler;

$ciConfig  = config('Schemas');
$cache     = \Config\Services::cache();

// Draft fresh schema
$schemas   = new Schemas($ciConfig, $cache);
$schema    = $schemas->draft();

// Archive
$archiver  = new CacheArchiver($ciConfig, $cache);
$archiver->archive($schema);

// Later: read from cache
$cacheReader = new CacheHandler($ciConfig, $cache);
$cacheReader->fetchAll();
$tables = $cacheReader->getTables();
```

## Fluent Reader Methods

All reader handlers now return `$this` from `fetch($tables)` and `fetchAll()` allowing chaining:

```php
$cacheReader
    ->fetch(['users'])
    ->fetch(['posts'])
    ->fetchAll();
```

## Structure Normalizations

- Field booleans (`primary_key`, `nullable`, `auto_increment`) are normalized internally; integer DB flags are cast to strict booleans.
- ForeignKey column names that may emerge as arrays from certain drivers are reduced to the first element.
- Relation inverse table names fallback to a non-null string to prevent null propagation.

## What’s Not Here Anymore

The following concepts were removed and should not appear in new integrations or documentation examples:

- Schema validation engine (`SchemaValidator`)
- Performance analyzer (`PerformanceAnalyzer`)
- Advanced relation detector / polymorphic mapping
- Intelligent cache manager with tagging/versioning
- Extended logging / `SchemaLogger` (now a deprecated no‑op stub for BC)
- Event/hook system & plugin architecture
- Multi-format export/import beyond currently present archiver handlers

## Requirements

- PHP 8.1+
- CodeIgniter 4.x
- A supported database driver consistent with the active CI4 application

## License

MIT. See [LICENSE](../LICENSE).

## Getting Help

- Issues: https://github.com/daycry/schemas/issues

---

Start with [Installation](installation.md) or go straight to the [Quick Start](quick-start.md).
