# API Reference - Methods

This document provides detailed information about all public methods available in the Daycry Schemas library.

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

#### Schema Metadata

```php
/**
 * Set schema version
 *
 * @param string $version Version string
 * @return self For method chaining
 */
public function setVersion(string $version): self

/**
 * Get schema version
 *
 * @return string|null Current version
 */
public function getVersion(): ?string

/**
 * Set metadata for the schema
 *
 * @param array $metadata Metadata array
 * @return self For method chaining
 */
public function setMetadata(array $metadata): self

/**
 * Add metadata item
 *
 * @param string $key Metadata key
 * @param mixed $value Metadata value
 * @return self For method chaining
 */
public function addMetadata(string $key, mixed $value): self
```

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

#### Relation Management

```php
/**
 * Add a relation to the table
 *
 * @param Relation $relation The relation to add
 * @return self For method chaining
 */
public function addRelation(Relation $relation): self

/**
 * Remove a relation from the table
 *
 * @param string $type Relation type
 * @param string $table Related table
 * @return self For method chaining
 */
public function removeRelation(string $type, string $table): self

/**
 * Get relations by type
 *
 * @param string $type Relation type
 * @return array Array of matching relations
 */
public function getRelationsByType(string $type): array

/**
 * Get all outgoing relations
 *
 * @return array Array of outgoing relations
 */
public function getOutgoingRelations(): array

/**
 * Get all incoming relations
 *
 * @return array Array of incoming relations
 */
public function getIncomingRelations(): array
```

### Field Class

#### Type and Properties

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

#### SQL Generation

```php
/**
 * Get SQL definition for the field
 *
 * @param string $database Database type
 * @return string SQL definition
 */
public function getDefinition(string $database = 'mysql'): string

/**
 * Get CREATE TABLE column definition
 *
 * @param string $database Database type
 * @return string Column definition
 */
public function getColumnDefinition(string $database = 'mysql'): string

/**
 * Get ALTER TABLE definition for adding field
 *
 * @param string $database Database type
 * @return string ALTER definition
 */
public function getAlterDefinition(string $database = 'mysql'): string
```

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

#### Index Properties

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

#### Validation

```php
/**
 * Validate foreign key configuration
 *
 * @return bool True if valid
 */
public function isValid(): bool

/**
 * Get validation errors
 *
 * @return array Array of error messages
 */
public function getValidationErrors(): array

/**
 * Check if foreign key is self-referencing
 *
 * @param string $currentTable Current table name
 * @return bool True if self-referencing
 */
public function isSelfReferencing(string $currentTable): bool
```

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

### CacheHandler (Reader)

```php
/**
 * Set cache key prefix
 *
 * @param string $prefix Cache key prefix
 * @return self For method chaining
 */
public function setCachePrefix(string $prefix): self

/**
 * Check if cache is valid
 *
 * @return bool True if cache is valid
 */
public function isCacheValid(): bool

/**
 * Invalidate cache
 *
 * @return bool True if successful
 */
public function invalidateCache(): bool

/**
 * Get cache statistics
 *
 * @return array Cache statistics
 */
public function getCacheStats(): array
```

## Drafter Methods

### DatabaseHandler (Drafter)

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

/**
 * Enable/disable relation detection
 *
 * @param bool $enabled True to enable
 * @return self For method chaining
 */
public function setRelationDetection(bool $enabled): self

/**
 * Get database information
 *
 * @return array Database information
 */
public function getDatabaseInfo(): array

/**
 * Get table statistics
 *
 * @param string $table Table name
 * @return array Table statistics
 */
public function getTableStats(string $table): array
```

## Archiver Methods

### CacheArchiver

```php
/**
 * Set cache TTL
 *
 * @param int $ttl Time to live in seconds
 * @return self For method chaining
 */
public function setTtl(int $ttl): self

/**
 * Set cache tags
 *
 * @param array $tags Cache tags
 * @return self For method chaining
 */
public function setTags(array $tags): self

/**
 * Retrieve archived schema
 *
 * @return Structures\Schema|null Retrieved schema
 */
public function retrieve(): ?Structures\Schema

/**
 * Check if archived schema exists
 *
 * @return bool True if exists
 */
public function exists(): bool

/**
 * Get archive timestamp
 *
 * @return int|null Archive timestamp
 */
public function getTimestamp(): ?int
```

## Utility Methods

### BaseHandler

```php
/**
 * Get error messages
 *
 * @return array Array of error messages
 */
public function getErrors(): array

/**
 * Clear all errors
 *
 * @return self For method chaining
 */
public function clearErrors(): self

/**
 * Check if handler has errors
 *
 * @return bool True if has errors
 */
public function hasErrors(): bool

/**
 * Get last error message
 *
 * @return string|null Last error or null
 */
public function getLastError(): ?string
```

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

This comprehensive methods reference provides detailed information about all public methods available in the Daycry Schemas library, including their parameters, return types, and usage examples.
