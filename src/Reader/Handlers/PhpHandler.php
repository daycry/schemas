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
 * PhpHandler for reading schemas from PHP files
 *
 * Reads schema from PHP files that return array or Schema objects
 */
class PhpHandler extends BaseReader
{
    /**
     * Read schema from PHP file
     */
    public function read(string $path): Schema
    {
        $schema = new Schema();
        
        if (!is_file($path) || !is_readable($path)) {
            return $schema;
        }
        
        try {
            // Include the PHP file and expect it to return schema data
            $data = include $path;
            
            if (is_array($data)) {
                return $this->arrayToSchema($data);
            }
            
            if ($data instanceof Schema) {
                return $data;
            }
            
        } catch (\Throwable $e) {
            // Log error if logging is enabled
            if ($this->config->logging['enabled'] ?? false) {
                log_message('error', 'Failed to read PHP schema file: ' . $path . ' - ' . $e->getMessage());
            }
        }
        
        return $schema;
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
                $schema->tables->{$tableName} = (object) $tableData;
            }
        }
        
        return $schema;
    }
}
