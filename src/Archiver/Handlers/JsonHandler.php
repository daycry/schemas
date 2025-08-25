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

namespace Daycry\Schemas\Archiver\Handlers;

use Daycry\Schemas\Archiver\BaseArchiver;
use Daycry\Schemas\Archiver\ArchiverInterface;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Structures\Schema;

/**
 * JSON Archiver Handler
 * 
 * Archives schemas to JSON format for easy export/import
 */
class JsonHandler extends BaseArchiver implements ArchiverInterface
{
    /**
     * The target JSON file path
     *
     * @var string
     */
    protected $filePath;

    /**
     * Pretty print JSON output
     *
     * @var bool
     */
    protected $prettyPrint;

    /**
     * Constructor
     *
     * @param SchemasConfig $config
     * @param string        $filePath
     * @param bool          $prettyPrint
     */
    public function __construct(?SchemasConfig $config = null, string $filePath = 'schema.json', bool $prettyPrint = true)
    {
        parent::__construct($config);
        
        $this->filePath = $filePath;
        $this->prettyPrint = $prettyPrint;
    }

    /**
     * Archive schema to JSON format
     *
     * @param Schema $schema
     *
     * @return bool
     */
    public function archive(Schema $schema): bool
    {
        try {
            $data = $this->convertSchemaToArray($schema);
            
            $flags = JSON_THROW_ON_ERROR;
            if ($this->prettyPrint) {
                $flags |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
            }
            
            $json = json_encode($data, $flags);
            
            $directory = dirname($this->filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            return file_put_contents($this->filePath, $json) !== false;
            
        } catch (\JsonException $e) {
            $this->errors[] = 'JSON encoding failed: ' . $e->getMessage();
            return false;
        } catch (\Exception $e) {
            $this->errors[] = 'Archive failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Convert schema to array for JSON encoding
     */
    protected function convertSchemaToArray(Schema $schema): array
    {
        $data = [
            'schema' => [
                'version' => '1.0',
                'exported_at' => date('c'),
                'tables' => [],
            ]
        ];

        if ($schema->tables) {
            foreach ($schema->tables as $tableName => $table) {
                $tableData = [
                    'name' => $table->name,
                    'comment' => $table->comment,
                    'engine' => $table->engine,
                    'collation' => $table->collation,
                    'fields' => [],
                    'indexes' => [],
                    'foreign_keys' => [],
                    'relations' => []
                ];

                // Convert fields
                if ($table->fields) {
                    foreach ($table->fields as $fieldName => $field) {
                        $tableData['fields'][$fieldName] = [
                            'name' => $field->name,
                            'type' => $field->type,
                            'max_length' => $field->max_length,
                            'nullable' => $field->nullable,
                            'default' => $field->default,
                            'auto_increment' => $field->auto_increment,
                            'primary_key' => $field->primary_key,
                            'comment' => $field->comment,
                        ];
                    }
                }

                // Convert indexes
                if ($table->indexes) {
                    foreach ($table->indexes as $indexName => $index) {
                        $tableData['indexes'][$indexName] = [
                            'name' => $index->name,
                            'fields' => $index->fields,
                            'type' => $index->type,
                            'unique' => $index->unique,
                        ];
                    }
                }

                // Convert foreign keys
                if ($table->foreignKeys) {
                    foreach ($table->foreignKeys as $fkName => $foreignKey) {
                        $tableData['foreign_keys'][$fkName] = [
                            'constraint_name' => $foreignKey->constraint_name,
                            'column_name' => $foreignKey->column_name,
                            'foreign_table_name' => $foreignKey->foreign_table_name,
                            'foreign_column_name' => $foreignKey->foreign_column_name,
                            'on_delete' => $foreignKey->on_delete,
                            'on_update' => $foreignKey->on_update,
                        ];
                    }
                }

                // Convert relations
                if ($table->relations) {
                    foreach ($table->relations as $relationName => $relation) {
                        $tableData['relations'][$relationName] = [
                            'type' => $relation->type,
                            'table' => $relation->table,
                            'pivot' => $relation->pivot,
                            'field' => $relation->field,
                        ];
                    }
                }

                $data['schema']['tables'][$tableName] = $tableData;
            }
        }

        return $data;
    }

    /**
     * Load schema from JSON file
     */
    public function load(): ?Schema
    {
        if (!file_exists($this->filePath)) {
            $this->errors[] = "JSON file not found: {$this->filePath}";
            return null;
        }

        try {
            $content = file_get_contents($this->filePath);
            if ($content === false) {
                $this->errors[] = "Failed to read JSON file: {$this->filePath}";
                return null;
            }

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            
            return $this->convertArrayToSchema($data);
            
        } catch (\JsonException $e) {
            $this->errors[] = 'JSON decoding failed: ' . $e->getMessage();
            return null;
        } catch (\Exception $e) {
            $this->errors[] = 'Load failed: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Convert array data back to Schema object
     */
    protected function convertArrayToSchema(array $data): Schema
    {
        $schema = new Schema();

        if (!isset($data['schema']['tables'])) {
            return $schema;
        }

        foreach ($data['schema']['tables'] as $tableName => $tableData) {
            $table = new \Daycry\Schemas\Structures\Table($tableName);
            
            $table->comment = $tableData['comment'] ?? null;
            $table->engine = $tableData['engine'] ?? null;
            $table->collation = $tableData['collation'] ?? null;

            // Convert fields
            if (isset($tableData['fields'])) {
                foreach ($tableData['fields'] as $fieldName => $fieldData) {
                    $field = new \Daycry\Schemas\Structures\Field($fieldName);
                    $field->type = $fieldData['type'] ?? null;
                    $field->max_length = $fieldData['max_length'] ?? null;
                    $field->nullable = $fieldData['nullable'] ?? false;
                    $field->default = $fieldData['default'] ?? null;
                    $field->auto_increment = $fieldData['auto_increment'] ?? false;
                    $field->primary_key = $fieldData['primary_key'] ?? false;
                    $field->comment = $fieldData['comment'] ?? null;
                    
                    $table->fields->{$fieldName} = $field;
                }
            }

            // Convert indexes
            if (isset($tableData['indexes'])) {
                foreach ($tableData['indexes'] as $indexName => $indexData) {
                    $index = new \Daycry\Schemas\Structures\Index($indexName);
                    $index->fields = $indexData['fields'] ?? [];
                    $index->type = $indexData['type'] ?? null;
                    $index->unique = $indexData['unique'] ?? false;
                    
                    $table->indexes->{$indexName} = $index;
                }
            }

            // Convert foreign keys
            if (isset($tableData['foreign_keys'])) {
                foreach ($tableData['foreign_keys'] as $fkName => $fkData) {
                    $foreignKey = new \Daycry\Schemas\Structures\ForeignKey();
                    $foreignKey->constraint_name = $fkData['constraint_name'] ?? null;
                    $foreignKey->column_name = $fkData['column_name'] ?? null;
                    $foreignKey->foreign_table_name = $fkData['foreign_table_name'] ?? null;
                    $foreignKey->foreign_column_name = $fkData['foreign_column_name'] ?? null;
                    $foreignKey->on_delete = $fkData['on_delete'] ?? null;
                    $foreignKey->on_update = $fkData['on_update'] ?? null;
                    
                    $table->foreignKeys->{$fkName} = $foreignKey;
                }
            }

            // Convert relations
            if (isset($tableData['relations'])) {
                foreach ($tableData['relations'] as $relationName => $relationData) {
                    $relation = new \Daycry\Schemas\Structures\Relation();
                    $relation->type = $relationData['type'] ?? null;
                    $relation->table = $relationData['table'] ?? null;
                    $relation->pivot = $relationData['pivot'] ?? null;
                    $relation->field = $relationData['field'] ?? null;
                    
                    $table->relations->{$relationName} = $relation;
                }
            }

            $schema->tables->{$tableName} = $table;
        }

        return $schema;
    }

    /**
     * Export schema to JSON string
     */
    public function export(Schema $schema): string
    {
        $data = $this->convertSchemaToArray($schema);
        
        $flags = JSON_THROW_ON_ERROR;
        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;
        }
        
        return json_encode($data, $flags);
    }

    /**
     * Import schema from JSON string
     */
    public function import(string $json): ?Schema
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return $this->convertArrayToSchema($data);
        } catch (\JsonException $e) {
            $this->errors[] = 'JSON import failed: ' . $e->getMessage();
            return null;
        }
    }
}
