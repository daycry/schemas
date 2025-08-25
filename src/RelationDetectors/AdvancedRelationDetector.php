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

namespace Daycry\Schemas\RelationDetectors;

use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Relation;
use Daycry\Schemas\Structures\Mergeable;

/**
 * Advanced Relation Detector
 * 
 * Provides sophisticated algorithms for detecting database relationships
 */
class AdvancedRelationDetector
{
    /**
     * Patterns for detecting polymorphic relationships
     */
    protected array $polymorphicPatterns = [
        '/^(.+)able_type$/',  // commentable_type
        '/^(.+)_type$/',      // parent_type
    ];

    /**
     * Patterns for self-referencing relationships
     */
    protected array $selfReferencingPatterns = [
        '/^parent_id$/',
        '/^(.+)_parent_id$/',
        '/^manager_id$/',
        '/^supervisor_id$/',
    ];

    /**
     * Detect advanced relationships in schema
     */
    public function detectRelations(Schema $schema): Schema
    {
        $this->detectPolymorphicRelations($schema);
        $this->detectSelfReferencingRelations($schema);
        $this->detectImplicitManyToMany($schema);
        $this->detectHierarchicalRelations($schema);
        $this->detectValueObjects($schema);

        return $schema;
    }

    /**
     * Detect polymorphic relationships
     */
    protected function detectPolymorphicRelations(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            $polymorphicFields = [];
            
            // Look for *_type and *_id field pairs
            foreach ($table->fields as $fieldName => $field) {
                foreach ($this->polymorphicPatterns as $pattern) {
                    if (preg_match($pattern, $fieldName, $matches)) {
                        $baseName = $matches[1];
                        $idFieldName = $baseName . '_id';
                        
                        if (isset($table->fields->{$idFieldName})) {
                            $polymorphicFields[$baseName] = [
                                'type_field' => $fieldName,
                                'id_field' => $idFieldName
                            ];
                        }
                    }
                }
            }

            // Create polymorphic relations
            foreach ($polymorphicFields as $baseName => $fields) {
                $relation = new Relation();
                $relation->type = 'morphTo';
                $relation->polymorphic = true;
                $relation->morphType = $fields['type_field'];
                $relation->morphId = $fields['id_field'];
                $relation->table = $baseName;

                $table->relations->{$baseName} = $relation;

                // Find potential target tables and create inverse relations
                $this->createPolymorphicInverseRelations($schema, $tableName, $baseName, $fields);
            }
        }
    }

    /**
     * Detect self-referencing relationships
     */
    protected function detectSelfReferencingRelations(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            foreach ($table->fields as $fieldName => $field) {
                foreach ($this->selfReferencingPatterns as $pattern) {
                    if (preg_match($pattern, $fieldName)) {
                        // Check if this field references the same table's primary key
                        $primaryKey = $this->findPrimaryKeyField($table);
                        
                        if ($primaryKey) {
                            // Self-referencing relation (parent)
                            $parentRelation = new Relation();
                            $parentRelation->type = 'belongsTo';
                            $parentRelation->table = $tableName;
                            $parentRelation->singleton = true;
                            $parentRelation->selfReferencing = true;
                            $parentRelation->pivots = [[
                                $tableName,
                                $fieldName,
                                $tableName,
                                $primaryKey
                            ]];

                            $table->relations->parent = $parentRelation;

                            // Self-referencing relation (children)
                            $childrenRelation = new Relation();
                            $childrenRelation->type = 'hasMany';
                            $childrenRelation->table = $tableName;
                            $childrenRelation->selfReferencing = true;
                            $childrenRelation->pivots = [[
                                $tableName,
                                $primaryKey,
                                $tableName,
                                $fieldName
                            ]];

                            $table->relations->children = $childrenRelation;
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * Detect implicit many-to-many relationships
     */
    protected function detectImplicitManyToMany(Schema $schema): void
    {
        $tableNames = array_keys((array)$schema->tables);

        foreach ($tableNames as $table1) {
            foreach ($tableNames as $table2) {
                if ($table1 >= $table2) continue; // Avoid duplicates and self-comparison

                // Look for tables that could serve as implicit pivot tables
                $potentialPivots = $this->findPotentialPivotTables($schema, $table1, $table2);

                foreach ($potentialPivots as $pivotTable) {
                    if ($this->validateImplicitPivot($schema, $table1, $table2, $pivotTable)) {
                        $this->createImplicitManyToManyRelations($schema, $table1, $table2, $pivotTable);
                    }
                }
            }
        }
    }

    /**
     * Detect hierarchical relationships (tree structures)
     */
    protected function detectHierarchicalRelations(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            // Look for common hierarchical patterns
            $hierarchicalFields = [];
            
            foreach ($table->fields as $fieldName => $field) {
                if (preg_match('/^(lft|left|rgt|right|depth|level|path)$/', $fieldName)) {
                    $hierarchicalFields[] = $fieldName;
                }
            }

            // If we found hierarchical fields, mark the table as hierarchical
            if (count($hierarchicalFields) >= 2) {
                // Check for nested set pattern (lft, rgt)
                if (in_array('lft', $hierarchicalFields) && in_array('rgt', $hierarchicalFields)) {
                    $this->addHierarchicalMetadata($table, 'nested_set', $hierarchicalFields);
                }
                // Check for path enumeration
                elseif (in_array('path', $hierarchicalFields)) {
                    $this->addHierarchicalMetadata($table, 'path_enumeration', $hierarchicalFields);
                }
                // Check for adjacency list with level
                elseif (in_array('level', $hierarchicalFields) || in_array('depth', $hierarchicalFields)) {
                    $this->addHierarchicalMetadata($table, 'adjacency_list_with_level', $hierarchicalFields);
                }
            }
        }
    }

    /**
     * Detect value objects (embedded objects)
     */
    protected function detectValueObjects(Schema $schema): void
    {
        foreach ($schema->tables as $tableName => $table) {
            if (!($table instanceof Table)) {
                continue;
            }

            $valueObjectGroups = [];
            
            // Look for field groups that could be value objects
            foreach ($table->fields as $fieldName => $field) {
                // Address value object pattern
                if (preg_match('/^(billing|shipping|home|work)_(address|street|city|state|zip|country)/', $fieldName, $matches)) {
                    $prefix = $matches[1];
                    $type = $matches[2];
                    
                    if (!isset($valueObjectGroups[$prefix . '_address'])) {
                        $valueObjectGroups[$prefix . '_address'] = [];
                    }
                    $valueObjectGroups[$prefix . '_address'][] = $fieldName;
                }
                
                // Money value object pattern
                elseif (preg_match('/^(.+)_(amount|currency)$/', $fieldName, $matches)) {
                    $prefix = $matches[1];
                    
                    if (!isset($valueObjectGroups[$prefix . '_money'])) {
                        $valueObjectGroups[$prefix . '_money'] = [];
                    }
                    $valueObjectGroups[$prefix . '_money'][] = $fieldName;
                }
                
                // Name value object pattern
                elseif (preg_match('/^(first|last|middle)_name$/', $fieldName)) {
                    if (!isset($valueObjectGroups['full_name'])) {
                        $valueObjectGroups['full_name'] = [];
                    }
                    $valueObjectGroups['full_name'][] = $fieldName;
                }
            }

            // Add value object metadata
            foreach ($valueObjectGroups as $groupName => $fields) {
                if (count($fields) >= 2) {
                    $this->addValueObjectMetadata($table, $groupName, $fields);
                }
            }
        }
    }

    /**
     * Create polymorphic inverse relations
     */
    protected function createPolymorphicInverseRelations(Schema $schema, string $sourceTable, string $baseName, array $fields): void
    {
        foreach ($schema->tables as $targetTableName => $targetTable) {
            if ($targetTableName === $sourceTable || !($targetTable instanceof Table)) {
                continue;
            }

            // Create morphMany relation
            $relation = new Relation();
            $relation->type = 'morphMany';
            $relation->table = $sourceTable;
            $relation->polymorphic = true;
            $relation->morphType = $fields['type_field'];
            $relation->morphId = $fields['id_field'];

            $targetTable->relations->{$sourceTable} = $relation;
        }
    }

    /**
     * Find potential pivot tables for two tables
     */
    protected function findPotentialPivotTables(Schema $schema, string $table1, string $table2): array
    {
        $potentialPivots = [];

        foreach ($schema->tables as $tableName => $table) {
            if ($tableName === $table1 || $tableName === $table2 || !($table instanceof Table)) {
                continue;
            }

            // Check if this table has foreign keys to both target tables
            $referencesTable1 = false;
            $referencesTable2 = false;

            foreach ($table->foreignKeys as $fk) {
                if (isset($fk->foreign_table_name)) {
                    if ($fk->foreign_table_name === $table1) {
                        $referencesTable1 = true;
                    }
                    if ($fk->foreign_table_name === $table2) {
                        $referencesTable2 = true;
                    }
                }
            }

            if ($referencesTable1 && $referencesTable2) {
                $potentialPivots[] = $tableName;
            }
        }

        return $potentialPivots;
    }

    /**
     * Validate that a table can serve as an implicit pivot
     */
    protected function validateImplicitPivot(Schema $schema, string $table1, string $table2, string $pivotTable): bool
    {
        $pivot = $schema->tables->{$pivotTable};
        
        if (!($pivot instanceof Table)) {
            return false;
        }

        // Check that the pivot table primarily contains foreign keys to the two tables
        $totalFields = count((array)$pivot->fields);
        $foreignKeyFields = 0;
        $relevantForeignKeys = 0;

        foreach ($pivot->foreignKeys as $fk) {
            $foreignKeyFields++;
            if (isset($fk->foreign_table_name) && 
                in_array($fk->foreign_table_name, [$table1, $table2], true)) {
                $relevantForeignKeys++;
            }
        }

        // Must have exactly 2 relevant foreign keys and minimal additional fields
        return $relevantForeignKeys === 2 && ($totalFields - $foreignKeyFields) <= 2;
    }

    /**
     * Create implicit many-to-many relations
     */
    protected function createImplicitManyToManyRelations(Schema $schema, string $table1, string $table2, string $pivotTable): void
    {
        // Mark pivot table
        $schema->tables->{$pivotTable}->pivot = true;

        // Clear existing relations for pivot table
        $schema->tables->{$pivotTable}->relations = new Mergeable();

        // Create many-to-many relation from table1 to table2
        $relation1 = new Relation();
        $relation1->type = 'manyToMany';
        $relation1->table = $table2;
        $relation1->pivot = $pivotTable;

        $schema->tables->{$table1}->relations->{$table2} = $relation1;

        // Create many-to-many relation from table2 to table1
        $relation2 = new Relation();
        $relation2->type = 'manyToMany';
        $relation2->table = $table1;
        $relation2->pivot = $pivotTable;

        $schema->tables->{$table2}->relations->{$table1} = $relation2;
    }

    /**
     * Find primary key field of a table
     */
    protected function findPrimaryKeyField(Table $table): ?string
    {
        foreach ($table->fields as $fieldName => $field) {
            if (isset($field->primary_key) && $field->primary_key) {
                return $fieldName;
            }
        }
        return null;
    }

    /**
     * Add hierarchical metadata to table
     */
    protected function addHierarchicalMetadata(Table $table, string $type, array $fields): void
    {
        if (!isset($table->metadata)) {
            $table->metadata = new \stdClass();
        }
        
        $table->metadata->hierarchical = [
            'type' => $type,
            'fields' => $fields
        ];
    }

    /**
     * Add value object metadata to table
     */
    protected function addValueObjectMetadata(Table $table, string $groupName, array $fields): void
    {
        if (!isset($table->metadata)) {
            $table->metadata = new \stdClass();
        }
        
        if (!isset($table->metadata->value_objects)) {
            $table->metadata->value_objects = [];
        }
        
        $table->metadata->value_objects[$groupName] = $fields;
    }
}
