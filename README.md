# Schemas (Minimal Core) for CodeIgniter 4

[![Build status](https://github.com/daycry/schemas/actions/workflows/php.yml/badge.svg?branch=master)](https://github.com/daycry/schemas/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/daycry/schemas/badge.svg?branch=master)](https://coveralls.io/github/daycry/schemas?branch=master)
[![Downloads](https://poser.pugx.org/daycry/schemas/downloads)](https://packagist.org/packages/daycry/schemas)
[![GitHub release (latest by date)](https://img.shields.io/github/v/release/daycry/schemas)](https://packagist.org/packages/daycry/schemas)
[![GitHub stars](https://img.shields.io/github/stars/daycry/schemas)](https://packagist.org/packages/daycry/schemas)
[![GitHub license](https://img.shields.io/github/license/daycry/schemas)](https://github.com/daycry/schemas/blob/master/LICENSE)

> Minimal core edition: focused only on drafting (database / model / directory), reading, and archiving (cache). Async, plugins, events, layered environments, complex validation, advanced relation flags, and export/import formats were removed for a lean runtime.

## Quick Start

1. Install with Composer:
   `composer require daycry/schemas`
2. Generate and cache a schema (database + models) via spark:
   `php spark schemas -draft database,model -archive cache`
3. Fetch it in your code:
   ```php
   $schema = service('schemas')->get();
   ```

## Core Feature Summary

* Database structure introspection (tables, fields, indexes, foreign keys)
* Draft sources: database, models, directory (user-provided PHP schema files)
* Archive + read from cache (file/redis/etc via CI4 cache handlers)
* Lightweight structure objects: Schema, Table, Field, Index, ForeignKey, Relation
* Simple automation flags (draft / archive / read)
* Explicit, extensible handler lists (swap or extend by config)

## Removed Legacy Subsystems

| Removed Feature | Why | Replacement / Path |
|-----------------|-----|--------------------|
| Async background drafting | Complexity > benefit | Run synchronously (fast) |
| Plugin manager | Indirection overhead | Create small wrapper packages |
| Event bus | Rare real use | Add domain events externally if needed |
| Export/Import (json/php/yaml) | Serialization bloat | Future addon (see Extensions) |
| Snapshot/runtime overrides | Hidden mutable state | Explicit service instances |
| Layered environment profiles | Hard to reason | Single flat config class |
| Validation engine | Redundant vs tests | Rely on types + PHPUnit |
| Logging / metrics collector | Unnecessary core weight | Use app/logger directly |
| Advanced relation flags | Too many toggles | Single boolean `$relationships` |

If you relied on something removed, create a thin external package that composes over this core.

## Installation

Composer (recommended):

```
composer require daycry/schemas
```

Manual: clone/download and add the `src` namespace to your `app/Config/Autoload.php`.

## Configuration

Publish (optional) configuration to your app (if a publisher command is present) or copy the distributed template to `app/Config/Schemas.php`.

Key options (see `src/Config/Schemas.php`):

```php
public string $defaultGroup = 'default';        // Database group
public array  $ignoredTables = ['migrations'];  // Skip these tables
public array  $includedTables = [];             // If non-empty: only these tables
public string $tablePrefix = '';                // Add/remove prefix handling
public bool   $silent = true;                   // Best-effort mode
public array  $cache = [                        // Archive/read cache config
    'enabled' => false,
    'handler' => 'file',
    'ttl'     => 3600,
    'prefix'  => 'schemas_',
];
public bool $relationships = true;              // Auto relation detection
public array $automate = [                      // Auto actions when needed
    'draft' => true,
    'archive' => true,
    'read' => true,
];
public array $draftHandlers = [                 // Order-sensitive
    'database'  => DatabaseHandler::class,
    'model'     => ModelHandler::class,
    'directory' => DirectoryHandler::class,
];
public array $archiveHandlers = [               // Archive modes
    'cache' => CacheHandler::class,
];
public array $readHandlers = [                  // Reader sources
    'cache'     => \Daycry\Schemas\Reader\Handlers\CacheHandler::class,
    'directory' => \Daycry\Schemas\Reader\Handlers\DirectoryHandler::class,
    'php'       => \Daycry\Schemas\Reader\Handlers\PhpHandler::class,
    'json'      => \Daycry\Schemas\Reader\Handlers\JsonHandler::class,
];
```

Edit handler lists to extend or swap implementations.

## Public API Surface

The intent is a small, explicit contract. Everything not listed here should be considered internal and subject to change between minor versions.

### Service: `Daycry\Schemas\Schemas`

Workflow (chainable) methods:
* `draft(array|string|null $handlers = null): self` – Merge drafted structures from one or more handler keys or class names. Null = all configured draft handlers in order.
* `archive(string|array $mode = 'cache'): self` – Persist the current schema using configured archive handler(s) for a mode, or pass an array of archiver instances.
* `read(string|array $path): self` – Load schema data from cache/directory/php/json sources and merge.

State helpers:
* `get(): ?Schema` – Current in-memory schema (or `null`).
* `setSchema(Schema $schema): self` – Replace current schema.
* `reset(): self` – Clear schema & errors.
* `getErrors(): string[]` – Retrieve & clear collected errors.

### Structures (`Daycry\Schemas\Structures`)
Plain data objects (all final except `Mergeable` base):
* `Schema`, `Table`, `Field`, `Index`, `ForeignKey`, `Relation`, plus additional DB objects if present (e.g., Procedure, Trigger, View).

### Draft Handlers (`Daycry\Schemas\Drafter\Handlers`)
* `DatabaseHandler` – Introspects DB schema via configured database group.
* `ModelHandler` – Parses application Models for table/field hints.
* `DirectoryHandler` (+ sub-handlers like `DirectoryHandlers\PhpHandler`) – Loads user-provided schema PHP files.

### Archive Handlers (`Daycry\Schemas\Archiver\Handlers`)
* `CacheHandler` – Stores schema in CodeIgniter Cache.

### Reader Handlers (`Daycry\Schemas\Reader\Handlers`)
* `CacheHandler`, `DirectoryHandler`, `PhpHandler`, `JsonHandler` – Rehydrate schema from respective sources.

### Extensibility Points
* Add a new draft handler: implement `DrafterInterface`, register in `$draftHandlers`.
* Add an archive handler: implement `ArchiverInterface`, add to `$archiveHandlers` list or new mode key.
* Add a reader: implement `ReaderInterface`, map extension/key in `$readHandlers`.
* Provide custom static schema slices: place PHP schema files in your configured `schemasDirectory`.

## Usage

Basic automated workflow (all automate flags true):

```php
$schemas = service('schemas');
$schema = $schemas->get(); // Will auto draft/read/archive on first call depending on flags
```

Manual workflow control:

```php
$schemas = service('schemas');

// Draft database + models then archive
$schemas->draft(['database','model'])->archive();

// Later, read from cache, add directory schemas, and fetch
$schema = $schemas->read('cache')->draft('directory')->get();
```

Custom handler instance:

```php
$db = db_connect('alternate');
$schemas = service('schemas');
$databaseHandler = new \Daycry\Schemas\Drafter\Handlers\DatabaseHandler(config('Schemas'), $db);
$schema = $schemas->draft([$databaseHandler])->get();
```

## Command

Spark command to draft & archive or print schemas:

```
php spark schemas -draft database,model -archive cache
php spark schemas -draft database,model,directory -print
```

Flags:
* `-draft handler1,handler2` (optional; default = all configured)
* `-archive cache` (or omit to skip archiving)
* `-print` output current schema to console (bypasses archive)

## Automation

`$automate` is evaluated when calling `get()` if no schema exists yet:
* `draft`: run all draft handlers (or configured subset) to build schema
* `archive`: persist after drafting
* `read`: attempt to read before drafting

Disable any flag for explicit control in performance‑critical flows.

## Structure

Schemas uses foreign keys, indexes, and naming convention to detect relationships
automatically. Make sure your database is setup using the appropriate keys and
foreign keys to assist with the detection. Naming conventions follow the format of
`{table}_id` for foreign keys and `{table1}_{table2}` for pivot tables. For more examples
on relationship naming conventions consult the Rails Guide
...(Rails reference omitted for brevity)...

### Intervention

Should autodetection fail or should you need to deviate from conventions there are a few
tools you can use to overwrite or augment the generated schema.

* **Config/Schemas**: the Config file includes a variable for `$ignoredTables` that will let you skip tables entirely. By default this includes the framework's `migrations` table.
* **app/Schemas/{file}.php**: The `DirectoryHandler` will load any schemas detected in your **Schemas** directory - this gives you a chance to specify anything you want. See [tests/_support/Schemas/Good/Products.php](tests/_support/Schemas/Good/Products.php) for an example.

## Supported Draft / Archive / Read

Draft: database, model, directory (PHP files)

Archive: cache

Read: cache, directory, php, json

## Database Support

All CodeIgniter 4 database drivers work but due to some differences in index handling they
may not all report the same results. Example: see skipped tests for SQLite3.

## Extensions & Addons

You can prototype external addons without modifying core by creating packages that:
* Provide new handler classes (implement the appropriate interface)
* Recommend a config snippet for users to append handlers
* (Optionally) add a spark command for export or diagnostics

Example add‑ons (future packages):
* `daycry/schemas-export-json` – export/import JSON
* `daycry/schemas-events` – lightweight event dispatcher wrapper

## Roadmap (Minimal Core Perspective)

Potential future (only if demanded by real-world use):
* External export/import addon(s)
* Optional tiny event hook layer
* Migration diff generation as standalone tool

PRs welcome – keep the core surface area minimal.