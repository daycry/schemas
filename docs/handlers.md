# Handlers Documentation

This document provides comprehensive information about all handlers available in the Daycry Schemas library, including Readers, Drafters, and Archivers.

## Overview

Handlers are specialized components that manage different aspects of schema operations:

- **Readers**: Read schema data from various sources (cache, database, files)
- **Drafters**: Create fresh schema definitions from databases
- **Archivers**: Store and retrieve schema data to/from various storage systems

## Reader Handlers

Readers implement the `ReaderInterface` and extend `BaseReader` to provide schema reading capabilities from different sources.

### CacheHandler (Reader)

Reads schema data from cache storage.

#### Configuration
```php
use Daycry\Schemas\Reader\Handlers\CacheHandler;

$config = config('Schemas');
$config->cache = [
    'enabled' => true,
    'handler' => 'redis',
    'prefix' => 'schemas_',
    'ttl' => 3600
];

$cache = \Config\Services::cache();
$reader = new CacheHandler($config, $cache);
```

#### Methods
```php
// Check cache validity
if ($reader->isCacheValid()) {
    $tables = $reader->getTables();
}

// Fetch specific tables from cache
$reader->fetch(['users', 'posts']);

// Fetch all cached tables
$reader->fetchAll();

// Get cache statistics
$stats = $reader->getCacheStats();
// Returns: ['hits' => 150, 'misses' => 5, 'size' => '2.5MB']

// Invalidate cache
$reader->invalidateCache();
```

#### Usage Example
```php
$cacheReader = new CacheHandler($config, $cache);

// Try to load from cache first
if ($cacheReader->ready() && $cacheReader->isCacheValid()) {
    $schema = $cacheReader->getTables();
    echo "Loaded from cache: " . count($schema) . " tables";
} else {
    echo "Cache miss or invalid";
}
```

### DatabaseHandler (Reader)

Reads schema data from existing database schema information.

#### Configuration
```php
use Daycry\Schemas\Reader\Handlers\DatabaseHandler;

$config = config('Schemas');
$db = \Config\Database::connect($config->defaultGroup);
$reader = new DatabaseHandler($config, $db);
```
# Handlers (Minimal Core)

This page lists only the handlers that exist in the trimmed core. Anything mentioning factories, chains, versioning, tags, compression layers, model analysis, advanced relation/performance tooling, or multi‑format archiving was removed. If you still see any of those concepts here, report it – it's a bug in the docs.

## Readers

Readers load an already drafted+archived schema representation. They are intentionally simple and fluent: call `fetch()` (optionally with specific table names) or `fetchAll()`, then access tables through the reader instance.

### CacheHandler

Load schema data from the configured cache store.

```php
use Daycry\Schemas\Reader\Handlers\CacheHandler;

$config = config('Schemas');
$cache  = \Config\Services::cache();

$reader = new CacheHandler($config, $cache);

// Fetch all tables from cache (no-op if cache empty)
$reader->fetchAll();

// Or only specific tables (silently ignores unknown ones)
$reader->fetch(['users', 'posts']);

// Access tables (array of Structures\Table)
$tables = $reader->getTables();
```

### DatabaseObjectHandler (optional)

Reads table/field definitions directly through the database connection when you want a lightweight live read without drafting (best effort – only basic columns / keys). Prefer the drafter+archiver flow for consistent normalized data.

```php
use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;

$config = config('Schemas');
$db     = \Config\Database::connect($config->defaultGroup);

$reader = new DatabaseObjectHandler($config, $db);
$reader->fetchAll();
$tables = $reader->getTables();
```

### JsonHandler / PhpHandler

Read a schema from a previously stored JSON or PHP file (these correspond to basic archive formats kept for convenience).

```php
use Daycry\Schemas\Reader\Handlers\JsonHandler;
// or: use Daycry\Schemas\Reader\Handlers\PhpHandler;

$config = config('Schemas');
$reader = new JsonHandler($config); // looks in writable/schemas by convention

$reader->fetchAll();
$tables = $reader->getTables();
```

## Drafters

Drafters build a fresh in-memory schema snapshot. Only two core drafters remain.

### DatabaseHandler

Introspects the live database connection to build the structure snapshot (tables, fields, indexes, foreign keys, relations).

```php
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;

$config = config('Schemas');
$db     = \Config\Database::connect($config->defaultGroup);

$drafter = new DatabaseHandler($config, $db);
$schema  = $drafter->draft(); // Returns Structures\Schema or null
```

Basic options respected (from `Schemas` config):
- ignoredTables
- tablePrefix (used to strip/display prefix)

### DirectoryHandler

Parses migration (or similar) PHP files to assemble an approximate schema when DB access is unavailable.

```php
use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;

$config = config('Schemas');
$drafter = new DirectoryHandler($config);
$schema  = $drafter->draft();
```

## Archivers

Archivers persist a drafted schema so readers can reuse it. Only the simplest cache + flat file formats remain.

### CacheHandler (Archiver)

Stores the schema object (single blob) in the configured cache store.

```php
use Daycry\Schemas\Archiver\Handlers\CacheHandler as CacheArchiver;

$config = config('Schemas');
$cache  = \Config\Services::cache();

$archiver = new CacheArchiver($config, $cache);
$archiver->archive($schema);   // bool
$restored = $archiver->retrieve(); // Structures\Schema|null
```

### JsonHandler / XmlHandler (Archiver)

Serialize the schema to JSON (or XML) under the writable schemas directory.

```php
use Daycry\Schemas\Archiver\Handlers\JsonHandler as JsonArchiver;
// or: use Daycry\Schemas\Archiver\Handlers\XmlHandler as XmlArchiver;

$config   = config('Schemas');
$archiver = new JsonArchiver($config);
$archiver->archive($schema);
$restored = $archiver->retrieve();
```

## Custom Handlers (Still Supported, Just Simpler)

You can extend `BaseReader`, `BaseDrafter`, or `BaseArchiver` to plug a new source or storage. Keep implementations minimal: load/produce a `Structures\Schema` (with normalized tables, fields, indexes, foreign/relations) or return null on failure.

Example skeleton for a reader:

```php
use Daycry\Schemas\Reader\BaseReader;
use Daycry\Schemas\Reader\ReaderInterface;

class MyReader extends BaseReader implements ReaderInterface
{
    public function fetch($tables): self { /* optional subset handling */ return $this; }
    public function fetchAll(): self { /* load everything */ return $this; }
    protected function loadTables(): array { return []; }
}
```

## Removed (Legacy) Items

Removed and no longer documented: factories, handler chains, model drafter, database/file archivers with metadata/versioning/tags, compression toggles, statistics, advanced relation/performance tuning flags.

If migrating from an older version: replace any factory/chain usages with direct instantiation as shown above.

That is the entire handler surface of the minimal core.
```
