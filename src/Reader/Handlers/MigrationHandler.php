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

namespace Daycry\Schemas\Reader\Handlers;

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Reader\BaseReader;
use Daycry\Schemas\Reader\ReaderInterface;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\Index;
use Daycry\Schemas\Structures\ForeignKey;

/**
 * Migration Handler
 * 
 * Reads CodeIgniter 4 migration files to generate schemas
 */
class MigrationHandler extends BaseReader implements ReaderInterface
{
    /**
     * Migration files directory
     *
     * @var string
     */
    protected $migrationPath;

    /**
     * Loaded tables
     *
     * @var Mergeable|null
     */
    protected $tables;

    /**
     * Constructor
     *
     * @param SchemasConfig $config
     * @param string        $migrationPath
     */
    public function __construct(?SchemasConfig $config = null, string $migrationPath = null)
    {
        parent::__construct($config);
        
        $this->migrationPath = $migrationPath ?? APPPATH . 'Database/Migrations/';
        $this->tables = new Mergeable();
        $this->ready = is_dir($this->migrationPath);
    }

    /**
     * Get all tables
     */
    public function getTables(): ?Mergeable
    {
        return $this->tables;
    }

    /**
     * Fetch specified migration files
     *
     * @param array|string $migrations
     *
     * @return $this
     */
    public function fetch($migrations)
    {
        if (!$this->ensureReady()) {
            return $this;
        }

        if (is_string($migrations)) {
            $migrations = [$migrations];
        }

        foreach ($migrations as $migration) {
            $this->parseMigrationFile($migration);
        }

        return $this;
    }

    /**
     * Fetch all migration files
     *
     * @return $this
     */
    public function fetchAll()
    {
        if (!$this->ensureReady()) {
            return $this;
        }

        $files = glob($this->migrationPath . '*.php');
        
        if ($files === false) {
            return $this;
        }

        foreach ($files as $file) {
            $this->parseMigrationFile($file);
        }

        return $this;
    }

    /**
     * Parse a migration file
     */
    protected function parseMigrationFile(string $file): void
    {
        if (!file_exists($file)) {
            $file = $this->migrationPath . $file;
            if (!file_exists($file)) {
                return;
            }
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        // Parse table creations
        $this->parseCreateTable($content);
        
        // Parse table modifications
        $this->parseModifyTable($content);
        
        // Parse index creations
        $this->parseIndexes($content);
        
        // Parse foreign keys
        $this->parseForeignKeys($content);
    }

    /**
     * Parse CREATE TABLE statements from migration
     */
    protected function parseCreateTable(string $content): void
    {
        // Match createTable calls
        preg_match_all('/\$this->forge->createTable\s*\(\s*[\'"]([^\'\"]+)[\'"]/', $content, $matches);
        
        foreach ($matches[1] as $tableName) {
            if (!property_exists($this->tables, $tableName)) {
                $this->tables->{$tableName} = new Table($tableName);
            }
        }

        // Parse field definitions within createTable blocks
        preg_match_all('/\$this->forge->addField\s*\(\s*\[([^\]]+)\]/', $content, $fieldMatches);
        
        foreach ($fieldMatches[1] as $fieldsBlock) {
            $this->parseFieldBlock($fieldsBlock);
        }
    }

    /**
     * Parse field definitions
     */
    protected function parseFieldBlock(string $fieldsBlock): void
    {
        // This is a simplified parser - in reality, you'd need a more sophisticated approach
        preg_match_all('/[\'"]([^\'\"]+)[\'\"]\s*=>\s*\[([^\]]+)\]/', $fieldsBlock, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $fieldName = $matches[1][$i];
            $fieldDef = $matches[2][$i];
            
            $field = new Field($fieldName);
            
            // Parse field properties
            if (preg_match('/[\'"]type[\'\"]\s*=>\s*[\'"]([^\'\"]+)/', $fieldDef, $typeMatch)) {
                $field->type = $typeMatch[1];
            }
            
            if (preg_match('/[\'"]constraint[\'\"]\s*=>\s*(\d+)/', $fieldDef, $constraintMatch)) {
                $field->max_length = (int) $constraintMatch[1];
            }
            
            if (preg_match('/[\'"]null[\'\"]\s*=>\s*(true|false)/', $fieldDef, $nullMatch)) {
                $field->nullable = $nullMatch[1] === 'true';
            }
            
            if (preg_match('/[\'"]auto_increment[\'\"]\s*=>\s*true/', $fieldDef)) {
                $field->auto_increment = true;
            }
            
            if (preg_match('/[\'"]default[\'\"]\s*=>\s*[\'"]([^\'\"]+)/', $fieldDef, $defaultMatch)) {
                $field->default = $defaultMatch[1];
            }
            
            // Store field - we'd need to associate it with the correct table
            // For simplicity, we'll store it in a temporary way
        }
    }

    /**
     * Parse table modifications
     */
    protected function parseModifyTable(string $content): void
    {
        // Parse modifyColumn, addColumn, dropColumn calls
        preg_match_all('/\$this->forge->(addColumn|modifyColumn|dropColumn)\s*\(\s*[\'"]([^\'\"]+)[\'"]/', $content, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $operation = $matches[1][$i];
            $tableName = $matches[2][$i];
            
            if (!property_exists($this->tables, $tableName)) {
                $this->tables->{$tableName} = new Table($tableName);
            }
            
            // Further parsing would be needed for specific column changes
        }
    }

    /**
     * Parse index creations
     */
    protected function parseIndexes(string $content): void
    {
        // Parse addKey calls
        preg_match_all('/\$this->forge->addKey\s*\(\s*[\'"]([^\'\"]+)[\'"](?:\s*,\s*(true|false))?\s*\)/', $content, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $fieldName = $matches[1][$i];
            $isPrimary = isset($matches[2][$i]) && $matches[2][$i] === 'true';
            
            // Would need to associate with correct table and create Index objects
        }
    }

    /**
     * Parse foreign key constraints
     */
    protected function parseForeignKeys(string $content): void
    {
        // Parse addForeignKey calls
        preg_match_all('/\$this->forge->addForeignKey\s*\(\s*[\'"]([^\'\"]+)[\'\"]\s*,\s*[\'"]([^\'\"]+)[\'\"]\s*,\s*[\'"]([^\'\"]+)[\'"]/', $content, $matches);
        
        for ($i = 0; $i < count($matches[1]); $i++) {
            $localField = $matches[1][$i];
            $foreignTable = $matches[2][$i];
            $foreignField = $matches[3][$i];
            
            // Would create ForeignKey objects and associate with tables
        }
    }

    /**
     * Return count of tables
     */
    public function count(): int
    {
        return $this->tables === null ? 0 : count($this->tables);
    }

    /**
     * Return tables for iteration
     */
    public function getIterator(): Mergeable
    {
        return $this->fetchAll()->tables;
    }

    /**
     * Magic getter for table access
     */
    public function __get(string $name)
    {
        if ($this->tables && property_exists($this->tables, $name)) {
            return $this->tables->{$name};
        }
        return null;
    }

    /**
     * Magic isset for table checking
     */
    public function __isset(string $name): bool
    {
        return $this->tables && property_exists($this->tables, $name);
    }
}
