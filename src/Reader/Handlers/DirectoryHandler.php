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

use Daycry\Schemas\Reader\BaseReader;
use Daycry\Schemas\Structures\Schema;

/**
 * DirectoryHandler for reading schemas from directory
 *
 * Reads schema files from a specified directory
 */
class DirectoryHandler extends BaseReader
{
    /**
     * Read schema from directory
     */
    public function read(string $path): Schema
    {
        $schema = new Schema();
        
        // If path is just 'directory' use the default schemas directory
        if ($path === 'directory') {
            $path = $this->config->schemasDirectory;
        }
        
        if (!is_dir($path)) {
            return $schema;
        }
        
        // Scan directory for schema files
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            if (is_readable($file)) {
                $tableSchema = $this->readSchemaFile($file);
                if ($tableSchema) {
                    $schema->merge($tableSchema);
                }
            }
        }
        
        return $schema;
    }
    
    /**
     * Read individual schema file
     */
    private function readSchemaFile(string $file): ?Schema
    {
        try {
            // Include the file and expect it to return a schema array or object
            $data = include $file;
            
            if (is_array($data)) {
                return $this->arrayToSchema($data);
            }
            
            if ($data instanceof Schema) {
                return $data;
            }
            
        } catch (\Throwable $e) {
            // Log error if logging is enabled
            if ($this->config->logging['enabled'] ?? false) {
                log_message('error', 'Failed to read schema file: ' . $file . ' - ' . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Convert array data to Schema object
     */
    private function arrayToSchema(array $data): Schema
    {
        $schema = new Schema();
        
        foreach ($data as $tableName => $tableData) {
            if (is_array($tableData)) {
                // Convert table data to appropriate structures
                // This is a simplified implementation
                $schema->tables->{$tableName} = (object) $tableData;
            }
        }
        
        return $schema;
    }
}
