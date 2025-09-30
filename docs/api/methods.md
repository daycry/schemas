# API Reference – Minimal Methods

This trimmed list covers only methods relevant to the minimal core. Anything about: version metadata setters, statistics, advanced relation detection toggles, cache tags/versioning, compression, factories, chains, validation, performance, logging, async – has been removed from both code and documentation.

## Core Methods

### Schemas Class

#### Constructor and Initialization

```php
/**
 * Initialize the Schemas handler
 *
 * @param Config\Schemas|null $config Configuration instance
 * @param CacheInterface|null $cache Cache instance
 */
public function __construct(?Config\Schemas $config = null, ?CacheInterface $cache = null)
```

#### Primary Schema Operations

```php
/**
 * Get the complete database schema
 *
 * @return Structures\Schema The complete schema
 * @throws SchemasException If schema cannot be loaded
 */
public function get(): Structures\Schema

/**
 * Get a specific table schema
 *
 * @param string $tableName The table name
 * @return Structures\Table|null The table schema or null if not found
 */
public function getTable(string $tableName): ?Structures\Table

/**
 * Get all tables as a mergeable collection
 *
 * @return Structures\Mergeable Collection of tables
 */
public function getTables(): Structures\Mergeable

/**
 * Draft a fresh schema from the database
 *
 * @return Structures\Schema The drafted schema
 * @throws SchemasException If drafting fails
 */
public function draft(): Structures\Schema

/**
 * Archive a schema using the configured archiver
 *
 * @param Structures\Schema $schema The schema to archive
 * @return bool True if successful
 */
public function archive(Structures\Schema $schema): bool
```

#### Configuration Methods

```php
/**
 * Set the database group to use
 *
 * @param string $group Database group name
 * @return self For method chaining
 */
public function setDatabase(string $group): self

/**
 * Get the current configuration
 *
 * @return Config\Schemas The configuration instance
 */
public function getConfig(): Config\Schemas

/**
 * Check if a specific table exists
 *
 * @param string $tableName The table name
 * @return bool True if table exists
 */
public function hasTable(string $tableName): bool
```

## Structure Methods

### Schema Class

#### Table Management

```php
/**
 * Add a table to the schema
 *
 * @param Table $table The table to add
 * @return self For method chaining
 * @throws SchemasException If table already exists
 */
public function addTable(Table $table): self

/**
 * Remove a table from the schema
 *
 * @param string $name The table name
 * @return self For method chaining
 */
public function removeTable(string $name): self

/**
 * Check if a table exists in the schema
 *
 * @param string $name The table name
 * @return bool True if table exists
 */
public function hasTable(string $name): bool

/**
 * Get a specific table from the schema
 *
 * @param string $name The table name
 * @return Table|null The table or null if not found
 */
public function getTable(string $name): ?Table

/**
 * Get all table names
 *
 * @return array Array of table names
 */
public function getTableNames(): array
```

#### (Removed Metadata / Versioning)
Versioning & arbitrary metadata setters were dropped. A schema instance is a structural snapshot only.

### Table Class

#### Field Management

```php
/**
 * Add a field to the table
 *
 * @param Field $field The field to add
 * @return self For method chaining
 * @throws SchemasException If field already exists
 */
public function addField(Field $field): self

/**
 * Remove a field from the table
 *
 * @param string $name The field name
 * @return self For method chaining
 */
public function removeField(string $name): self

/**
 * Check if a field exists
 *
 * @param string $name The field name
 * @return bool True if field exists
 */
public function hasField(string $name): bool

/**
 * Get a specific field
 *
 * @param string $name The field name
 * @return Field|null The field or null if not found
 */
public function getField(string $name): ?Field

/**
 * Get all field names
 *
 * @return array Array of field names
 */
public function getFieldNames(): array

/**
 * Get fields by type
 *
 * @param string $type Field type
 * @return array Array of matching fields
 */
public function getFieldsByType(string $type): array
```

#### Index Management

```php
/**
 * Add an index to the table
 *
 * @param Index $index The index to add
 * @return self For method chaining
 * @throws SchemasException If index already exists
 */
public function addIndex(Index $index): self

/**
 * Remove an index from the table
 *
 * @param string $name The index name
 * @return self For method chaining
 */
public function removeIndex(string $name): self

/**
 * Check if an index exists
 *
 * @param string $name The index name
 * @return bool True if index exists
 */
public function hasIndex(string $name): bool

/**
 * Get a specific index
 *
 * @param string $name The index name
 * @return Index|null The index or null if not found
 */
public function getIndex(string $name): ?Index

/**
 * Get the primary key index
 *
 * @return Index|null The primary key or null if not found
 */
public function getPrimaryKey(): ?Index

/**
 * Get all unique indexes
 *
 * @return array Array of unique indexes
 */
public function getUniqueIndexes(): array
```

#### Foreign Key Management

```php
/**
 * Add a foreign key to the table
 *
 * @param ForeignKey $foreignKey The foreign key to add
 * @return self For method chaining
 * @throws SchemasException If foreign key already exists
 */
public function addForeignKey(ForeignKey $foreignKey): self

/**
 * Remove a foreign key from the table
 *
 * @param string $name The constraint name
 * @return self For method chaining
 */
public function removeForeignKey(string $name): self

/**
 * Check if a foreign key exists
 *
 * @param string $name The constraint name
 * @return bool True if foreign key exists
 */
public function hasForeignKey(string $name): bool

/**
 * Get a specific foreign key
 *
 * @param string $name The constraint name
 * @return ForeignKey|null The foreign key or null if not found
 */
public function getForeignKey(string $name): ?ForeignKey

/**
 * Get foreign keys by referenced table
 *
 * @param string $table Referenced table name
 * @return array Array of matching foreign keys
 */
public function getForeignKeysByTable(string $table): array
```

#### Relation Access
Relations (if inferred) are stored on tables; only read the relation collection directly (no add/remove mutators in minimal core docs).

### Field Class

#### Type & Basic Properties

```php
/**
 * Set the field data type
 *
 * @param string $type Data type
 * @return self For method chaining
 */
public function setType(string $type): self

/**
 * Set maximum length for the field
 *
 * @param int $length Maximum length
 * @return self For method chaining
 */
public function setLength(int $length): self

/**
 * Set if field allows NULL values
 *
 * @param bool $nullable True if nullable
 * @return self For method chaining
 */
public function setNullable(bool $nullable): self

/**
 * Set default value for the field
 *
 * @param mixed $default Default value
 * @return self For method chaining
 */
public function setDefault(mixed $default): self

/**
 * Set if field is auto increment
 *
 * @param bool $autoIncrement True if auto increment
 * @return self For method chaining
 */
public function setAutoIncrement(bool $autoIncrement): self

/**
 * Set if field is primary key
 *
 * @param bool $primaryKey True if primary key
 * @return self For method chaining
 */
public function setPrimaryKey(bool $primaryKey): self

/**
 * Set field comment
 *
 * @param string $comment Field comment
 * @return self For method chaining
 */
public function setComment(string $comment): self

/**
 * Set if numeric field is unsigned
 *
 * @param bool $unsigned True if unsigned
 * @return self For method chaining
 */
public function setUnsigned(bool $unsigned): self

/**
 * Set precision for decimal fields
 *
 * @param int $precision Precision value
 * @return self For method chaining
 */
public function setPrecision(int $precision): self

/**
 * Set scale for decimal fields
 *
 * @param int $scale Scale value
 * @return self For method chaining
 */
public function setScale(int $scale): self
```

#### Type Checking

```php
/**
 * Check if field is numeric type
 *
 * @return bool True if numeric
 */
public function isNumeric(): bool

/**
 * Check if field is string type
 *
 * @return bool True if string
 */
public function isString(): bool

/**
 * Check if field is date/time type
 *
 * @return bool True if date/time
 */
public function isDate(): bool

/**
 * Check if field is boolean type
 *
 * @return bool True if boolean
 */
public function isBoolean(): bool

/**
 * Check if field is binary type
 *
 * @return bool True if binary
 */
public function isBinary(): bool

/**
 * Check if field can be indexed
 *
 * @return bool True if indexable
 */
public function isIndexable(): bool
```

(Removed) SQL generation helpers were part of a higher-level migration/diff feature set and are no longer documented.

### Index Class

#### Field Management

```php
/**
 * Add a field to the index
 *
 * @param string $field Field name
 * @param int|null $length Optional field length
 * @return self For method chaining
 */
public function addField(string $field, ?int $length = null): self

/**
 * Remove a field from the index
 *
 * @param string $field Field name
 * @return self For method chaining
 */
public function removeField(string $field): self

/**
 * Check if field is in the index
 *
 * @param string $field Field name
 * @return bool True if field is indexed
 */
public function hasField(string $field): bool

/**
 * Get field position in index
 *
 * @param string $field Field name
 * @return int|null Position or null if not found
 */
public function getFieldPosition(string $field): ?int

/**
 * Set field length for partial indexing
 *
 * @param string $field Field name
 * @param int $length Index length
 * @return self For method chaining
 */
public function setFieldLength(string $field, int $length): self
```

#### Index Properties (Core Subset)

```php
/**
 * Set index type
 *
 * @param string $type Index type (PRIMARY, UNIQUE, INDEX, FULLTEXT)
 * @return self For method chaining
 */
public function setType(string $type): self

/**
 * Set if index is unique
 *
 * @param bool $unique True if unique
 * @return self For method chaining
 */
public function setUnique(bool $unique): self

/**
 * Set index method
 *
 * @param string $method Index method (BTREE, HASH, etc.)
 * @return self For method chaining
 */
public function setMethod(string $method): self

/**
 * Set index comment
 *
 * @param string $comment Index comment
 * @return self For method chaining
 */
public function setComment(string $comment): self
```

#### Index Type Checking

```php
/**
 * Check if index is primary key
 *
 * @return bool True if primary key
 */
public function isPrimary(): bool

/**
 * Check if index is unique
 *
 * @return bool True if unique
 */
public function isUnique(): bool

/**
 * Check if index is fulltext
 *
 * @return bool True if fulltext
 */
public function isFulltext(): bool

/**
 * Check if index is spatial
 *
 * @return bool True if spatial
 */
public function isSpatial(): bool

/**
 * Check if index is composite (multi-column)
 *
 * @return bool True if composite
 */
public function isComposite(): bool
```

### ForeignKey Class

#### Configuration

```php
/**
 * Set constraint name
 *
 * @param string $name Constraint name
 * @return self For method chaining
 */
public function setConstraintName(string $name): self

/**
 * Set local column
 *
 * @param string $column Local column name
 * @return self For method chaining
 */
public function setColumnName(string $column): self

/**
 * Set foreign table
 *
 * @param string $table Foreign table name
 * @return self For method chaining
 */
public function setForeignTableName(string $table): self

/**
 * Set foreign column
 *
 * @param string $column Foreign column name
 * @return self For method chaining
 */
public function setForeignColumnName(string $column): self

/**
 * Set ON DELETE action
 *
 * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
 * @return self For method chaining
 */
public function setOnDelete(string $action): self

/**
 * Set ON UPDATE action
 *
 * @param string $action Action (CASCADE, SET NULL, RESTRICT, NO ACTION)
 * @return self For method chaining
 */
public function setOnUpdate(string $action): self
```

Validation helper methods were removed (schema objects are trusted structural descriptions). Self-referencing detection logic not exposed as public helpers anymore.

## Reader Methods

### BaseReader

```php
/**
 * Check if reader is ready
 *
 * @return bool True if ready
 */
public function ready(): bool

/**
 * Fetch specific tables
 *
 * @param string|array $tables Table name(s)
 * @return self For method chaining
 */
public function fetch($tables): self

/**
 * Fetch all available tables
 *
 * @return self For method chaining
 */
public function fetchAll(): self

/**
 * Get loaded tables
 *
 * @return Structures\Mergeable|null Collection of tables
 */
public function getTables(): ?Structures\Mergeable

/**
 * Count loaded tables
 *
 * @return int Number of tables
 */
public function count(): int
```

### Cache Reader / Archiver Notes
Cache prefix/statistics/invalidation & TTL adjustments beyond initial config were removed. Cache usage is now: archive once, later read.

## Drafter Methods (DatabaseHandler)

```php
/**
 * Set table name filter
 *
 * @param array $tables Table names to include
 * @return self For method chaining
 */
public function setTableFilter(array $tables): self

/**
 * Set table prefix to ignore
 *
 * @param string $prefix Table prefix
 * @return self For method chaining
 */
public function setIgnorePrefix(string $prefix): self

Relation detection tuning, db info, and table statistics interfaces were removed; the drafter just drafts.

## Archiver Methods (Cache / Json / Xml)
Public surface is effectively: `archive(Structures\Schema $schema): bool` and `retrieve(): ?Structures\Schema`.

## Utility Methods

### BaseHandler
Explicit error collection helpers were internalized; consumers should rely on exceptions (unless `silent` suppresses). No public error API documented.

### Mergeable

```php
/**
 * Merge another object into this one
 *
 * @param object $object Object to merge
 * @return self For method chaining
 */
public function merge(object $object): self

/**
 * Convert to array
 *
 * @return array Array representation
 */
public function toArray(): array

/**
 * Convert to JSON
 *
 * @param int $flags JSON flags
 * @return string JSON representation
 */
public function toJson(int $flags = 0): string

/**
 * Create from array
 *
 * @param array $data Array data
 * @return static New instance
 */
public static function fromArray(array $data): static

/**
 * Create from JSON
 *
 * @param string $json JSON string
 * @return static New instance
 */
public static function fromJson(string $json): static
```

This minimal list reflects the current supported public surface. Anything else you find in source is internal and may change without notice.
