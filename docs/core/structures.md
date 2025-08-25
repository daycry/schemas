# Schema Structures

This document describes the core data structures used by the Daycry Schemas library to represent database schemas and their components.

## Overview

The library uses a hierarchical structure to represent database schemas:

```
Schema
├── Tables (Mergeable)
    ├── Table
        ├── Fields (Mergeable)
        │   └── Field
        ├── Indexes (Mergeable)
        │   └── Index
        ├── Foreign Keys (Mergeable)
        │   └── ForeignKey
        └── Relations (Mergeable)
            └── Relation
```

## Core Classes

### Schema

The top-level container for an entire database schema.

```php
namespace Daycry\Schemas\Structures;

class Schema extends Mergeable
{
    /**
     * Collection of tables in the schema
     * @var Mergeable|null
     */
    public $tables;

    /**
     * Schema metadata
     * @var array
     */
    public $metadata = [];

    /**
     * Schema version
     * @var string|null
     */
    public $version;

    /**
     * Creation timestamp
     * @var string|null
     */
    public $created_at;
}
```

**Usage:**
```php
$schema = new Schema();
$schema->tables = new Mergeable();

// Access tables
foreach ($schema->tables as $tableName => $table) {
    echo "Table: {$tableName}\n";
}
```

### Table

Represents a database table with all its components.

```php
class Table extends Mergeable
{
    /**
     * Table name
     * @var string|null
     */
    public $name;

    /**
     * Table comment/description
     * @var string|null
     */
    public $comment;

    /**
     * Storage engine (MySQL)
     * @var string|null
     */
    public $engine;

    /**
     * Table collation
     * @var string|null
     */
    public $collation;

    /**
     * Character set
     * @var string|null
     */
    public $charset;

    /**
     * Collection of fields
     * @var Mergeable|null
     */
    public $fields;

    /**
     * Collection of indexes
     * @var Mergeable|null
     */
    public $indexes;

    /**
     * Collection of foreign keys
     * @var Mergeable|null
     */
    public $foreignKeys;

    /**
     * Collection of relations
     * @var Mergeable|null
     */
    public $relations;

    /**
     * Table options/attributes
     * @var array
     */
    public $options = [];
}
```

**Usage:**
```php
$table = new Table('users');
$table->comment = 'User accounts table';
$table->engine = 'InnoDB';
$table->collation = 'utf8mb4_unicode_ci';

// Initialize collections
$table->fields = new Mergeable();
$table->indexes = new Mergeable();
$table->foreignKeys = new Mergeable();
$table->relations = new Mergeable();
```

### Field

Represents a table column/field.

```php
class Field extends Mergeable
{
    /**
     * Field name
     * @var string|null
     */
    public $name;

    /**
     * Data type (INT, VARCHAR, TEXT, etc.)
     * @var string|null
     */
    public $type;

    /**
     * Maximum length/size
     * @var int|null
     */
    public $max_length;

    /**
     * Whether field allows NULL values
     * @var bool
     */
    public $nullable = false;

    /**
     * Default value
     * @var mixed
     */
    public $default;

    /**
     * Whether field is auto-incrementing
     * @var bool
     */
    public $auto_increment = false;

    /**
     * Whether field is part of primary key
     * @var bool
     */
    public $primary_key = false;

    /**
     * Field comment/description
     * @var string|null
     */
    public $comment;

    /**
     * Unsigned (for numeric types)
     * @var bool
     */
    public $unsigned = false;

    /**
     * Precision for decimal types
     * @var int|null
     */
    public $precision;

    /**
     * Scale for decimal types
     * @var int|null
     */
    public $scale;

    /**
     * Additional field attributes
     * @var array
     */
    public $attributes = [];
}
```

**Usage:**
```php
// Primary key field
$idField = new Field('id');
$idField->type = 'INT';
$idField->max_length = 11;
$idField->auto_increment = true;
$idField->primary_key = true;
$idField->unsigned = true;

// String field
$nameField = new Field('name');
$nameField->type = 'VARCHAR';
$nameField->max_length = 255;
$nameField->nullable = false;
$nameField->comment = 'User full name';

// Decimal field
$priceField = new Field('price');
$priceField->type = 'DECIMAL';
$priceField->precision = 10;
$priceField->scale = 2;
$priceField->default = '0.00';
```

### Index

Represents a database index.

```php
class Index extends Mergeable
{
    /**
     * Index name
     * @var string|null
     */
    public $name;

    /**
     * Fields included in the index
     * @var array
     */
    public $fields = [];

    /**
     * Index type (INDEX, PRIMARY, UNIQUE, FULLTEXT, etc.)
     * @var string|null
     */
    public $type;

    /**
     * Whether index enforces uniqueness
     * @var bool
     */
    public $unique = false;

    /**
     * Index method (BTREE, HASH, etc.)
     * @var string|null
     */
    public $method;

    /**
     * Index comment
     * @var string|null
     */
    public $comment;

    /**
     * Index length specifications for fields
     * @var array
     */
    public $lengths = [];
}
```

**Usage:**
```php
// Primary key index
$primaryIndex = new Index('PRIMARY');
$primaryIndex->type = 'PRIMARY';
$primaryIndex->fields = ['id'];

// Unique index
$emailIndex = new Index('idx_email_unique');
$emailIndex->fields = ['email'];
$emailIndex->unique = true;

// Composite index
$nameIndex = new Index('idx_name_created');
$nameIndex->fields = ['last_name', 'first_name', 'created_at'];
$nameIndex->lengths = [null, null, null]; // No length restrictions

// Partial index with lengths
$textIndex = new Index('idx_description');
$textIndex->fields = ['description'];
$textIndex->lengths = [100]; // Index first 100 characters
```

### ForeignKey

Represents a foreign key constraint.

```php
class ForeignKey extends Mergeable
{
    /**
     * Constraint name
     * @var string|null
     */
    public $constraint_name;

    /**
     * Local column name
     * @var string|null
     */
    public $column_name;

    /**
     * Referenced table name
     * @var string|null
     */
    public $foreign_table_name;

    /**
     * Referenced column name
     * @var string|null
     */
    public $foreign_column_name;

    /**
     * Action on DELETE (CASCADE, RESTRICT, SET NULL, etc.)
     * @var string|null
     */
    public $on_delete;

    /**
     * Action on UPDATE (CASCADE, RESTRICT, SET NULL, etc.)
     * @var string|null
     */
    public $on_update;

    /**
     * Whether constraint is deferrable
     * @var bool
     */
    public $deferrable = false;

    /**
     * Initial deferred state
     * @var bool
     */
    public $initially_deferred = false;
}
```

**Usage:**
```php
$foreignKey = new ForeignKey();
$foreignKey->constraint_name = 'fk_posts_user_id';
$foreignKey->column_name = 'user_id';
$foreignKey->foreign_table_name = 'users';
$foreignKey->foreign_column_name = 'id';
$foreignKey->on_delete = 'CASCADE';
$foreignKey->on_update = 'RESTRICT';
```

### Relation

Represents a logical relationship between tables.

```php
class Relation extends Mergeable
{
    /**
     * Relation type (hasOne, hasMany, belongsTo, manyToMany, etc.)
     * @var string|null
     */
    public $type;

    /**
     * Related table name
     * @var string|null
     */
    public $table;

    /**
     * Pivot table for many-to-many relationships
     * @var string|null
     */
    public $pivot;

    /**
     * Local field/key
     * @var string|null
     */
    public $field;

    /**
     * Foreign field/key
     * @var string|null
     */
    public $foreign_field;

    /**
     * Additional relation attributes
     * @var array
     */
    public $attributes = [];
}
```

**Usage:**
```php
// One-to-many relation
$postsRelation = new Relation();
$postsRelation->type = 'hasMany';
$postsRelation->table = 'posts';
$postsRelation->field = 'id';
$postsRelation->foreign_field = 'user_id';

// Many-to-many relation
$rolesRelation = new Relation();
$rolesRelation->type = 'manyToMany';
$rolesRelation->table = 'roles';
$rolesRelation->pivot = 'user_roles';
$rolesRelation->field = 'id';
$rolesRelation->foreign_field = 'id';
```

## Extended Structures

### View

Represents a database view.

```php
class View extends Mergeable
{
    /**
     * View name
     * @var string|null
     */
    public $name;

    /**
     * View definition/SQL
     * @var string|null
     */
    public $definition;

    /**
     * Whether view is updatable
     * @var bool
     */
    public $updatable = false;

    /**
     * Tables/views this view depends on
     * @var array
     */
    public $dependencies = [];

    /**
     * Security type (DEFINER, INVOKER)
     * @var string|null
     */
    public $security;

    /**
     * View comment
     * @var string|null
     */
    public $comment;
}
```

### Procedure

Represents a stored procedure or function.

```php
class Procedure extends Mergeable
{
    /**
     * Procedure name
     * @var string|null
     */
    public $name;

    /**
     * Procedure type (PROCEDURE, FUNCTION)
     * @var string
     */
    public $type = 'PROCEDURE';

    /**
     * Procedure definition/SQL
     * @var string|null
     */
    public $definition;

    /**
     * Input/output parameters
     * @var array
     */
    public $parameters = [];

    /**
     * Return type (for functions)
     * @var string|null
     */
    public $returnType;

    /**
     * Security type (DEFINER, INVOKER)
     * @var string|null
     */
    public $security;

    /**
     * Language (SQL, PLpgSQL, etc.)
     * @var string
     */
    public $language = 'SQL';

    /**
     * Whether procedure is deterministic
     * @var bool
     */
    public $deterministic = false;

    /**
     * Data access characteristics
     * @var string|null
     */
    public $dataAccess;

    /**
     * Comment/description
     * @var string|null
     */
    public $comment;
}
```

### Trigger

Represents a database trigger.

```php
class Trigger extends Mergeable
{
    /**
     * Trigger name
     * @var string|null
     */
    public $name;

    /**
     * Table the trigger is on
     * @var string|null
     */
    public $table;

    /**
     * Trigger timing (BEFORE, AFTER, INSTEAD OF)
     * @var string|null
     */
    public $timing;

    /**
     * Trigger events (INSERT, UPDATE, DELETE)
     * @var array
     */
    public $events = [];

    /**
     * Trigger definition/SQL
     * @var string|null
     */
    public $definition;

    /**
     * Trigger order/position
     * @var int|null
     */
    public $order;

    /**
     * Trigger condition (WHEN clause)
     * @var string|null
     */
    public $condition;

    /**
     * Whether trigger is enabled
     * @var bool
     */
    public $enabled = true;

    /**
     * Trigger comment
     * @var string|null
     */
    public $comment;
}
```

## Mergeable Base Class

All structures extend the `Mergeable` class, which provides dynamic property management:

```php
abstract class Mergeable
{
    /**
     * Merge another object into this one
     */
    public function merge(object $object): self;

    /**
     * Convert to array
     */
    public function toArray(): array;

    /**
     * Check if property exists
     */
    public function __isset(string $name): bool;

    /**
     * Get property value
     */
    public function __get(string $name);

    /**
     * Set property value
     */
    public function __set(string $name, $value): void;
}
```

**Benefits of Mergeable:**
- Dynamic property addition
- Easy merging of schemas
- Consistent interface across all structures
- Support for iteration and counting

## Working with Structures

### Creating a Complete Table

```php
use Daycry\Schemas\Structures\{Table, Field, Index, ForeignKey, Mergeable};

// Create table
$table = new Table('posts');
$table->comment = 'Blog posts table';
$table->engine = 'InnoDB';

// Initialize collections
$table->fields = new Mergeable();
$table->indexes = new Mergeable();
$table->foreignKeys = new Mergeable();

// Add fields
$idField = new Field('id');
$idField->type = 'INT';
$idField->auto_increment = true;
$idField->primary_key = true;
$table->fields->id = $idField;

$titleField = new Field('title');
$titleField->type = 'VARCHAR';
$titleField->max_length = 255;
$titleField->nullable = false;
$table->fields->title = $titleField;

$userIdField = new Field('user_id');
$userIdField->type = 'INT';
$userIdField->nullable = false;
$table->fields->user_id = $userIdField;

// Add indexes
$primaryIndex = new Index('PRIMARY');
$primaryIndex->type = 'PRIMARY';
$primaryIndex->fields = ['id'];
$table->indexes->PRIMARY = $primaryIndex;

$titleIndex = new Index('idx_title');
$titleIndex->fields = ['title'];
$table->indexes->idx_title = $titleIndex;

// Add foreign key
$userFk = new ForeignKey();
$userFk->constraint_name = 'fk_posts_user_id';
$userFk->column_name = 'user_id';
$userFk->foreign_table_name = 'users';
$userFk->foreign_column_name = 'id';
$userFk->on_delete = 'CASCADE';
$table->foreignKeys->fk_posts_user_id = $userFk;
```

### Merging Schemas

```php
// Merge two schemas
$schema1 = new Schema();
$schema1->tables = new Mergeable();
$schema1->tables->users = $usersTable;

$schema2 = new Schema();
$schema2->tables = new Mergeable();
$schema2->tables->posts = $postsTable;

// Merge schema2 into schema1
$schema1->merge($schema2);

// Now schema1 contains both users and posts tables
```

### Converting to Arrays

```php
// Convert structures to arrays for serialization
$tableArray = $table->toArray();
$schemaArray = $schema->toArray();

// Use in JSON responses
header('Content-Type: application/json');
echo json_encode($schemaArray);
```

## Data Type Mapping

The library handles different database types consistently:

### MySQL Types
- `TINYINT`, `SMALLINT`, `MEDIUMINT`, `INT`, `BIGINT`
- `DECIMAL`, `NUMERIC`, `FLOAT`, `DOUBLE`
- `CHAR`, `VARCHAR`, `TEXT`, `MEDIUMTEXT`, `LONGTEXT`
- `DATE`, `TIME`, `DATETIME`, `TIMESTAMP`, `YEAR`
- `BINARY`, `VARBINARY`, `BLOB`, `MEDIUMBLOB`, `LONGBLOB`
- `JSON`, `GEOMETRY`, `POINT`, `LINESTRING`, `POLYGON`

### PostgreSQL Types
- `SMALLINT`, `INTEGER`, `BIGINT`, `SERIAL`, `BIGSERIAL`
- `DECIMAL`, `NUMERIC`, `REAL`, `DOUBLE PRECISION`
- `CHAR`, `VARCHAR`, `TEXT`
- `DATE`, `TIME`, `TIMESTAMP`, `TIMESTAMPTZ`, `INTERVAL`
- `BOOLEAN`, `BYTEA`, `UUID`, `JSON`, `JSONB`
- `ARRAY`, `HSTORE`, `TSVECTOR`

### SQLite Types
- `INTEGER`, `REAL`, `TEXT`, `BLOB`, `NUMERIC`

## Best Practices

### 1. Initialize Collections
Always initialize Mergeable collections before use:
```php
$table->fields = new Mergeable();
$table->indexes = new Mergeable();
```

### 2. Use Consistent Naming
Follow consistent naming conventions:
```php
// Good
$table->name = 'user_profiles';
$field->name = 'created_at';
$index->name = 'idx_email_unique';

// Avoid
$table->name = 'UserProfiles';
$field->name = 'CreatedAt';
```

### 3. Set Appropriate Defaults
Always set appropriate default values:
```php
$field->nullable = false; // Explicit
$field->auto_increment = false; // Explicit
$index->unique = false; // Explicit
```

### 4. Document Complex Structures
Add comments for complex relationships:
```php
$table->comment = 'User profiles with extended information';
$field->comment = 'Timestamp of last login attempt';
$index->comment = 'Composite index for user search optimization';
```

### 5. Validate Data
Always validate structure data:
```php
if (empty($field->name)) {
    throw new InvalidArgumentException('Field name cannot be empty');
}

if (!in_array($field->type, $validTypes)) {
    throw new InvalidArgumentException("Invalid field type: {$field->type}");
}
```

The structure system provides a flexible and powerful way to represent database schemas programmatically while maintaining type safety and consistency across different database systems.
