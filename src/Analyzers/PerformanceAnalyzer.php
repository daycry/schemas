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

namespace Daycry\Schemas\Analyzers;

use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\Index;

/**
 * Schema Performance Analyzer
 * 
 * Analyzes database schemas for performance issues and provides optimization recommendations
 */
class PerformanceAnalyzer
{
    /**
     * Performance issues found
     *
     * @var array<array>
     */
    protected array $issues = [];

    /**
     * Optimization recommendations
     *
     * @var array<array>
     */
    protected array $recommendations = [];

    /**
     * Performance score (0-100)
     */
    protected float $performanceScore = 0.0;

    /**
     * Analyze schema performance
     */
    public function analyze(Schema $schema): array
    {
        $this->reset();

        $this->analyzeForeignKeyIndexes($schema);
        $this->analyzeIndexEfficiency($schema);
        $this->analyzeDataTypes($schema);
        $this->analyzeTableStructure($schema);
        $this->analyzeRelationships($schema);
        $this->analyzeNormalization($schema);

        $this->calculatePerformanceScore();

        return [
            'score' => $this->performanceScore,
            'issues' => $this->issues,
            'recommendations' => $this->recommendations
        ];
    }

    /**
     * Get performance issues
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Get recommendations
     */
    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    /**
     * Get performance score
     */
    public function getPerformanceScore(): float
    {
        return $this->performanceScore;
    }

    /**
     * Reset analyzer state
     */
    protected function reset(): void
    {
        $this->issues = [];
        $this->recommendations = [];
        $this->performanceScore = 0.0;
    }

    /**
     * Analyze foreign key indexes
     */
    protected function analyzeForeignKeyIndexes(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            foreach ($table->foreignKeys as $fkName => $foreignKey) {
                if (!isset($foreignKey->column_name)) {
                    continue;
                }

                $hasIndex = $this->hasIndexOnColumn($table, $foreignKey->column_name);

                if (!$hasIndex) {
                    $this->addIssue(
                        'missing_fk_index',
                        "Missing index on foreign key column '{$foreignKey->column_name}' in table '{$tableName}'",
                        'high',
                        $tableName,
                        [
                            'column' => $foreignKey->column_name,
                            'foreign_key' => $fkName
                        ]
                    );

                    $this->addRecommendation(
                        'add_index',
                        "Add index on foreign key column '{$foreignKey->column_name}' in table '{$tableName}'",
                        "CREATE INDEX idx_{$tableName}_{$foreignKey->column_name} ON {$tableName} ({$foreignKey->column_name});",
                        'high'
                    );
                }
            }
        }
    }

    /**
     * Analyze index efficiency
     */
    protected function analyzeIndexEfficiency(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            $indexFields = [];
            foreach ($table->indexes as $indexName => $index) {
                $fields = $index->fields ?? [];
                
                if (empty($fields)) {
                    continue;
                }

                // Check for duplicate indexes
                $fieldKey = implode(',', $fields);
                if (isset($indexFields[$fieldKey])) {
                    $this->addIssue(
                        'duplicate_index',
                        "Duplicate index '{$indexName}' in table '{$tableName}' (same as '{$indexFields[$fieldKey]}')",
                        'medium',
                        $tableName,
                        [
                            'duplicate_indexes' => [$indexName, $indexFields[$fieldKey]],
                            'columns' => $fields
                        ]
                    );

                    $this->addRecommendation(
                        'remove_duplicate_index',
                        "Remove duplicate index '{$indexName}' from table '{$tableName}'",
                        "DROP INDEX {$indexName} ON {$tableName};",
                        'medium'
                    );
                } else {
                    $indexFields[$fieldKey] = $indexName;
                }

                // Check for overly wide indexes (more than 5 columns)
                if (count($fields) > 5) {
                    $this->addIssue(
                        'wide_index',
                        "Index '{$indexName}' in table '{$tableName}' has too many columns (" . count($fields) . ")",
                        'medium',
                        $tableName,
                        [
                            'index' => $indexName,
                            'column_count' => count($fields),
                            'columns' => $fields
                        ]
                    );

                    $this->addRecommendation(
                        'optimize_index',
                        "Consider splitting index '{$indexName}' or reducing the number of columns",
                        "Review the query patterns and consider creating separate indexes for different use cases",
                        'medium'
                    );
                }
            }
        }
    }

    /**
     * Analyze data types for efficiency
     */
    protected function analyzeDataTypes(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            foreach ($table->fields as $fieldName => $field) {
                if (!($field instanceof Field)) {
                    continue;
                }

                $this->analyzeFieldDataType($tableName, $fieldName, $field);
            }
        }
    }

    /**
     * Analyze individual field data type
     */
    protected function analyzeFieldDataType(string $tableName, string $fieldName, Field $field): void
    {
        if (!isset($field->type)) {
            return;
        }

        $type = strtolower($field->type);

        switch ($type) {
            case 'text':
            case 'longtext':
                if (isset($field->max_length) && $field->max_length <= 255) {
                    $this->addIssue(
                        'inefficient_text_type',
                        "Field '{$fieldName}' in table '{$tableName}' uses TEXT but could be VARCHAR({$field->max_length})",
                        'low',
                        $tableName,
                        [
                            'field' => $fieldName,
                            'current_type' => $type,
                            'suggested_type' => "VARCHAR({$field->max_length})"
                        ]
                    );
                }
                break;

            case 'varchar':
                if (isset($field->max_length) && $field->max_length > 4000) {
                    $this->addRecommendation(
                        'optimize_varchar',
                        "Consider using TEXT instead of VARCHAR({$field->max_length}) for field '{$fieldName}' in table '{$tableName}'",
                        "ALTER TABLE {$tableName} MODIFY {$fieldName} TEXT;",
                        'low'
                    );
                }
                break;

            case 'int':
            case 'integer':
                if (isset($field->max_length) && $field->max_length <= 3) {
                    $this->addRecommendation(
                        'optimize_int_type',
                        "Consider using TINYINT for field '{$fieldName}' in table '{$tableName}' if values are small",
                        "ALTER TABLE {$tableName} MODIFY {$fieldName} TINYINT;",
                        'low'
                    );
                }
                break;

            case 'enum':
                // ENUM is generally good for performance
                break;

            default:
                // Check for potential ENUM candidates
                if (in_array($type, ['varchar', 'char'], true) && 
                    isset($field->max_length) && $field->max_length <= 20) {
                    $this->addRecommendation(
                        'consider_enum',
                        "Consider using ENUM for field '{$fieldName}' in table '{$tableName}' if it has limited distinct values",
                        "Review the distinct values and consider: ALTER TABLE {$tableName} MODIFY {$fieldName} ENUM('value1', 'value2', ...);",
                        'low'
                    );
                }
        }
    }

    /**
     * Analyze table structure
     */
    protected function analyzeTableStructure(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            // Check for primary key
            $hasPrimaryKey = false;
            foreach ($table->fields as $field) {
                if (isset($field->primary_key) && $field->primary_key) {
                    $hasPrimaryKey = true;
                    break;
                }
            }

            if (!$hasPrimaryKey) {
                $this->addIssue(
                    'missing_primary_key',
                    "Table '{$tableName}' is missing a primary key",
                    'high',
                    $tableName
                );

                $this->addRecommendation(
                    'add_primary_key',
                    "Add a primary key to table '{$tableName}'",
                    "ALTER TABLE {$tableName} ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST;",
                    'high'
                );
            }

            // Check for too many columns
            $columnCount = count((array)$table->fields);
            if ($columnCount > 50) {
                $this->addIssue(
                    'too_many_columns',
                    "Table '{$tableName}' has too many columns ({$columnCount})",
                    'medium',
                    $tableName,
                    ['column_count' => $columnCount]
                );

                $this->addRecommendation(
                    'normalize_table',
                    "Consider normalizing table '{$tableName}' by splitting it into related tables",
                    "Review the table structure and identify groups of related columns that could be moved to separate tables",
                    'medium'
                );
            }
        }
    }

    /**
     * Analyze relationships
     */
    protected function analyzeRelationships(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            // Check for tables without relationships (but ignore tables that are part of a relationship)
            $hasRelations = count((array)$table->relations) > 0 || count((array)$table->foreignKeys) > 0;
            $isReferencedByOthers = $this->isTableReferencedByOthers($schema, $tableName);

            if (!$hasRelations && !$table->pivot && !$isReferencedByOthers) {
                $this->addIssue(
                    'isolated_table',
                    "Table '{$tableName}' has no relationships with other tables",
                    'low',
                    $tableName
                );
            }

            // Check for many-to-many without proper pivot
            foreach ($table->relations as $relation) {
                if (isset($relation->type) && $relation->type === 'manyToMany') {
                    if (!isset($relation->pivots) || empty($relation->pivots)) {
                        $this->addIssue(
                            'improper_many_to_many',
                            "Many-to-many relationship in table '{$tableName}' lacks proper pivot table definition",
                            'medium',
                            $tableName,
                            ['related_table' => $relation->table ?? 'unknown']
                        );
                    }
                }
            }
        }
    }

    /**
     * Analyze normalization
     */
    protected function analyzeNormalization(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            // Look for potential denormalization issues
            $textFields = [];
            $potentialDuplicates = [];

            foreach ($table->fields as $fieldName => $field) {
                if (!($field instanceof Field)) {
                    continue;
                }

                // Track text fields that might be repeated across tables
                if (isset($field->type) && in_array(strtolower($field->type), ['varchar', 'text'], true)) {
                    $textFields[] = $fieldName;
                }

                // Look for common patterns that suggest denormalization
                if (preg_match('/_(name|title|description)$/', $fieldName)) {
                    $potentialDuplicates[] = $fieldName;
                }
            }

            // If there are many text fields, suggest normalization
            if (count($textFields) > 10) {
                $this->addRecommendation(
                    'consider_normalization',
                    "Table '{$tableName}' has many text fields - consider normalization",
                    "Review if some text fields could be moved to related tables or if they represent repeated data",
                    'low'
                );
            }
        }
    }

    /**
     * Calculate overall performance score
     */
    protected function calculatePerformanceScore(): void
    {
        $totalIssues = count($this->issues);
        
        if ($totalIssues === 0) {
            $this->performanceScore = 100.0;
            return;
        }

        $penalty = 0;
        foreach ($this->issues as $issue) {
            switch ($issue['severity']) {
                case 'high':
                    $penalty += 20;
                    break;
                case 'medium':
                    $penalty += 10;
                    break;
                case 'low':
                    $penalty += 5;
                    break;
            }
        }

        $this->performanceScore = max(0, 100 - $penalty);
    }

    /**
     * Check if table has index on specific column
     */
    protected function hasIndexOnColumn(Table $table, string $columnName): bool
    {
        foreach ($table->indexes as $index) {
            if (in_array($columnName, $index->fields ?? [], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add performance issue
     */
    protected function addIssue(string $type, string $description, string $severity, string $tableName, array $details = []): void
    {
        $this->issues[] = [
            'type' => $type,
            'description' => $description,
            'severity' => $severity,
            'table' => $tableName,
            'details' => $details
        ];
    }

    /**
     * Add recommendation
     */
    protected function addRecommendation(string $type, string $description, string $solution, string $priority): void
    {
        $this->recommendations[] = [
            'type' => $type,
            'description' => $description,
            'solution' => $solution,
            'priority' => $priority
        ];
    }

    /**
     * Check if a table is referenced by other tables
     */
    protected function isTableReferencedByOthers(Schema $schema, string $tableName): bool
    {
        foreach ($schema->tables as $otherTableName => $otherTable) {
            if ($otherTableName === $tableName || !($otherTable instanceof Table)) {
                continue;
            }

            // Check foreign keys
            foreach ($otherTable->foreignKeys as $foreignKey) {
                if (isset($foreignKey->foreign_table_name) && $foreignKey->foreign_table_name === $tableName) {
                    return true;
                }
            }

            // Check relations
            foreach ($otherTable->relations as $relation) {
                if (isset($relation->table) && $relation->table === $tableName) {
                    return true;
                }
            }
        }

        return false;
    }
}
