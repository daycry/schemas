# Schema Validation

The Schema Validation system provides comprehensive integrity checking for database schemas. It can detect potential issues, inconsistencies, and violations of database best practices.

## Overview

The `SchemaValidator` class performs various validation checks on your database schema:

- **Circular Reference Detection**: Prevents infinite loops in foreign key relationships
- **Foreign Key Consistency**: Validates that foreign keys reference existing tables and columns
- **Data Type Validation**: Ensures field types are valid for the target database
- **Index Validation**: Checks index definitions and field references
- **Constraint Validation**: Validates various database constraints
- **Naming Convention Checks**: Ensures consistent naming patterns

## Basic Usage

```php
use Daycry\Schemas\SchemaValidator;
use Daycry\Schemas\Schemas;

// Load your schema
$schemas = new Schemas();
$schema = $schemas->get();

// Create validator
$validator = new SchemaValidator();

// Validate the schema
$result = $validator->validateSchema($schema);

// Check results
if ($result->isValid()) {
    echo "Schema is valid!\n";
} else {
    echo "Schema validation failed:\n";
    foreach ($result->getErrors() as $error) {
        echo "  ERROR: {$error}\n";
    }
}

// Check warnings (non-critical issues)
if (!empty($result->getWarnings())) {
    echo "Warnings:\n";
    foreach ($result->getWarnings() as $warning) {
        echo "  WARNING: {$warning}\n";
    }
}
```

## Configuration

Configure validation behavior:

```php
$validator = new SchemaValidator([
    'strictMode' => true,              // Enable strict validation
    'checkNamingConventions' => true,  // Validate naming patterns
    'allowedDataTypes' => [            // Restrict allowed data types
        'mysql' => ['INT', 'VARCHAR', 'TEXT', 'DATETIME'],
        'postgresql' => ['INTEGER', 'VARCHAR', 'TEXT', 'TIMESTAMP'],
    ],
    'maxTableNameLength' => 64,        // Maximum table name length
    'maxFieldNameLength' => 64,        // Maximum field name length
    'requirePrimaryKeys' => true,      // Require primary keys on all tables
    'requireForeignKeyIndexes' => true, // Require indexes on foreign key columns
]);
```

## Validation Types

### 1. Circular Reference Detection

Detects circular dependencies in foreign key relationships:

```php
// This would create a circular reference:
// users.company_id -> companies.id
// companies.owner_id -> users.id
// users.manager_id -> users.id (self-reference is OK)

$result = $validator->validateSchema($schema);

// Check for circular references specifically
$circularRefs = $result->getErrors('circular_reference');
foreach ($circularRefs as $error) {
    echo "Circular reference detected: {$error}\n";
}
```

**Example Error:**
```
Circular reference detected in foreign key chain: users -> companies -> users
```

### 2. Foreign Key Consistency

Validates foreign key references:

```php
// Checks that:
// - Referenced tables exist
// - Referenced columns exist
// - Data types are compatible
// - Indexes exist on foreign key columns (recommended)

$result = $validator->validateSchema($schema);

$fkErrors = $result->getErrors('foreign_key');
foreach ($fkErrors as $error) {
    echo "Foreign key error: {$error}\n";
}
```

**Example Errors:**
```
Foreign key 'fk_posts_user_id' references non-existent table 'users'
Foreign key 'fk_posts_category_id' references non-existent column 'categories.id'
Foreign key column 'posts.user_id' (INT) is incompatible with 'users.id' (VARCHAR)
```

### 3. Data Type Validation

Ensures field types are valid:

```php
// Validates:
// - Data type exists for the database system
// - Length/precision specifications are valid
// - Default values are compatible with the type

$validator = new SchemaValidator([
    'allowedDataTypes' => [
        'mysql' => [
            'INT', 'BIGINT', 'VARCHAR', 'TEXT', 'DATETIME', 
            'DECIMAL', 'FLOAT', 'BOOLEAN', 'JSON'
        ]
    ]
]);

$result = $validator->validateSchema($schema);

$typeErrors = $result->getErrors('data_type');
```

**Example Errors:**
```
Invalid data type 'INVALIDTYPE' for field 'users.status'
VARCHAR field 'users.email' missing required length specification
DECIMAL field 'products.price' has invalid precision/scale (15,3)
```

### 4. Index Validation

Validates index definitions:

```php
// Checks:
// - All indexed fields exist
// - Index types are valid
// - Composite indexes make sense
// - Primary key constraints

$result = $validator->validateSchema($schema);

$indexErrors = $result->getErrors('index');
foreach ($indexErrors as $error) {
    echo "Index error: {$error}\n";
}
```

**Example Errors:**
```
Index 'idx_user_email' references non-existent field 'users.email_address'
Primary key index missing on table 'users'
Composite index 'idx_user_profile' has too many fields (maximum 16)
```

### 5. Naming Convention Validation

Checks naming patterns:

```php
$validator = new SchemaValidator([
    'checkNamingConventions' => true,
    'namingRules' => [
        'tables' => '/^[a-z][a-z0-9_]*$/',           // snake_case
        'fields' => '/^[a-z][a-z0-9_]*$/',           // snake_case
        'indexes' => '/^(PRIMARY|idx_[a-z0-9_]+)$/', // idx_ prefix
        'foreignKeys' => '/^fk_[a-z0-9_]+$/',        // fk_ prefix
    ],
    'reservedWords' => [
        'mysql' => ['ORDER', 'GROUP', 'SELECT', 'FROM', 'WHERE'],
        'postgresql' => ['USER', 'ORDER', 'GROUP', 'SELECT'],
    ]
]);

$result = $validator->validateSchema($schema);

$namingErrors = $result->getErrors('naming');
```

**Example Errors:**
```
Table name 'UserProfiles' does not follow naming convention (use snake_case)
Field name 'firstName' does not follow naming convention
Reserved word 'order' used as table name
Index name 'email_unique' should start with 'idx_'
```

### 6. Constraint Validation

Validates various constraints:

```php
// Checks:
// - NOT NULL constraints consistency
// - DEFAULT value compatibility
// - AUTO_INCREMENT on appropriate fields
// - UNIQUE constraints

$validator = new SchemaValidator([
    'requirePrimaryKeys' => true,
    'requireForeignKeyIndexes' => true,
    'maxVarcharLength' => 65535,
    'allowNullablePrimaryKeys' => false,
]);

$result = $validator->validateSchema($schema);

$constraintErrors = $result->getErrors('constraint');
```

**Example Errors:**
```
Table 'posts' missing primary key
Primary key field 'users.id' should not be nullable
AUTO_INCREMENT field 'users.id' should be marked as primary key
Foreign key field 'posts.user_id' missing index for performance
```

## Advanced Validation

### Custom Validation Rules

Add custom validation rules:

```php
$validator = new SchemaValidator();

// Add custom validator for business rules
$validator->addCustomRule('business_logic', function($schema) {
    $errors = [];
    
    // Example: All user-related tables must have created_at field
    foreach ($schema->tables as $table) {
        if (strpos($table->name, 'user') !== false) {
            if (!property_exists($table->fields, 'created_at')) {
                $errors[] = "User table '{$table->name}' missing 'created_at' field";
            }
        }
    }
    
    return $errors;
});

$result = $validator->validateSchema($schema);
```

### Database-Specific Validation

Configure validation for specific database systems:

```php
$mysqlValidator = new SchemaValidator([
    'database' => 'mysql',
    'version' => '8.0',
    'allowedDataTypes' => [
        'mysql' => [
            'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT',
            'DECIMAL', 'FLOAT', 'DOUBLE',
            'CHAR', 'VARCHAR', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT',
            'DATE', 'TIME', 'DATETIME', 'TIMESTAMP', 'YEAR',
            'JSON', 'GEOMETRY'
        ]
    ],
    'engineSpecific' => [
        'allowedEngines' => ['InnoDB', 'MyISAM'],
        'defaultEngine' => 'InnoDB',
        'requireInnoDBForForeignKeys' => true,
    ]
]);

$postgresValidator = new SchemaValidator([
    'database' => 'postgresql',
    'version' => '13',
    'allowedDataTypes' => [
        'postgresql' => [
            'SMALLINT', 'INTEGER', 'BIGINT', 'SERIAL', 'BIGSERIAL',
            'DECIMAL', 'NUMERIC', 'REAL', 'DOUBLE PRECISION',
            'CHAR', 'VARCHAR', 'TEXT',
            'DATE', 'TIME', 'TIMESTAMP', 'TIMESTAMPTZ',
            'BOOLEAN', 'UUID', 'JSON', 'JSONB', 'ARRAY'
        ]
    ],
    'postgresSpecific' => [
        'checkExtensions' => true,
        'allowedExtensions' => ['uuid-ossp', 'hstore', 'postgis'],
    ]
]);
```

### Validation Profiles

Use predefined validation profiles:

```php
// Strict validation for production
$strictValidator = SchemaValidator::createStrict([
    'requirePrimaryKeys' => true,
    'requireForeignKeyIndexes' => true,
    'checkNamingConventions' => true,
    'validateDataTypes' => true,
    'detectCircularReferences' => true,
]);

// Lenient validation for development
$lenientValidator = SchemaValidator::createLenient([
    'allowMissingPrimaryKeys' => true,
    'allowNamingInconsistencies' => true,
    'warnOnly' => true,
]);

// Performance-focused validation
$performanceValidator = SchemaValidator::createPerformanceFocused([
    'requireIndexesOnForeignKeys' => true,
    'checkIndexEfficiency' => true,
    'validateQueryPerformance' => true,
    'maxTableSize' => 1000000, // rows
]);
```

## Validation Results

### ValidationResult Class

The validation result provides detailed information:

```php
class ValidationResult
{
    public function isValid(): bool;
    public function getErrors(): array;
    public function getWarnings(): array;
    public function getErrors(string $category = null): array;
    public function getWarnings(string $category = null): array;
    public function getErrorCount(): int;
    public function getWarningCount(): int;
    public function getSummary(): array;
    public function toArray(): array;
}

// Usage
$result = $validator->validateSchema($schema);

echo "Validation Summary:\n";
echo "  Valid: " . ($result->isValid() ? 'Yes' : 'No') . "\n";
echo "  Errors: " . $result->getErrorCount() . "\n";
echo "  Warnings: " . $result->getWarningCount() . "\n";

// Get summary by category
$summary = $result->getSummary();
foreach ($summary as $category => $counts) {
    echo "  {$category}: {$counts['errors']} errors, {$counts['warnings']} warnings\n";
}
```

### Error Categories

Errors are categorized for easier filtering:

```php
// Get specific error types
$circularErrors = $result->getErrors('circular_reference');
$foreignKeyErrors = $result->getErrors('foreign_key');
$dataTypeErrors = $result->getErrors('data_type');
$indexErrors = $result->getErrors('index');
$namingErrors = $result->getErrors('naming');
$constraintErrors = $result->getErrors('constraint');
$customErrors = $result->getErrors('custom');
```

## Integration Examples

### CI/CD Pipeline Integration

```php
// ci-validation.php
#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';

use Daycry\Schemas\Schemas;
use Daycry\Schemas\SchemaValidator;

// Load schema from database
$schemas = new Schemas(['database' => 'testing']);
$schema = $schemas->get();

// Create strict validator for CI
$validator = SchemaValidator::createStrict();

// Run validation
$result = $validator->validateSchema($schema);

// Output results
if ($result->isValid()) {
    echo "✓ Schema validation passed\n";
    exit(0);
} else {
    echo "✗ Schema validation failed\n";
    
    foreach ($result->getErrors() as $error) {
        echo "  ERROR: {$error}\n";
    }
    
    foreach ($result->getWarnings() as $warning) {
        echo "  WARNING: {$warning}\n";
    }
    
    exit(1);
}
```

### Automated Health Checks

```php
function validateSchemaHealth(): array
{
    $schemas = new Schemas();
    $schema = $schemas->get();
    
    $validator = new SchemaValidator([
        'strictMode' => false,
        'warnOnly' => true,
    ]);
    
    $result = $validator->validateSchema($schema);
    
    return [
        'timestamp' => date('c'),
        'valid' => $result->isValid(),
        'error_count' => $result->getErrorCount(),
        'warning_count' => $result->getWarningCount(),
        'errors' => $result->getErrors(),
        'warnings' => $result->getWarnings(),
        'summary' => $result->getSummary(),
    ];
}

// Run health check and log results
$health = validateSchemaHealth();

if (!$health['valid']) {
    // Send alert
    // Log critical errors
    // Take corrective action
}
```

### Pre-Migration Validation

```php
function validateBeforeMigration($migrationFile)
{
    // Parse migration to get proposed changes
    $migrationHandler = new MigrationHandler();
    $proposedChanges = $migrationHandler->parseMigration($migrationFile);
    
    // Apply changes to current schema (simulation)
    $schemas = new Schemas();
    $currentSchema = $schemas->get();
    $proposedSchema = $currentSchema->merge($proposedChanges);
    
    // Validate proposed schema
    $validator = new SchemaValidator();
    $result = $validator->validateSchema($proposedSchema);
    
    if (!$result->isValid()) {
        echo "Migration validation failed:\n";
        foreach ($result->getErrors() as $error) {
            echo "  {$error}\n";
        }
        return false;
    }
    
    return true;
}

// Before running migration
if (!validateBeforeMigration('002_add_user_profiles.php')) {
    echo "Migration aborted due to validation errors\n";
    exit(1);
}
```

## Best Practices

### 1. Regular Validation
Run schema validation regularly:
```php
// Daily health check
if (date('H') === '06') { // 6 AM
    $health = validateSchemaHealth();
    logHealthCheck($health);
}
```

### 2. Staged Validation
Use different validation levels for different environments:
```php
// Development: Lenient
$devValidator = SchemaValidator::createLenient();

// Staging: Moderate
$stagingValidator = new SchemaValidator(['strictMode' => false]);

// Production: Strict
$prodValidator = SchemaValidator::createStrict();
```

### 3. Custom Rules for Business Logic
Add domain-specific validation:
```php
$validator->addCustomRule('audit_trail', function($schema) {
    $errors = [];
    
    foreach ($schema->tables as $table) {
        // Skip system tables
        if (in_array($table->name, ['migrations', 'sessions'])) {
            continue;
        }
        
        // Check for audit fields
        $auditFields = ['created_at', 'updated_at', 'created_by', 'updated_by'];
        foreach ($auditFields as $field) {
            if (!property_exists($table->fields, $field)) {
                $errors[] = "Table '{$table->name}' missing audit field '{$field}'";
            }
        }
    }
    
    return $errors;
});
```

### 4. Validation Documentation
Document validation rules:
```php
/**
 * Company Schema Validation Rules
 * 
 * 1. All tables must have primary keys
 * 2. All user-related tables must have audit fields
 * 3. All foreign keys must have corresponding indexes
 * 4. Table names must use snake_case
 * 5. No circular references allowed
 * 6. All monetary fields must use DECIMAL type
 */
$validator = new SchemaValidator([
    'requirePrimaryKeys' => true,
    'requireForeignKeyIndexes' => true,
    'checkNamingConventions' => true,
    'strictMode' => true,
]);
```

The Schema Validation system helps ensure database integrity, consistency, and adherence to best practices, making it an essential tool for maintaining high-quality database schemas.
