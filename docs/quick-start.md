# Quick Start Guide

This guide will help you get started with the Daycry Schemas library quickly. You'll learn the basic usage patterns and see practical examples.

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

## Schema Validation

Validate your schema for potential issues:

```php
use Daycry\Schemas\SchemaValidator;

$schemas = new Schemas();
$schema = $schemas->get();

// Create validator
$validator = new SchemaValidator();

// Validate the schema
$result = $validator->validateSchema($schema);

if ($result->isValid()) {
    echo "Schema is valid!\n";
} else {
    echo "Schema validation failed:\n";
    foreach ($result->getErrors() as $error) {
        echo "  - {$error}\n";
    }
}

// Get warnings (non-critical issues)
$warnings = $result->getWarnings();
if (!empty($warnings)) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  - {$warning}\n";
    }
}
```

## Performance Analysis

Analyze your schema for performance issues:

```php
use Daycry\Schemas\PerformanceAnalyzer;

$schemas = new Schemas();
$schema = $schemas->get();

// Create analyzer
$analyzer = new PerformanceAnalyzer();

// Analyze performance
$analysis = $analyzer->analyzeSchema($schema);

echo "Performance Score: {$analysis['score']}/100\n\n";

// Show recommendations
echo "Recommendations:\n";
foreach ($analysis['recommendations'] as $recommendation) {
    echo "  Priority: {$recommendation['priority']}\n";
    echo "  Message: {$recommendation['message']}\n";
    echo "  Table: {$recommendation['table']}\n\n";
}

// Detailed analysis by table
foreach ($analysis['table_scores'] as $tableName => $score) {
    echo "Table {$tableName}: {$score}/100\n";
}
```

## Intelligent Caching

Use intelligent caching for better performance:

```php
use Daycry\Schemas\IntelligentCacheManager;
use Daycry\Schemas\Schemas;

// Set up cache manager
$cache = \Config\Services::cache();
$cacheManager = new IntelligentCacheManager($cache);

// Try to get from cache first
$cacheKey = 'main_schema';
$schema = $cacheManager->get($cacheKey);

if (!$schema) {
    echo "Loading schema from database...\n";
    
    // Load from database
    $schemas = new Schemas();
    $schema = $schemas->get();
    
    // Store in cache with tags and TTL
    $cacheManager->store($cacheKey, $schema, ['database', 'production'], 3600);
    
    echo "Schema cached for future use.\n";
} else {
    echo "Schema loaded from cache.\n";
}

// Invalidate cache when needed
// $cacheManager->invalidateByTag('database');
```

## Relationship Detection

Discover advanced relationships in your schema:

```php
use Daycry\Schemas\AdvancedRelationDetector;

$schemas = new Schemas();
$schema = $schemas->get();

// Create detector
$detector = new AdvancedRelationDetector();

// Detect relationships
$relations = $detector->detectAdvancedRelations($schema);

// Show polymorphic relationships
echo "Polymorphic Relationships:\n";
foreach ($relations['polymorphic'] as $relation) {
    echo "  Table: {$relation['table']}\n";
    echo "  Type Field: {$relation['type_field']}\n";
    echo "  ID Field: {$relation['id_field']}\n\n";
}

// Show hierarchical structures
echo "Hierarchical Structures:\n";
foreach ($relations['hierarchical'] as $relation) {
    echo "  Table: {$relation['table']}\n";
    echo "  Type: {$relation['type']}\n";
    echo "  Parent Field: {$relation['parent_field']}\n\n";
}

// Show many-to-many relationships
echo "Many-to-Many Relationships:\n";
foreach ($relations['many_to_many'] as $relation) {
    echo "  Pivot Table: {$relation['pivot_table']}\n";
    echo "  Left Table: {$relation['left_table']}\n";
    echo "  Right Table: {$relation['right_table']}\n\n";
}
```

## Export and Import

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

## Database Objects (Views, Procedures, Triggers)

Work with database views, stored procedures, and triggers:

```php
use Daycry\Schemas\Reader\Handlers\DatabaseObjectHandler;

// Create handler
$objectHandler = new DatabaseObjectHandler();

// Fetch all database objects
$objectHandler->fetchAll();

// Examine views
if ($objectHandler->views) {
    echo "Database Views:\n";
    foreach ($objectHandler->views as $view) {
        echo "  View: {$view->name}\n";
        echo "    Updatable: " . ($view->updatable ? 'Yes' : 'No') . "\n";
        echo "    Dependencies: " . implode(', ', $view->dependencies) . "\n\n";
    }
}

// Examine stored procedures
if ($objectHandler->procedures) {
    echo "Stored Procedures:\n";
    foreach ($objectHandler->procedures as $procedure) {
        echo "  Procedure: {$procedure->name}\n";
        echo "    Type: {$procedure->type}\n";
        echo "    Language: {$procedure->language}\n";
        echo "    Deterministic: " . ($procedure->deterministic ? 'Yes' : 'No') . "\n\n";
    }
}

// Examine triggers
if ($objectHandler->triggers) {
    echo "Triggers:\n";
    foreach ($objectHandler->triggers as $trigger) {
        echo "  Trigger: {$trigger->name}\n";
        echo "    Table: {$trigger->table}\n";
        echo "    Timing: {$trigger->timing}\n";
        echo "    Events: " . implode(', ', $trigger->events) . "\n\n";
    }
}
```

## Logging and Metrics

Monitor operations with detailed logging:

```php
use Daycry\Schemas\SchemaLogger;

// Create logger (you can pass any PSR-3 compatible logger)
$logger = new SchemaLogger(log_message(...));

// Log operations
$sessionId = $logger->logOperationStart('schema_analysis', [
    'database' => 'production',
    'tables' => 50
]);

// Perform your operations...
$schemas = new Schemas();
$schema = $schemas->get();

// End logging
$logger->logOperationEnd($sessionId, true, [
    'tables_loaded' => count($schema->tables),
    'memory_used' => memory_get_peak_usage(true)
]);

// Get performance metrics
$metrics = $logger->getMetrics();
echo "Total Operations: {$metrics['total_operations']}\n";
echo "Average Duration: {$metrics['avg_duration']}ms\n";
echo "Success Rate: " . ($metrics['success_rate'] * 100) . "%\n";
echo "Peak Memory: " . round($metrics['peak_memory'] / 1024 / 1024, 2) . "MB\n";
```

## Error Handling

Handle errors gracefully:

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

// Alternative: Silent mode
$schemas = new Schemas(['silent' => true]);
$schema = $schemas->get();

if (!empty($schemas->getErrors())) {
    echo "Errors encountered:\n";
    foreach ($schemas->getErrors() as $error) {
        echo "  - {$error}\n";
    }
}
```

## Configuration in Practice

Here's a practical configuration example:

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
    
    // Enable all advanced features
    public bool $enableValidation = true;
    public bool $enablePerformanceAnalysis = true;
    public bool $enableIntelligentCache = true;
    
    public array $cache = [
        'enabled' => true,
        'handler' => 'redis', // or 'file', 'memcached'
        'ttl' => 3600,
        'prefix' => 'schemas_',
    ];
    
    public array $logging = [
        'enabled' => true,
        'level' => 'info',
        'handler' => 'file',
        'path' => WRITEPATH . 'logs/schemas/',
    ];
    
    public array $performance = [
        'enabled' => true,
        'threshold_score' => 70,
        'max_recommendations' => 15,
        'analyze_indexes' => true,
        'analyze_foreign_keys' => true,
    ];
}
```

## Next Steps

Now that you understand the basics:

1. Explore [Advanced Features](../advanced/) for more sophisticated usage
2. Check out [Handler Documentation](../handlers/) for specific handlers
3. Review [Examples](../examples/) for real-world scenarios
4. Read the [API Reference](../api/) for detailed class documentation

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

### Automated Health Checks
```php
// Create automated schema health check
function checkSchemaHealth() {
    $schemas = new Schemas();
    $schema = $schemas->get();
    
    $validator = new SchemaValidator();
    $result = $validator->validateSchema($schema);
    
    $analyzer = new PerformanceAnalyzer();
    $analysis = $analyzer->analyzeSchema($schema);
    
    return [
        'valid' => $result->isValid(),
        'errors' => $result->getErrors(),
        'warnings' => $result->getWarnings(),
        'performance_score' => $analysis['score'],
        'recommendations' => $analysis['recommendations'],
    ];
}

// Run health check
$health = checkSchemaHealth();
if (!$health['valid'] || $health['performance_score'] < 70) {
    // Alert administrators
    // Log issues
    // Take corrective action
}
```

This quick start guide covers the essential patterns you'll use most often. The library is designed to be intuitive and follows CodeIgniter conventions, so you should feel comfortable using it right away.
