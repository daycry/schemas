<?php

declare(strict_types=1);

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Daycry\Schemas\Validators;

use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\ForeignKey;

/**
 * Schema Validator
 * 
 * Validates schema integrity and provides recommendations for optimization
 */
class SchemaValidator
{
    /**
     * Array of validation errors found
     *
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * Array of validation warnings found
     *
     * @var array<string>
     */
    protected array $warnings = [];

    /**
     * Array of optimization suggestions
     *
     * @var array<string>
     */
    protected array $suggestions = [];

    /**
     * Validate a complete schema
     *
     * @param Schema $schema The schema to validate
     * @return bool True if schema is valid, false otherwise
     */
    public function validate(Schema $schema): bool
    {
        $this->reset();

        // Validate foreign key consistency
        $this->validateForeignKeyConsistency($schema);

        // Detect circular references
        $this->detectCircularReferences($schema);

        // Validate data types
        $this->validateDataTypes($schema);

        // Check for duplicate indexes
        $this->validateIndexes($schema);

        // Generate optimization suggestions
        $this->generateOptimizationSuggestions($schema);

        return empty($this->errors);
    }

    /**
     * Get validation errors
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validation warnings
     *
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get optimization suggestions
     *
     * @return array<string>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * Reset validator state
     */
    protected function reset(): void
    {
        $this->errors = [];
        $this->warnings = [];
        $this->suggestions = [];
    }

    /**
     * Validate foreign key consistency
     */
    protected function validateForeignKeyConsistency(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            foreach ($table->foreignKeys as $fkName => $foreignKey) {
                if (!($foreignKey instanceof ForeignKey)) {
                    continue;
                }

                // Check if referenced table exists
                if (!isset($schema->tables->{$foreignKey->foreign_table_name})) {
                    $this->errors[] = "Foreign key '{$fkName}' in table '{$tableName}' references non-existent table '{$foreignKey->foreign_table_name}'";
                    continue;
                }

                // Check if referenced column exists
                $referencedTable = $schema->tables->{$foreignKey->foreign_table_name};
                if (isset($foreignKey->foreign_column_name) && 
                    !isset($referencedTable->fields->{$foreignKey->foreign_column_name})) {
                    $this->errors[] = "Foreign key '{$fkName}' in table '{$tableName}' references non-existent column '{$foreignKey->foreign_column_name}' in table '{$foreignKey->foreign_table_name}'";
                }

                // Check if local column exists
                if (isset($foreignKey->column_name) && 
                    !isset($table->fields->{$foreignKey->column_name})) {
                    $this->errors[] = "Foreign key '{$fkName}' in table '{$tableName}' references non-existent local column '{$foreignKey->column_name}'";
                }
            }
        }
    }

    /**
     * Detect circular references in relationships
     */
    protected function detectCircularReferences(Schema $schema): void
    {
        $visited = [];
        $recursionStack = [];

        foreach ($schema->tables as $tableName => $table) {
            if (!isset($visited[$tableName])) {
                if ($this->hasCircularReference($schema, $tableName, $visited, $recursionStack)) {
                    $this->warnings[] = "Circular reference detected involving table '{$tableName}'";
                }
            }
        }
    }

    /**
     * Helper method for circular reference detection
     */
    protected function hasCircularReference(Schema $schema, string $tableName, array &$visited, array &$recursionStack): bool
    {
        $visited[$tableName] = true;
        $recursionStack[$tableName] = true;

        $table = $schema->tables->{$tableName};
        if (!($table instanceof Table)) {
            return false;
        }

        foreach ($table->relations as $relation) {
            $relatedTable = $relation->table;
            
            if (!isset($visited[$relatedTable])) {
                if ($this->hasCircularReference($schema, $relatedTable, $visited, $recursionStack)) {
                    return true;
                }
            } elseif (isset($recursionStack[$relatedTable])) {
                return true;
            }
        }

        unset($recursionStack[$tableName]);
        return false;
    }

    /**
     * Validate data types consistency
     */
    protected function validateDataTypes(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            foreach ($table->fields as $fieldName => $field) {
                // Check for potentially inefficient data types
                if (isset($field->type)) {
                    switch (strtolower($field->type)) {
                        case 'text':
                        case 'longtext':
                            if (isset($field->max_length) && $field->max_length < 255) {
                                $this->suggestions[] = "Field '{$fieldName}' in table '{$tableName}' uses TEXT but could be VARCHAR({$field->max_length})";
                            }
                            break;
                        
                        case 'varchar':
                            if (isset($field->max_length) && $field->max_length > 4000) {
                                $this->suggestions[] = "Field '{$fieldName}' in table '{$tableName}' uses VARCHAR({$field->max_length}) but could be TEXT";
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * Validate indexes for duplicates and efficiency
     */
    protected function validateIndexes(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            $indexColumns = [];
            foreach ($table->indexes as $indexName => $index) {
                $columns = $index->fields ?? [];
                $columnKey = implode(',', $columns);
                
                if (isset($indexColumns[$columnKey])) {
                    $this->warnings[] = "Duplicate index detected in table '{$tableName}': '{$indexName}' and '{$indexColumns[$columnKey]}' cover the same columns";
                } else {
                    $indexColumns[$columnKey] = $indexName;
                }
            }

            // Check for missing indexes on foreign keys
            foreach ($table->foreignKeys as $fkName => $foreignKey) {
                if (isset($foreignKey->column_name)) {
                    $hasIndex = false;
                    foreach ($table->indexes as $index) {
                        if (in_array($foreignKey->column_name, $index->fields ?? [], true)) {
                            $hasIndex = true;
                            break;
                        }
                    }
                    
                    if (!$hasIndex) {
                        $this->suggestions[] = "Foreign key column '{$foreignKey->column_name}' in table '{$tableName}' should have an index for better performance";
                    }
                }
            }
        }
    }

    /**
     * Generate optimization suggestions
     */
    protected function generateOptimizationSuggestions(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            // Suggest primary key if missing
            $hasPrimaryKey = false;
            foreach ($table->fields as $field) {
                if (isset($field->primary_key) && $field->primary_key) {
                    $hasPrimaryKey = true;
                    break;
                }
            }
            
            if (!$hasPrimaryKey) {
                $this->suggestions[] = "Table '{$tableName}' should have a primary key for better performance and replication";
            }

            // Check for tables without relations
            if (count((array)$table->relations) === 0 && count((array)$table->foreignKeys) === 0) {
                $this->warnings[] = "Table '{$tableName}' has no relationships - consider if this is intentional";
            }
        }
    }
}
