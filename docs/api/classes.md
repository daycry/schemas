# API Reference - Current Core Classes

This document lists the classes that remain in the trimmed (minimal) core of the Daycry Schemas library. Classes from previously larger feature sets (validation, performance analysis, advanced relation detection, intelligent cache manager, event/hook system, extended logging) have been removed and are intentionally omitted here. Any deprecated stubs (e.g. `SchemaLogger`) are excluded.

## Core Classes

### Schemas

The main entry point for the library.

```php
namespace Daycry\Schemas;

class Schemas extends BaseHandler
{
    // Constructor
    public function __construct(?Config\Schemas $config = null, ?CacheInterface $cache = null)
    
    // Main Methods
    public function get(): Structures\Schema                    // Get complete schema
    public function getTable(string $tableName): ?Structures\Table  // Get specific table
    public function getTables(): Structures\Mergeable          // Get all tables
    public function draft(): Structures\Schema                 // Draft schema from database
    public function archive(Structures\Schema $schema): bool   // Archive schema
    
    // Configuration
    public function setDatabase(string $group): self           // Set database group
    public function getConfig(): Config\Schemas                // Get configuration
    public function getErrors(): array                         // Get error messages
}
```

### BaseHandler

Base class for all handlers providing common functionality.

```php
namespace Daycry\Schemas;

abstract class BaseHandler
{
    // Properties
    protected ?Config\Schemas $config
    protected array $errors = []
    
    // Constructor
    public function __construct(?Config\Schemas $config = null)
    
    // Methods
    public function getErrors(): array                          // Get error messages
    public function clearErrors(): self                        // Clear errors
    protected function logError(string $message): void         // Log error message
}
```

## Structure Classes

### Schema

Top-level container for database schema.

```php
namespace Daycry\Schemas\Structures;

class Schema extends Mergeable
{
    public ?Mergeable $tables       // Collection of tables
    public array $metadata = []     // Schema metadata
    public ?string $version         // Schema version
    public ?string $created_at      // Creation timestamp
    
    public function __construct()
    public function addTable(Table $table): self
    public function removeTable(string $name): self
    public function hasTable(string $name): bool
    public function getTable(string $name): ?Table
}
```

### Table

Represents a database table.

```php
namespace Daycry\Schemas\Structures;

class Table extends Mergeable
{
    public ?string $name            // Table name
    public ?string $comment         // Table comment
    public ?string $engine          // Storage engine (MySQL)
    public ?string $collation       // Collation
    public ?string $charset         // Character set
    public ?Mergeable $fields       // Collection of fields
    public ?Mergeable $indexes      // Collection of indexes
    public ?Mergeable $foreignKeys  // Collection of foreign keys
    public ?Mergeable $relations    // Collection of relations
    public array $options = []      // Additional options
    
    public function __construct(?string $name = null)
    public function addField(Field $field): self
    public function addIndex(Index $index): self
    public function addForeignKey(ForeignKey $foreignKey): self
    public function addRelation(Relation $relation): self
    public function hasField(string $name): bool
    public function getField(string $name): ?Field
    public function getPrimaryKey(): ?Index
}
```

### Field

Represents a table column.

```php
namespace Daycry\Schemas\Structures;

class Field extends Mergeable
{
    public ?string $name            // Field name
    public ?string $type            // Data type
    public ?int $max_length         // Maximum length
    public bool $nullable = false   // Allows NULL
    public mixed $default           // Default value
    public bool $auto_increment = false  // Auto increment
    public bool $primary_key = false     // Primary key
    public ?string $comment         // Field comment
    public bool $unsigned = false   // Unsigned (numeric)
    public ?int $precision          // Decimal precision
    public ?int $scale              // Decimal scale
    public array $attributes = []   // Additional attributes
    
    public function __construct(?string $name = null)
    public function isNumeric(): bool
    public function isString(): bool
    public function isDate(): bool
    public function getDefinition(): string
}
```

### Index

Represents a database index.

```php
namespace Daycry\Schemas\Structures;

class Index extends Mergeable
{
    public ?string $name            // Index name
    public array $fields = []       // Indexed fields
    public ?string $type            // Index type
    public bool $unique = false     // Unique constraint
    public ?string $method          // Index method (BTREE, HASH)
    public ?string $comment         // Index comment
    public array $lengths = []      // Field length specifications
    
    public function __construct(?string $name = null)
    public function addField(string $field, ?int $length = null): self
    public function isPrimary(): bool
    public function isUnique(): bool
    public function getDefinition(): string
}
```

### ForeignKey

Represents a foreign key constraint.

```php
namespace Daycry\Schemas\Structures;

class ForeignKey extends Mergeable
{
    public ?string $constraint_name      // Constraint name
    public ?string $column_name          // Local column
    public ?string $foreign_table_name   // Referenced table
    public ?string $foreign_column_name  // Referenced column
    public ?string $on_delete           // DELETE action
    public ?string $on_update           // UPDATE action
    public bool $deferrable = false     // Deferrable constraint
    public bool $initially_deferred = false  // Initially deferred
    
    public function __construct()
    public function getDefinition(): string
    public function isValid(): bool
}
```

### Relation

Represents a logical relationship between tables.

```php
namespace Daycry\Schemas\Structures;

class Relation extends Mergeable
{
    public ?string $type            // Relation type
    public ?string $table           // Related table
    public ?string $pivot           // Pivot table (many-to-many)
    public ?string $field           // Local field
    public ?string $foreign_field   // Foreign field
    public array $attributes = []   // Additional attributes
    
    public function __construct()
    public function isOneToMany(): bool
    public function isManyToMany(): bool
    public function getDefinition(): string
}
```

### Extended Structures

#### View

Represents a database view.

```php
namespace Daycry\Schemas\Structures;

class View extends Mergeable
{
    public ?string $name            // View name
    public ?string $definition      // SQL definition
    public bool $updatable = false // Updatable view
    public array $dependencies = [] // Table dependencies
    public ?string $security        // Security type
    public ?string $comment         // View comment
    
    public function __construct(?string $name = null)
    public function getDependencies(): array
    public function isUpdatable(): bool
}
```

#### Procedure

Represents a stored procedure or function.

```php
namespace Daycry\Schemas\Structures;

class Procedure extends Mergeable
{
    public ?string $name                    // Procedure name
    public string $type = 'PROCEDURE'       // Type (PROCEDURE/FUNCTION)
    public ?string $definition              // SQL definition
    public array $parameters = []           // Parameters
    public ?string $returnType              // Return type (functions)
    public ?string $security                // Security type
    public string $language = 'SQL'         // Language
    public bool $deterministic = false      // Deterministic flag
    public ?string $dataAccess              // Data access type
    public ?string $comment                 // Comment
    
    public function __construct(?string $name = null)
    public function isFunction(): bool
    public function addParameter(array $parameter): self
    public function getParameterCount(): int
}
```

#### Trigger

Represents a database trigger.

```php
namespace Daycry\Schemas\Structures;

class Trigger extends Mergeable
{
    public ?string $name            // Trigger name
    public ?string $table           // Target table
    public ?string $timing          // Timing (BEFORE/AFTER)
    public array $events = []       // Events (INSERT/UPDATE/DELETE)
    public ?string $definition      // SQL definition
    public ?int $order              // Execution order
    public ?string $condition       // WHEN condition
    public bool $enabled = true     // Enabled flag
    public ?string $comment         // Comment
    
    public function __construct(?string $name = null)
    public function addEvent(string $event): self
    public function getEvents(): array
    public function isEnabled(): bool
}
```

## Reader Classes

### BaseReader

Base class for all readers.

```php
namespace Daycry\Schemas\Reader;

abstract class BaseReader extends BaseHandler implements ReaderInterface
{
    protected bool $ready = false      // Ready state
    protected ?string $schema          // Current schema
    
    public function ready(): bool                   // Check if ready
    protected function ensureReady(): bool         // Ensure ready state
    public function fetch($tables)                 // Fetch specific tables
    public function fetchAll()                     // Fetch all tables
}
```

### CacheHandler

Reads schemas from cache.

```php
namespace Daycry\Schemas\Reader\Handlers;

class CacheHandler extends BaseReader
{
    protected ?CacheInterface $cache       // Cache instance
    protected ?Mergeable $tables          // Cached tables
    
    public function __construct(?Config\Schemas $config = null, ?CacheInterface $cache = null)
    public function getTables(): ?Mergeable
    public function fetch($tables): self
    public function fetchAll(): self
    public function __get(string $name): ?Table
}
```

## Drafter Classes

### BaseDrafter

Base class for all drafters.

```php
namespace Daycry\Schemas\Drafter;

abstract class BaseDrafter extends BaseHandler implements DrafterInterface
{
    public function draft(): ?Structures\Schema    // Draft schema
}
```

### DatabaseHandler

Drafts schemas from database connections.

```php
namespace Daycry\Schemas\Drafter\Handlers;

class DatabaseHandler extends BaseDrafter
{
    protected BaseConnection $db           // Database connection
    protected string $prefix               // Table prefix
    protected string $fieldRegex          // Field pattern regex
    
    public function __construct(?Config\Schemas $config = null, $db = null)
    public function draft(): ?Structures\Schema
    protected function getTables(): array
    protected function getFields(string $table): array
    protected function getIndexes(string $table): array
    protected function getForeignKeys(string $table): array
}
```

## Archiver Classes

### BaseArchiver

Base class for all archivers.

```php
namespace Daycry\Schemas\Archiver;

abstract class BaseArchiver extends BaseHandler implements ArchiverInterface
{
    public function archive(Structures\Schema $schema): bool  // Archive schema
}
```

### CacheArchiver

Archives schemas to cache.

```php
namespace Daycry\Schemas\Archiver\Handlers;

class CacheArchiver extends BaseArchiver
{
    protected CacheInterface $cache        // Cache instance
    protected string $cacheKey             // Cache key
    protected int $ttl                     // Time to live
    
    public function __construct(?Config\Schemas $config = null, ?CacheInterface $cache = null)
    public function archive(Structures\Schema $schema): bool
    public function retrieve(): ?Structures\Schema
    public function invalidate(): bool
}
```

## Removed Advanced Feature Classes

The following previously documented classes have been removed from the codebase and should not be used:
`SchemaValidator`, `PerformanceAnalyzer`, `AdvancedRelationDetector`, `IntelligentCacheManager`, and any event / hook or extended logging helpers. Applications should implement bespoke logic if such functionality is required.

## Helper Classes

### Mergeable

Base class providing dynamic property management.

```php
namespace Daycry\Schemas\Structures;

abstract class Mergeable implements Countable, IteratorAggregate
{
    public function merge(object $object): self
    public function toArray(): array
    public function count(): int
    public function getIterator(): ArrayIterator
    public function __isset(string $name): bool
    public function __get(string $name): mixed
    public function __set(string $name, mixed $value): void
    public function __unset(string $name): void
}
```

### ValidationResult

Contains validation results and error information.

```php
namespace Daycry\Schemas;

class ValidationResult
{
    protected bool $valid                  // Overall validity
    protected array $errors = []           // Error messages
    protected array $warnings = []         // Warning messages
    
    public function __construct(bool $valid = true)
    public function isValid(): bool
    public function addError(string $error, string $category = 'general'): self
    public function addWarning(string $warning, string $category = 'general'): self
    public function getErrors(string $category = null): array
    public function getWarnings(string $category = null): array
    public function getErrorCount(): int
    public function getWarningCount(): int
    public function getSummary(): array
    public function toArray(): array
}
```

## Configuration Classes

### Schemas Config

Main configuration class.

```php
namespace Daycry\Schemas\Config;

class Schemas extends BaseConfig
{
    public string $defaultGroup = 'default'        // Default DB group
    public array $ignoredTables = []               // Tables to ignore
    public bool $silent = false                    // Silent mode
    public array $cache = []                       // Cache configuration
    public bool $enableValidation = false          // Enable validation
    public bool $enablePerformanceAnalysis = false // Enable performance analysis
    public bool $enableIntelligentCache = false    // Enable intelligent caching
    public array $logging = []                     // Logging configuration
    public array $performance = []                 // Performance settings
    public array $relationships = []               // Relationship detection
}
```

## Interfaces

### ReaderInterface (Fluent)

Interface for all readers. Methods now return the implementing instance (`static`) enabling fluent chaining.

```php
namespace Daycry\Schemas\Reader;

interface ReaderInterface extends \Countable, \IteratorAggregate
{
    public function ready(): bool;
    /** @return static */
    public function fetch($tables);
    /** @return static */
    public function fetchAll();
}
```

### DrafterInterface

Interface for all drafters.

```php
namespace Daycry\Schemas\Drafter;

interface DrafterInterface
{
    public function draft(): ?Structures\Schema;
}
```

### ArchiverInterface

Interface for all archivers.

```php
namespace Daycry\Schemas\Archiver;

interface ArchiverInterface
{
    public function archive(Structures\Schema $schema): bool;
}
```

## Usage Patterns

### Factory Pattern

```php
// Create components using factory methods
$reader = ReaderFactory::create('cache', $config);
$drafter = DrafterFactory::create('database', $config);
$archiver = ArchiverFactory::create('file', $config);
```

### Reader Fluent Interface

Reader handler example demonstrating fluent `fetch` / `fetchAll`:

```php
$cacheReader
    ->fetch(['users'])
    ->fetch(['posts'])
    ->fetchAll();
```

Structure objects themselves retain basic mutable public properties; any prior dedicated fluent mutator helpers removed from docs for clarity.

### Normalization Highlights

- Field integer flags are cast to booleans upon construction (`primary_key`, `nullable`, `auto_increment`).
- Foreign key column fields that arrive as arrays are reduced to their first element.
- Relation inverse table properties fallback to a non‑null string to avoid null propagation.

### Dependency Injection

```php
// Constructor injection
class MyService
{
    public function __construct(
        private Schemas $schemas,
        private SchemaValidator $validator,
        private PerformanceAnalyzer $analyzer
    ) {}
}
```

This API reference provides the foundation for understanding and working with all classes in the Daycry Schemas library. Each class is designed to work together seamlessly while maintaining clear separation of concerns.
