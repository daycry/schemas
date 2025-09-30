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

use Daycry\Schemas\Archiver\ArchiverInterface;
use Daycry\Schemas\Archiver\BaseArchiver;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\Field as StructureField;
use Daycry\Schemas\Structures\ForeignKey;
use Daycry\Schemas\Structures\ForeignKey as StructureForeignKey;
use Daycry\Schemas\Structures\Index;
use Daycry\Schemas\Structures\Index as StructureIndex;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Table as StructureTable;
use DOMDocument;
use DOMElement;
use Exception;

/**
 * XML Archiver Handler
 *
 * Archives schemas to XML format compatible with Doctrine DBAL
 */
class XmlHandler extends BaseArchiver implements ArchiverInterface
{
    /**
     * The target XML file path
     *
     * @var string
     */
    protected $filePath;

    /**
     * Format XML output
     *
     * @var bool
     */
    protected $formatOutput;

    /**
     * Constructor
     */
    public function __construct(?SchemasConfig $config = null, string $filePath = 'schema.xml', bool $formatOutput = true)
    {
        parent::__construct($config);

        $this->filePath     = $filePath;
        $this->formatOutput = $formatOutput;
    }

    /**
     * Archive schema to XML format
     */
    public function archive(Schema $schema): bool
    {
        try {
            $xml = $this->convertSchemaToXml($schema);

            $directory = dirname($this->filePath);
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            return $xml->save($this->filePath) !== false;
        } catch (Exception $e) {
            $this->errors[] = 'XML archive failed: ' . $e->getMessage();

            return false;
        }
    }

    /**
     * Convert schema to XML document
     */
    protected function convertSchemaToXml(Schema $schema): DOMDocument
    {
        $xml               = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = $this->formatOutput;

        // Create root element
        $database = $xml->createElement('database');
        $database->setAttribute('name', 'schema');
        $database->setAttribute('exported_at', date('c'));
        $xml->appendChild($database);

        foreach ($schema->tables as $table) {
            $this->addTableToXml($xml, $database, $table);
        }

        return $xml;
    }

    /**
     * Add table to XML document
     */
    protected function addTableToXml(DOMDocument $xml, DOMElement $parent, StructureTable $table): void
    {
        $tableElement = $xml->createElement('table');
        $tableElement->setAttribute('name', $table->name);

        if ($table->comment) {
            $tableElement->setAttribute('comment', $table->comment);
        }
        if ($table->engine) {
            $tableElement->setAttribute('engine', $table->engine);
        }
        if ($table->collation) {
            $tableElement->setAttribute('collation', $table->collation);
        }

        // Add fields
        foreach ($table->fields as $field) {
            $this->addFieldToXml($xml, $tableElement, $field);
        }

        // Add indexes
        foreach ($table->indexes as $index) {
            $this->addIndexToXml($xml, $tableElement, $index);
        }

        // Add foreign keys
        foreach ($table->foreignKeys as $foreignKey) {
            $this->addForeignKeyToXml($xml, $tableElement, $foreignKey);
        }

        $parent->appendChild($tableElement);
    }

    /**
     * Add field to XML table element
     */
    protected function addFieldToXml(DOMDocument $xml, DOMElement $table, StructureField $field): void
    {
        $column = $xml->createElement('column');
        $column->setAttribute('name', $field->name);
        $column->setAttribute('type', $field->type);

        if ($field->max_length !== null) {
            $column->setAttribute('length', (string) $field->max_length);
        }

        $column->setAttribute('notnull', $field->nullable ? 'false' : 'true');

        if ($field->default !== null) {
            $column->setAttribute('default', $field->default);
        }

        if ($field->auto_increment) {
            $column->setAttribute('autoincrement', 'true');
        }

        if ($field->comment) {
            $column->setAttribute('comment', $field->comment);
        }

        $table->appendChild($column);
    }

    /**
     * Add index to XML table element
     */
    protected function addIndexToXml(DOMDocument $xml, DOMElement $table, StructureIndex $index): void
    {
        if ($index->type === 'PRIMARY') {
            // Primary key
            $pk = $xml->createElement('primary-key');
            if (is_array($index->fields)) {
                foreach ($index->fields as $field) {
                    $column = $xml->createElement('column');
                    $column->setAttribute('name', $field);
                    $pk->appendChild($column);
                }
            }
            $table->appendChild($pk);
        } else {
            // Regular index
            $indexElement = $xml->createElement('index');
            $indexElement->setAttribute('name', $index->name);

            if ($index->unique) {
                $indexElement->setAttribute('unique', 'true');
            }

            if (is_array($index->fields)) {
                foreach ($index->fields as $field) {
                    $column = $xml->createElement('column');
                    $column->setAttribute('name', $field);
                    $indexElement->appendChild($column);
                }
            }

            $table->appendChild($indexElement);
        }
    }

    /**
     * Add foreign key to XML table element
     */
    protected function addForeignKeyToXml(DOMDocument $xml, DOMElement $table, StructureForeignKey $foreignKey): void
    {
        $fk = $xml->createElement('foreign-key');

        if ($foreignKey->constraint_name) {
            $fk->setAttribute('name', $foreignKey->constraint_name);
        }

        $fk->setAttribute('foreignTable', $foreignKey->foreign_table_name);

        if ($foreignKey->on_delete) {
            $fk->setAttribute('onDelete', strtoupper($foreignKey->on_delete));
        }

        if ($foreignKey->on_update) {
            $fk->setAttribute('onUpdate', strtoupper($foreignKey->on_update));
        }

        // Local column
        $localColumn = $xml->createElement('reference');
        $localColumn->setAttribute('local', $foreignKey->column_name);
        $localColumn->setAttribute('foreign', $foreignKey->foreign_column_name);
        $fk->appendChild($localColumn);

        $table->appendChild($fk);
    }

    /**
     * Load schema from XML file
     */
    public function load(): ?Schema
    {
        if (! file_exists($this->filePath)) {
            $this->errors[] = "XML file not found: {$this->filePath}";

            return null;
        }

        try {
            $xml = new DOMDocument();

            if (! $xml->load($this->filePath)) {
                $this->errors[] = "Failed to parse XML file: {$this->filePath}";

                return null;
            }

            return $this->convertXmlToSchema($xml);
        } catch (Exception $e) {
            $this->errors[] = 'XML load failed: ' . $e->getMessage();

            return null;
        }
    }

    /**
     * Convert XML document to Schema object
     */
    protected function convertXmlToSchema(DOMDocument $xml): Schema
    {
        $schema = new Schema();

        $tables = $xml->getElementsByTagName('table');

        foreach ($tables as $tableNode) {
            // Ensure we have a DOMElement
            if (! ($tableNode instanceof DOMElement)) {
                continue;
            }

            $tableElement = $tableNode;
            $table        = new Table($tableElement->getAttribute('name'));

            if ($tableElement->hasAttribute('comment')) {
                $table->comment = $tableElement->getAttribute('comment');
            }
            if ($tableElement->hasAttribute('engine')) {
                $table->engine = $tableElement->getAttribute('engine');
            }
            if ($tableElement->hasAttribute('collation')) {
                $table->collation = $tableElement->getAttribute('collation');
            }

            // Load columns
            $columns = $tableElement->getElementsByTagName('column');

            foreach ($columns as $columnNode) {
                if (! ($columnNode instanceof DOMElement)) {
                    continue;
                }
                $columnElement = $columnNode;

                $field       = new Field($columnElement->getAttribute('name'));
                $field->type = $columnElement->getAttribute('type');

                if ($columnElement->hasAttribute('length')) {
                    $field->max_length = (int) $columnElement->getAttribute('length');
                }

                $field->nullable = $columnElement->getAttribute('notnull') !== 'true';

                if ($columnElement->hasAttribute('default')) {
                    $field->default = $columnElement->getAttribute('default');
                }

                if ($columnElement->hasAttribute('autoincrement')) {
                    $field->auto_increment = $columnElement->getAttribute('autoincrement') === 'true';
                }

                if ($columnElement->hasAttribute('comment')) {
                    $field->comment = $columnElement->getAttribute('comment');
                }

                $table->fields->{$field->name} = $field;
            }

            // Load indexes
            $indexes = $tableElement->getElementsByTagName('index');

            foreach ($indexes as $indexNode) {
                if (! ($indexNode instanceof DOMElement)) {
                    continue;
                }
                $indexElement = $indexNode;

                $index         = new Index($indexElement->getAttribute('name'));
                $index->unique = $indexElement->getAttribute('unique') === 'true';

                $indexColumns = $indexElement->getElementsByTagName('column');

                foreach ($indexColumns as $indexColumnNode) {
                    if (! ($indexColumnNode instanceof DOMElement)) {
                        continue;
                    }
                    $indexColumn     = $indexColumnNode;
                    $index->fields[] = $indexColumn->getAttribute('name');
                }

                $table->indexes->{$index->name} = $index;
            }

            // Load primary keys
            $primaryKeys = $tableElement->getElementsByTagName('primary-key');

            foreach ($primaryKeys as $pkNode) {
                if (! ($pkNode instanceof DOMElement)) {
                    continue;
                }
                $pkElement = $pkNode;

                $index       = new Index('PRIMARY');
                $index->type = 'PRIMARY';

                $pkColumns = $pkElement->getElementsByTagName('column');

                foreach ($pkColumns as $pkColumnNode) {
                    if (! ($pkColumnNode instanceof DOMElement)) {
                        continue;
                    }
                    $pkColumn        = $pkColumnNode;
                    $index->fields[] = $pkColumn->getAttribute('name');

                    // Mark field as primary key
                    $fieldName = $pkColumn->getAttribute('name');
                    if (property_exists($table->fields, $fieldName)) {
                        $table->fields->{$fieldName}->primary_key = true;
                    }
                }

                $table->indexes->PRIMARY = $index;
            }

            // Load foreign keys
            $foreignKeys = $tableElement->getElementsByTagName('foreign-key');

            foreach ($foreignKeys as $fkNode) {
                if (! ($fkNode instanceof DOMElement)) {
                    continue;
                }
                $fkElement = $fkNode;

                $foreignKey = new ForeignKey();

                if ($fkElement->hasAttribute('name')) {
                    $foreignKey->constraint_name = $fkElement->getAttribute('name');
                }

                $foreignKey->foreign_table_name = $fkElement->getAttribute('foreignTable');

                if ($fkElement->hasAttribute('onDelete')) {
                    $foreignKey->on_delete = strtolower($fkElement->getAttribute('onDelete'));
                }

                if ($fkElement->hasAttribute('onUpdate')) {
                    $foreignKey->on_update = strtolower($fkElement->getAttribute('onUpdate'));
                }

                $references = $fkElement->getElementsByTagName('reference');
                if ($references->length > 0) {
                    $referenceNode = $references->item(0);
                    if ($referenceNode instanceof DOMElement) {
                        $reference                       = $referenceNode;
                        $foreignKey->column_name         = $reference->getAttribute('local');
                        $foreignKey->foreign_column_name = $reference->getAttribute('foreign');
                    }
                }

                $fkName                        = $foreignKey->constraint_name ?: 'fk_' . $foreignKey->column_name;
                $table->foreignKeys->{$fkName} = $foreignKey;
            }

            $schema->tables->{$table->name} = $table;
        }

        return $schema;
    }

    /**
     * Export schema to XML string
     */
    public function export(Schema $schema): string
    {
        $xml    = $this->convertSchemaToXml($schema);
        $result = $xml->saveXML();

        return $result === false ? '' : $result;
    }

    /**
     * Import schema from XML string
     */
    public function import(string $xmlString): ?Schema
    {
        try {
            $xml = new DOMDocument();

            if (! $xml->loadXML($xmlString)) {
                $this->errors[] = 'Failed to parse XML string';

                return null;
            }

            return $this->convertXmlToSchema($xml);
        } catch (Exception $e) {
            $this->errors[] = 'XML import failed: ' . $e->getMessage();

            return null;
        }
    }
}
