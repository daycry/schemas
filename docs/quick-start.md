# Quick Start Guide

This guide introduces the current minimal core of the Daycry Schemas library: drafting a schema, archiving it, and reading it back via fluent readers. Removed legacy subsystems (validation, performance scoring, intelligent cache manager, advanced relation detection, extended logging) are intentionally not referenced.

## Basic Usage

### Reading Your Database Schema

The most common use case is reading your database schema:

```php
<?php

use Daycry\Schemas\Schemas;

// Create a new instance
$schemas = new Schemas();

// Read the complete schema from your default database
$schema = $schemas->get();

// Display basic information
echo "Database contains " . count($schema->tables) . " tables\n";

// Iterate through tables
foreach ($schema->tables as $tableName => $table) {
    echo "Table: {$table->name}\n";
    echo "  Fields: " . count($table->fields) . "\n";
    echo "  Indexes: " . count($table->indexes) . "\n";
    echo "  Foreign Keys: " . count($table->foreignKeys) . "\n\n";
}
```

### Examining Table Structure

Get detailed information about table fields:

```php
// Get a specific table
$usersTable = $schema->tables->users;

if ($usersTable) {
    echo "Table: {$usersTable->name}\n";
    
    // Examine fields
    foreach ($usersTable->fields as $field) {
        echo "  Field: {$field->name}\n";
        echo "    Type: {$field->type}\n";
        echo "    Length: {$field->max_length}\n";
        echo "    Nullable: " . ($field->nullable ? 'Yes' : 'No') . "\n";
        echo "    Default: {$field->default}\n";
        echo "    Auto Increment: " . ($field->auto_increment ? 'Yes' : 'No') . "\n\n";
    }
    
    // Examine indexes
    foreach ($usersTable->indexes as $index) {
        echo "  Index: {$index->name}\n";
        echo "    Type: {$index->type}\n";
        echo "    Fields: " . implode(', ', $index->fields) . "\n";
        echo "    Unique: " . ($index->unique ? 'Yes' : 'No') . "\n\n";
    }
    
    // Examine foreign keys
    foreach ($usersTable->foreignKeys as $fk) {
        echo "  Foreign Key: {$fk->constraint_name}\n";
        echo "    Column: {$fk->column_name}\n";
        echo "    References: {$fk->foreign_table_name}.{$fk->foreign_column_name}\n";
        echo "    On Delete: {$fk->on_delete}\n";
        echo "    On Update: {$fk->on_update}\n\n";
    }
}
```

### Working with Multiple Databases

Connect to different databases:

```php
use Daycry\Schemas\Schemas;

// Use default database
$defaultSchemas = new Schemas();
$defaultSchema = $defaultSchemas->get();

// Use specific database group
$testSchemas = new Schemas(['database' => 'tests']);
$testSchema = $testSchemas->get();

// Compare schemas
echo "Default DB tables: " . count($defaultSchema->tables) . "\n";
echo "Test DB tables: " . count($testSchema->tables) . "\n";
```

## Export / Import (Basic)

Export your schema to different formats:

```php
use Daycry\Schemas\Archiver\Handlers\JsonHandler;
use Daycry\Schemas\Archiver\Handlers\XmlHandler;

$schemas = new Schemas();
$schema = $schemas->get();

// Export to JSON
$jsonHandler = new JsonHandler(null, 'schema.json');
if ($jsonHandler->archive($schema)) {
    echo "Schema exported to JSON successfully.\n";
}

// Export to XML
$xmlHandler = new XmlHandler(null, 'schema.xml');
if ($xmlHandler->archive($schema)) {
    echo "Schema exported to XML successfully.\n";
}

// Export to string for API responses
$jsonString = $jsonHandler->export($schema);
$xmlString = $xmlHandler->export($schema);

// Later, import from files
$importedFromJson = $jsonHandler->load();
$importedFromXml = $xmlHandler->load();

// Or import from strings
$schemaFromJson = $jsonHandler->import($jsonString);
$schemaFromXml = $xmlHandler->import($xmlString);
```

## Database Objects (Optional)

If the `DatabaseObjectHandler` exists in your current build you can load views/procedures/triggers similarly; otherwise skip this section.

<!-- Logging and metrics section removed along with SchemaLogger. Applications may integrate their own PSR-3 logging around operations if needed. -->

## Error Handling

Handle errors gracefully using `SchemasException`:

```php
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Exceptions\SchemasException;

try {
    $schemas = new Schemas();
    $schema = $schemas->get();
    
    // Process schema...
    
} catch (SchemasException $e) {
    // Handle library-specific errors
    echo "Schema Error: " . $e->getMessage();
    
    // Get additional error context
    if (method_exists($e, 'getContext')) {
        $context = $e->getContext();
        echo "Error Context: " . json_encode($context);
    }
    
} catch (\DatabaseException $e) {
    // Handle database connection errors
    echo "Database Error: " . $e->getMessage();
    
} catch (\Exception $e) {
    // Handle any other errors
    echo "Unexpected Error: " . $e->getMessage();
}

// Silent mode (collect errors instead of throwing)
$schemas = new Schemas(['silent' => true]);
$schema = $schemas->get();
foreach ($schemas->getErrors() as $error) {
    echo "Silent error: {$error}\n";
}
```

## Configuration Example

Minimal configuration example:

```php
// app/Config/Schemas.php
<?php

namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public string $defaultGroup = 'default';
    
    public array $ignoredTables = [
        'migrations',
        'ci_sessions',
        'cache_items',
        'logs',
    ];
    
    public bool $silent = false;

    // Basic cache configuration (only if CacheArchiver / CacheHandler used)
    public array $cache = [
        'enabled' => true,
        'handler' => 'file',
        'ttl'      => 3600,
        'prefix'   => 'schemas_',
    ];
}
```

## Next Steps

Now that you understand the basics:

1. Explore handler docs (if present) for cache / database drafting specifics.
2. Review examples for round‑trip and fluent usage.
3. Read the API reference for structure details.

## Common Patterns

### Schema Comparison
```php
// Compare two database schemas
$prodSchema = (new Schemas(['database' => 'production']))->get();
$devSchema = (new Schemas(['database' => 'development']))->get();

// Find missing tables
foreach ($prodSchema->tables as $tableName => $table) {
    if (!property_exists($devSchema->tables, $tableName)) {
        echo "Table {$tableName} missing in development\n";
    }
}
```

This quick start guide covers the essential patterns you'll use most often with the trimmed core.
