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

#### Methods
```php
// Set table filter
$reader->setTableFilter(['users', 'posts', 'categories']);

// Set tables to ignore
$reader->setIgnorePattern('temp_*');

// Fetch schema information
$reader->fetchAll();
$tables = $reader->getTables();

// Get database metadata
$dbInfo = $reader->getDatabaseInfo();
// Returns: ['version' => '8.0.25', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci']
```

### FileHandler (Reader)

Reads schema data from JSON/XML files.

#### Configuration
```php
use Daycry\Schemas\Reader\Handlers\FileHandler;

$config = config('Schemas');
$config->file = [
    'path' => WRITEPATH . 'schemas/',
    'format' => 'json',
    'compression' => false
];

$reader = new FileHandler($config);
```

#### Methods
```php
// Set file path
$reader->setFilePath('/path/to/schema.json');

// Set file format
$reader->setFormat('json'); // or 'xml', 'yaml'

// Fetch from file
$reader->fetchAll();
$tables = $reader->getTables();

// Check file existence and validity
if ($reader->fileExists() && $reader->isFileValid()) {
    $reader->fetchAll();
}
```

## Drafter Handlers

Drafters implement the `DrafterInterface` and extend `BaseDrafter` to create fresh schema definitions from various sources.

### DatabaseHandler (Drafter)

Creates schema definitions by introspecting database structures.

#### Configuration
```php
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;

$config = config('Schemas');
$config->relationships['enabled'] = true;
$config->performance['enabled'] = true;

$db = \Config\Database::connect();
$drafter = new DatabaseHandler($config, $db);
```

#### Methods
```php
// Set table filter
$drafter->setTableFilter(['users', 'posts']);

// Enable/disable relation detection
$drafter->setRelationDetection(true);

// Set prefix to ignore
$drafter->setIgnorePrefix('temp_');

// Draft complete schema
$schema = $drafter->draft();

// Get database information
$dbInfo = $drafter->getDatabaseInfo();

// Get table statistics
$stats = $drafter->getTableStats('users');
// Returns: ['rows' => 1500, 'size' => '2.5MB', 'index_size' => '512KB']
```

#### Advanced Features
```php
// Enable advanced relationship detection
$drafter->setAdvancedRelationDetection([
    'polymorphic' => true,
    'self_referencing' => true,
    'many_to_many' => true
]);

// Set custom naming conventions
$drafter->setNamingConventions([
    'foreign_key_suffix' => '_id',
    'pivot_table_pattern' => '{table1}_{table2}'
]);

// Include views in schema
$drafter->includeViews(true);

// Include stored procedures
$drafter->includeProcedures(true);
```

#### Usage Example
```php
$drafter = new DatabaseHandler($config, $db);

// Configure drafting options
$drafter->setTableFilter(['users', 'posts', 'categories'])
        ->setRelationDetection(true)
        ->includeViews(false);

// Draft the schema
$schema = $drafter->draft();

echo "Drafted schema with " . count($schema->tables) . " tables";
foreach ($schema->tables as $table) {
    echo "Table: {$table->name} ({$table->fields->count()} fields)";
}
```

### DirectoryHandler (Drafter)

Creates schema definitions by scanning database migration files.

#### Configuration
```php
use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;

$config = config('Schemas');
$config->migration = [
    'path' => APPPATH . 'Database/Migrations/',
    'pattern' => '*.php',
    'namespace' => 'App\Database\Migrations'
];

$drafter = new DirectoryHandler($config);
```

#### Methods
```php
// Set migration directory
$drafter->setMigrationPath(APPPATH . 'Database/Migrations/');

// Set file pattern
$drafter->setFilePattern('*_create_*.php');

// Set namespace
$drafter->setNamespace('App\Database\Migrations');

// Draft from migrations
$schema = $drafter->draft();

// Get migration files found
$files = $drafter->getMigrationFiles();
```

### ModelHandler (Drafter)

Creates schema definitions by analyzing CodeIgniter model files.

#### Configuration
```php
use Daycry\Schemas\Drafter\Handlers\ModelHandler;

$config = config('Schemas');
$config->model = [
    'path' => APPPATH . 'Models/',
    'namespace' => 'App\Models',
    'base_model' => 'CodeIgniter\Model'
];

$drafter = new ModelHandler($config);
```

#### Methods
```php
// Set models directory
$drafter->setModelPath(APPPATH . 'Models/');

// Set model namespace
$drafter->setNamespace('App\Models');

// Draft from models
$schema = $drafter->draft();

// Get analyzed models
$models = $drafter->getAnalyzedModels();
```

## Archiver Handlers

Archivers implement the `ArchiverInterface` and extend `BaseArchiver` to store schema data in various storage systems.

### CacheArchiver

Stores schema data in cache systems with advanced features.

#### Configuration
```php
use Daycry\Schemas\Archiver\Handlers\CacheArchiver;

$config = config('Schemas');
$config->cache = [
    'enabled' => true,
    'versioning' => true,
    'compression' => true,
    'tags' => ['schemas', 'database']
];

$cache = \Config\Services::cache();
$archiver = new CacheArchiver($config, $cache);
```

#### Methods
```php
// Archive schema
$success = $archiver->archive($schema);

// Set custom TTL
$archiver->setTtl(7200); // 2 hours

// Set cache tags
$archiver->setTags(['schemas', 'v2.0', 'production']);

// Retrieve archived schema
$schema = $archiver->retrieve();

// Check if archive exists
if ($archiver->exists()) {
    $timestamp = $archiver->getTimestamp();
    echo "Archived at: " . date('Y-m-d H:i:s', $timestamp);
}

// Invalidate archive
$archiver->invalidate();
```

#### Versioning Support
```php
// Enable versioning
$archiver->enableVersioning(true);

// Archive with version
$archiver->archiveVersion($schema, '2.1.0');

// Retrieve specific version
$schema = $archiver->retrieveVersion('2.0.0');

// List available versions
$versions = $archiver->getVersions();
// Returns: ['1.0.0', '1.1.0', '2.0.0', '2.1.0']

// Compare versions
$diff = $archiver->compareVersions('2.0.0', '2.1.0');
```

### FileArchiver

Stores schema data in files with various formats.

#### Configuration
```php
use Daycry\Schemas\Archiver\Handlers\FileArchiver;

$config = config('Schemas');
$config->file = [
    'path' => WRITEPATH . 'schemas/',
    'format' => 'json',
    'compression' => true,
    'backup_count' => 5
];

$archiver = new FileArchiver($config);
```

#### Methods
```php
// Archive to file
$success = $archiver->archive($schema);

// Set custom file path
$archiver->setFilePath('/custom/path/schema.json');

// Set format
$archiver->setFormat('json'); // json, xml, yaml, php

// Enable compression
$archiver->setCompression(true);

// Archive with backup
$archiver->archiveWithBackup($schema);

// Retrieve from file
$schema = $archiver->retrieve();

// Get file information
$info = $archiver->getFileInfo();
// Returns: ['size' => '1.5MB', 'modified' => 1634567890, 'format' => 'json']
```

### DatabaseArchiver

Stores schema data in database tables.

#### Configuration
```php
use Daycry\Schemas\Archiver\Handlers\DatabaseArchiver;

$config = config('Schemas');
$config->database = [
    'table' => 'schema_archives',
    'versioning' => true,
    'compression' => false
];

$db = \Config\Database::connect();
$archiver = new DatabaseArchiver($config, $db);
```

#### Methods
```php
// Archive to database
$success = $archiver->archive($schema);

// Set custom table
$archiver->setTable('custom_schema_table');

// Archive with metadata
$archiver->archiveWithMetadata($schema, [
    'environment' => 'production',
    'version' => '2.1.0',
    'author' => 'admin'
]);

// Retrieve by ID
$schema = $archiver->retrieveById(123);

// Search archives
$archives = $archiver->search([
    'version' => '2.1.0',
    'environment' => 'production'
]);

// Get archive history
$history = $archiver->getHistory('users');
```

## Handler Factories

### ReaderFactory

Creates reader instances based on configuration.

```php
use Daycry\Schemas\Reader\ReaderFactory;

// Create from configuration
$reader = ReaderFactory::create('cache', $config);

// Create with custom parameters
$reader = ReaderFactory::create('database', $config, $db);

// Available types: 'cache', 'database', 'file'
```

### DrafterFactory

Creates drafter instances based on configuration.

```php
use Daycry\Schemas\Drafter\DrafterFactory;

// Create from configuration
$drafter = DrafterFactory::create('database', $config);

// Available types: 'database', 'directory', 'model'
```

### ArchiverFactory

Creates archiver instances based on configuration.

```php
use Daycry\Schemas\Archiver\ArchiverFactory;

// Create from configuration
$archiver = ArchiverFactory::create('cache', $config);

// Available types: 'cache', 'file', 'database'
```

## Custom Handlers

### Creating Custom Readers

```php
use Daycry\Schemas\Reader\BaseReader;
use Daycry\Schemas\Reader\ReaderInterface;

class CustomReader extends BaseReader implements ReaderInterface
{
    public function fetch($tables): self
    {
        // Custom fetch logic
        return $this;
    }
    
    public function fetchAll(): self
    {
        // Custom fetch all logic
        return $this;
    }
    
    protected function loadTables(): array
    {
        // Custom table loading logic
        return [];
    }
}
```

### Creating Custom Drafters

```php
use Daycry\Schemas\Drafter\BaseDrafter;
use Daycry\Schemas\Drafter\DrafterInterface;

class CustomDrafter extends BaseDrafter implements DrafterInterface
{
    public function draft(): ?Structures\Schema
    {
        // Custom drafting logic
        $schema = new Structures\Schema();
        
        // Add tables, fields, indexes, etc.
        
        return $schema;
    }
}
```

### Creating Custom Archivers

```php
use Daycry\Schemas\Archiver\BaseArchiver;
use Daycry\Schemas\Archiver\ArchiverInterface;

class CustomArchiver extends BaseArchiver implements ArchiverInterface
{
    public function archive(Structures\Schema $schema): bool
    {
        // Custom archiving logic
        try {
            // Store schema data
            return true;
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            return false;
        }
    }
    
    public function retrieve(): ?Structures\Schema
    {
        // Custom retrieval logic
        return null;
    }
}
```

## Handler Chains

### Reader Chain

Process multiple readers in sequence:

```php
use Daycry\Schemas\Reader\ReaderChain;

$chain = new ReaderChain();
$chain->addReader(new CacheHandler($config, $cache))
      ->addReader(new DatabaseHandler($config, $db))
      ->addReader(new FileHandler($config));

// Will try cache first, then database, then file
$tables = $chain->getTables();
```

### Archiver Chain

Archive to multiple destinations:

```php
use Daycry\Schemas\Archiver\ArchiverChain;

$chain = new ArchiverChain();
$chain->addArchiver(new CacheArchiver($config, $cache))
      ->addArchiver(new FileArchiver($config))
      ->addArchiver(new DatabaseArchiver($config, $db));

// Will archive to all destinations
$success = $chain->archive($schema);
```

This comprehensive handlers documentation provides complete information about all available handlers and how to use them effectively in the Daycry Schemas library.
