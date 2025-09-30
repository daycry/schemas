<?php
declare(strict_types=1);

/**
 * Example Addon: JSON File Archiver
 *
 * This file demonstrates how an external package (or your app code) can
 * introduce a new archive handler without modifying the core library.
 *
 * Production recommendation: place this in its own package namespace like
 * `Daycry\SchemasExportJson` and require it via Composer. Then instruct users
 * to append the handler to `$archiveHandlers` (either an existing mode or a new one).
 */

namespace Examples\Schemas\AddonExportJson;

use Daycry\Schemas\Archiver\ArchiverInterface;
use Daycry\Schemas\Archiver\BaseArchiver;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Structures\Schema;

final class JsonFileArchiver extends BaseArchiver implements ArchiverInterface
{
    private string $path;
    private bool $pretty;

    public function __construct(?SchemasConfig $config = null, string $path = 'writable/schema-export.json', bool $pretty = true)
    {
        parent::__construct($config);
        $this->path   = $path;
        $this->pretty = $pretty;
    }

    public function archive(Schema $schema): bool
    {
        try {
            $array  = $this->serialize($schema);
            $flags  = JSON_THROW_ON_ERROR | ($this->pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0);
            $json   = json_encode($array, $flags);

            $dir = \dirname($this->path);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            return file_put_contents($this->path, $json) !== false;
        } catch (\Throwable $e) {
            $this->errors[] = 'JSON export failed: ' . $e->getMessage();
            return false;
        }
    }

    private function serialize(Schema $schema): array
    {
        $out = [
            'version'     => '1.0',
            'exported_at' => date('c'),
            'tables'      => [],
        ];

        if ($schema->tables) {
            foreach ($schema->tables as $tableName => $table) {
                $t = [
                    'comment'      => $table->comment,
                    'engine'       => $table->engine,
                    'collation'    => $table->collation,
                    'fields'       => [],
                    'indexes'      => [],
                    'foreign_keys' => [],
                    'relations'    => [],
                ];

                if ($table->fields) {
                    foreach ($table->fields as $fieldName => $field) {
                        $t['fields'][$fieldName] = [
                            'type'          => $field->type,
                            'max_length'    => $field->max_length,
                            'nullable'      => $field->nullable,
                            'default'       => $field->default,
                            'auto_increment'=> $field->auto_increment,
                            'primary_key'   => $field->primary_key,
                            'comment'       => $field->comment,
                        ];
                    }
                }

                if ($table->indexes) {
                    foreach ($table->indexes as $idxName => $index) {
                        $t['indexes'][$idxName] = [
                            'fields' => $index->fields,
                            'type'   => $index->type,
                            'unique' => $index->unique,
                        ];
                    }
                }

                if ($table->foreignKeys) {
                    foreach ($table->foreignKeys as $fkName => $fk) {
                        $t['foreign_keys'][$fkName] = [
                            'column'      => $fk->column_name,
                            'ref_table'   => $fk->foreign_table_name,
                            'ref_column'  => $fk->foreign_column_name,
                            'on_delete'   => $fk->on_delete,
                            'on_update'   => $fk->on_update,
                        ];
                    }
                }

                if ($table->relations) {
                    foreach ($table->relations as $relName => $rel) {
                        $t['relations'][$relName] = [
                            'type'  => $rel->type,
                            'table' => $rel->table,
                            'pivot' => $rel->pivot,
                            'field' => $rel->field,
                        ];
                    }
                }

                $out['tables'][$tableName] = $t;
            }
        }

        return $out;
    }
}
