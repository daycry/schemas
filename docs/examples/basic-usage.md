# Basic Usage Examples

This document provides fundamental examples for getting started with the Daycry Schemas library.

## Table of Contents
- [Installation and Setup](#installation-and-setup)
- [Reading Schemas](#reading-schemas)
- [Working with Tables](#working-with-tables)
- [Working with Fields](#working-with-fields)
- [Working with Indexes](#working-with-indexes)
- [Working with Foreign Keys](#working-with-foreign-keys)
- [Error Handling](#error-handling)
- [Complete Examples](#complete-examples)

## Installation and Setup

### Prerequisites
- PHP 8.1+
- CodeIgniter 4.0+
- Database connection configured

### Installation
```bash
composer require daycry/schemas
```

### Basic Configuration
```php
// app/Config/Schemas.php
<?php namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public string $defaultGroup = 'default';
    public array $ignoredTables = ['migrations'];
    public bool $silent = false;
}
```

## Reading Schemas

### Example 1: Get Complete Schema
```php
<?php
use Daycry\Schemas\Schemas;

// Create schemas instance
$schemas = new Schemas();

try {
    // Get complete database schema
    $schema = $schemas->get();
    
    echo "Database Schema Information:\n";
    echo "Total tables: " . count($schema->tables) . "\n";
    echo "Schema version: " . ($schema->version ?? 'Unknown') . "\n";
    
    // List all tables
    foreach ($schema->tables as $table) {
        echo "- {$table->name} ({$table->fields->count()} fields)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

**Expected Output:**
```
Database Schema Information:
Total tables: 5
Schema version: Unknown
- users (8 fields)
- posts (6 fields)
- categories (4 fields)
- tags (3 fields)
- post_tags (3 fields)
```

### Example 2: Get Specific Table
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();

// Get specific table
$table = $schemas->getTable('users');

if ($table) {
    echo "Table: {$table->name}\n";
    echo "Engine: {$table->engine}\n";
    echo "Collation: {$table->collation}\n";
    echo "Comment: {$table->comment}\n";
    echo "Fields: " . count($table->fields) . "\n";
    echo "Indexes: " . count($table->indexes) . "\n";
    echo "Foreign Keys: " . count($table->foreignKeys) . "\n";
} else {
    echo "Table 'users' not found\n";
}
```

### Example 3: Check Table Existence
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();

$tablesToCheck = ['users', 'posts', 'nonexistent'];

foreach ($tablesToCheck as $tableName) {
    if ($schemas->hasTable($tableName)) {
        echo "✓ Table '{$tableName}' exists\n";
    } else {
        echo "✗ Table '{$tableName}' does not exist\n";
    }
}
```

## Working with Tables

### Example 4: Exploring Table Structure
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$table = $schemas->getTable('users');

if ($table) {
    echo "=== Table: {$table->name} ===\n\n";
    
    // Table properties
    echo "Engine: {$table->engine}\n";
    echo "Charset: {$table->charset}\n";
    echo "Collation: {$table->collation}\n";
    echo "Comment: {$table->comment}\n\n";
    
    // Fields information
    echo "=== Fields ({$table->fields->count()}) ===\n";
    foreach ($table->fields as $field) {
        $nullable = $field->nullable ? 'NULL' : 'NOT NULL';
        $default = $field->default !== null ? "DEFAULT '{$field->default}'" : '';
        $autoInc = $field->auto_increment ? 'AUTO_INCREMENT' : '';
        
        echo "- {$field->name}: {$field->type}";
        if ($field->max_length) echo "({$field->max_length})";
        echo " {$nullable} {$default} {$autoInc}\n";
    }
    
    // Primary key
    $primaryKey = $table->getPrimaryKey();
    if ($primaryKey) {
        echo "\nPrimary Key: " . implode(', ', $primaryKey->fields) . "\n";
    }
}
```

### Example 5: Table Relationships
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$table = $schemas->getTable('posts');

if ($table && $table->relations) {
    echo "=== Relations for '{$table->name}' ===\n";
    
    foreach ($table->relations as $relation) {
        echo "Type: {$relation->type}\n";
        echo "Related Table: {$relation->table}\n";
        echo "Local Field: {$relation->field}\n";
        echo "Foreign Field: {$relation->foreign_field}\n";
        
        if ($relation->pivot) {
            echo "Pivot Table: {$relation->pivot}\n";
        }
        echo "---\n";
    }
}
```

## Working with Fields

### Example 6: Field Analysis
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$table = $schemas->getTable('users');

if ($table) {
    echo "=== Field Analysis for '{$table->name}' ===\n\n";
    
    foreach ($table->fields as $field) {
        echo "Field: {$field->name}\n";
        echo "  Type: {$field->type}";
        if ($field->max_length) echo "({$field->max_length})";
        echo "\n";
        
        // Type checking
        echo "  Properties:\n";
        echo "    - Numeric: " . ($field->isNumeric() ? 'Yes' : 'No') . "\n";
        echo "    - String: " . ($field->isString() ? 'Yes' : 'No') . "\n";
        echo "    - Date: " . ($field->isDate() ? 'Yes' : 'No') . "\n";
        echo "    - Nullable: " . ($field->nullable ? 'Yes' : 'No') . "\n";
        echo "    - Primary Key: " . ($field->primary_key ? 'Yes' : 'No') . "\n";
        echo "    - Auto Increment: " . ($field->auto_increment ? 'Yes' : 'No') . "\n";
        
        if ($field->default !== null) {
            echo "    - Default: {$field->default}\n";
        }
        
        if ($field->comment) {
            echo "    - Comment: {$field->comment}\n";
        }
        
        echo "\n";
    }
}
```

### Example 7: Field Type Filtering
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$table = $schemas->getTable('users');

if ($table) {
    echo "=== Field Types in '{$table->name}' ===\n\n";
    
    // Group fields by type
    $fieldsByType = [];
    foreach ($table->fields as $field) {
        $type = $field->type;
        if (!isset($fieldsByType[$type])) {
            $fieldsByType[$type] = [];
        }
        $fieldsByType[$type][] = $field->name;
    }
    
    // Display grouped fields
    foreach ($fieldsByType as $type => $fields) {
        echo "{$type}: " . implode(', ', $fields) . "\n";
    }
    
    echo "\n=== Special Fields ===\n";
    
    // Find special field types
    $numericFields = [];
    $stringFields = [];
    $dateFields = [];
    
    foreach ($table->fields as $field) {
        if ($field->isNumeric()) $numericFields[] = $field->name;
        if ($field->isString()) $stringFields[] = $field->name;
        if ($field->isDate()) $dateFields[] = $field->name;
    }
    
    echo "Numeric: " . implode(', ', $numericFields) . "\n";
    echo "String: " . implode(', ', $stringFields) . "\n";
    echo "Date/Time: " . implode(', ', $dateFields) . "\n";
}
```

## Working with Indexes

### Example 8: Index Information
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$table = $schemas->getTable('users');

if ($table && $table->indexes) {
    echo "=== Indexes for '{$table->name}' ===\n\n";
    
    foreach ($table->indexes as $index) {
        echo "Index: {$index->name}\n";
        echo "  Type: {$index->type}\n";
        echo "  Fields: " . implode(', ', $index->fields) . "\n";
        echo "  Unique: " . ($index->unique ? 'Yes' : 'No') . "\n";
        echo "  Method: {$index->method}\n";
        
        // Check index type
        if ($index->isPrimary()) {
            echo "  → Primary Key Index\n";
        } elseif ($index->isUnique()) {
            echo "  → Unique Index\n";
        } else {
            echo "  → Regular Index\n";
        }
        
        if ($index->comment) {
            echo "  Comment: {$index->comment}\n";
        }
        
        echo "\n";
    }
}
```

### Example 9: Finding Specific Index Types
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$schema = $schemas->get();

echo "=== Index Analysis Across All Tables ===\n\n";

$indexStats = [
    'primary' => 0,
    'unique' => 0,
    'regular' => 0,
    'composite' => 0
];

foreach ($schema->tables as $table) {
    if ($table->indexes) {
        echo "Table: {$table->name}\n";
        
        foreach ($table->indexes as $index) {
            if ($index->isPrimary()) {
                $indexStats['primary']++;
                echo "  ✓ Primary: " . implode(', ', $index->fields) . "\n";
            } elseif ($index->isUnique()) {
                $indexStats['unique']++;
                echo "  ◆ Unique: " . implode(', ', $index->fields) . "\n";
            } else {
                $indexStats['regular']++;
                echo "  ● Regular: " . implode(', ', $index->fields) . "\n";
            }
            
            if (count($index->fields) > 1) {
                $indexStats['composite']++;
                echo "    → Composite index\n";
            }
        }
        echo "\n";
    }
}

echo "=== Index Statistics ===\n";
echo "Primary Keys: {$indexStats['primary']}\n";
echo "Unique Indexes: {$indexStats['unique']}\n";
echo "Regular Indexes: {$indexStats['regular']}\n";
echo "Composite Indexes: {$indexStats['composite']}\n";
```

## Working with Foreign Keys

### Example 10: Foreign Key Analysis
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$table = $schemas->getTable('posts');

if ($table && $table->foreignKeys) {
    echo "=== Foreign Keys for '{$table->name}' ===\n\n";
    
    foreach ($table->foreignKeys as $fk) {
        echo "Constraint: {$fk->constraint_name}\n";
        echo "  Local Column: {$fk->column_name}\n";
        echo "  References: {$fk->foreign_table_name}.{$fk->foreign_column_name}\n";
        echo "  On Delete: {$fk->on_delete}\n";
        echo "  On Update: {$fk->on_update}\n";
        echo "  Valid: " . ($fk->isValid() ? 'Yes' : 'No') . "\n";
        echo "\n";
    }
}
```

### Example 11: Relationship Mapping
```php
<?php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$schema = $schemas->get();

echo "=== Database Relationship Map ===\n\n";

$relationships = [];

foreach ($schema->tables as $table) {
    if ($table->foreignKeys) {
        foreach ($table->foreignKeys as $fk) {
            $relationship = [
                'from_table' => $table->name,
                'from_column' => $fk->column_name,
                'to_table' => $fk->foreign_table_name,
                'to_column' => $fk->foreign_column_name,
                'constraint' => $fk->constraint_name
            ];
            $relationships[] = $relationship;
        }
    }
}

// Group by target table
$grouped = [];
foreach ($relationships as $rel) {
    $target = $rel['to_table'];
    if (!isset($grouped[$target])) {
        $grouped[$target] = [];
    }
    $grouped[$target][] = $rel;
}

foreach ($grouped as $targetTable => $relations) {
    echo "Table '{$targetTable}' is referenced by:\n";
    foreach ($relations as $rel) {
        echo "  - {$rel['from_table']}.{$rel['from_column']}\n";
    }
    echo "\n";
}
```

## Error Handling

### Example 12: Comprehensive Error Handling
```php
<?php
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Exceptions\SchemasException;

try {
    $schemas = new Schemas();
    
    // Set database group that might not exist
    $schemas->setDatabase('nonexistent');
    
    $schema = $schemas->get();
    
} catch (SchemasException $e) {
    echo "Schemas Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    
    // Check for specific error types
    if (str_contains($e->getMessage(), 'database')) {
        echo "This appears to be a database connection issue.\n";
    }
    
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
    
} finally {
    // Check for any accumulated errors
    $errors = $schemas->getErrors();
    if (!empty($errors)) {
        echo "Additional errors:\n";
        foreach ($errors as $error) {
            echo "- {$error}\n";
        }
    }
}
```

### Example 13: Silent Mode with Error Checking
```php
<?php
use Daycry\Schemas\Schemas;

// Enable silent mode
$config = config('Schemas');
$config->silent = true;

$schemas = new Schemas($config);

// Try to get schema
$schema = $schemas->get();

// Check for errors manually
if ($schemas->hasErrors()) {
    echo "Errors occurred during schema loading:\n";
    foreach ($schemas->getErrors() as $error) {
        echo "- {$error}\n";
    }
} else {
    echo "Schema loaded successfully with " . count($schema->tables) . " tables\n";
}
```

## Complete Examples

### Example 14: Complete Schema Inspector
```php
<?php
use Daycry\Schemas\Schemas;

class SchemaInspector
{
    private Schemas $schemas;
    
    public function __construct()
    {
        $this->schemas = new Schemas();
    }
    
    public function inspect(): void
    {
        try {
            $schema = $this->schemas->get();
            
            $this->displayOverview($schema);
            $this->displayTables($schema);
            $this->displayRelationships($schema);
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    private function displayOverview($schema): void
    {
        echo "=== DATABASE OVERVIEW ===\n";
        echo "Total Tables: " . count($schema->tables) . "\n";
        
        $totalFields = 0;
        $totalIndexes = 0;
        $totalForeignKeys = 0;
        
        foreach ($schema->tables as $table) {
            $totalFields += count($table->fields);
            $totalIndexes += count($table->indexes);
            $totalForeignKeys += count($table->foreignKeys);
        }
        
        echo "Total Fields: {$totalFields}\n";
        echo "Total Indexes: {$totalIndexes}\n";
        echo "Total Foreign Keys: {$totalForeignKeys}\n\n";
    }
    
    private function displayTables($schema): void
    {
        echo "=== TABLES ===\n";
        foreach ($schema->tables as $table) {
            echo "• {$table->name}\n";
            echo "  Fields: " . count($table->fields) . "\n";
            echo "  Indexes: " . count($table->indexes) . "\n";
            echo "  Foreign Keys: " . count($table->foreignKeys) . "\n";
            
            if ($table->comment) {
                echo "  Comment: {$table->comment}\n";
            }
            echo "\n";
        }
    }
    
    private function displayRelationships($schema): void
    {
        echo "=== RELATIONSHIPS ===\n";
        
        foreach ($schema->tables as $table) {
            if ($table->foreignKeys && count($table->foreignKeys) > 0) {
                echo "Table '{$table->name}' references:\n";
                
                foreach ($table->foreignKeys as $fk) {
                    echo "  → {$fk->foreign_table_name}.{$fk->foreign_column_name}";
                    echo " (via {$fk->column_name})\n";
                }
                echo "\n";
            }
        }
    }
}

// Usage
$inspector = new SchemaInspector();
$inspector->inspect();
```

### Example 15: Schema Comparison Tool
```php
<?php
use Daycry\Schemas\Schemas;

class SchemaComparator
{
    public function compareDatabases(string $db1, string $db2): void
    {
        $schemas1 = new Schemas();
        $schemas1->setDatabase($db1);
        $schema1 = $schemas1->get();
        
        $schemas2 = new Schemas();
        $schemas2->setDatabase($db2);
        $schema2 = $schemas2->get();
        
        $this->compareSchemas($schema1, $schema2, $db1, $db2);
    }
    
    private function compareSchemas($schema1, $schema2, $name1, $name2): void
    {
        echo "=== SCHEMA COMPARISON: {$name1} vs {$name2} ===\n\n";
        
        $tables1 = array_keys((array)$schema1->tables);
        $tables2 = array_keys((array)$schema2->tables);
        
        // Tables only in first schema
        $onlyIn1 = array_diff($tables1, $tables2);
        if (!empty($onlyIn1)) {
            echo "Tables only in {$name1}:\n";
            foreach ($onlyIn1 as $table) {
                echo "  - {$table}\n";
            }
            echo "\n";
        }
        
        // Tables only in second schema
        $onlyIn2 = array_diff($tables2, $tables1);
        if (!empty($onlyIn2)) {
            echo "Tables only in {$name2}:\n";
            foreach ($onlyIn2 as $table) {
                echo "  - {$table}\n";
            }
            echo "\n";
        }
        
        // Common tables
        $common = array_intersect($tables1, $tables2);
        if (!empty($common)) {
            echo "Common tables: " . implode(', ', $common) . "\n\n";
            
            foreach ($common as $tableName) {
                $this->compareTables(
                    $schema1->tables->$tableName,
                    $schema2->tables->$tableName,
                    $tableName
                );
            }
        }
    }
    
    private function compareTables($table1, $table2, $tableName): void
    {
        echo "--- Table: {$tableName} ---\n";
        
        $fields1 = array_keys((array)$table1->fields);
        $fields2 = array_keys((array)$table2->fields);
        
        $fieldDiff1 = array_diff($fields1, $fields2);
        $fieldDiff2 = array_diff($fields2, $fields1);
        
        if (!empty($fieldDiff1) || !empty($fieldDiff2)) {
            if (!empty($fieldDiff1)) {
                echo "  Fields only in first: " . implode(', ', $fieldDiff1) . "\n";
            }
            if (!empty($fieldDiff2)) {
                echo "  Fields only in second: " . implode(', ', $fieldDiff2) . "\n";
            }
        } else {
            echo "  Fields are identical\n";
        }
        echo "\n";
    }
}

// Usage
$comparator = new SchemaComparator();
$comparator->compareDatabases('development', 'production');
```

These basic usage examples provide a solid foundation for working with the Daycry Schemas library. Each example builds upon previous concepts and demonstrates practical real-world scenarios.
