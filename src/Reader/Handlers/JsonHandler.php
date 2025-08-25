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
 * JsonHandler for reading schemas from JSON files
 *
 * Reads schema from JSON files
 */
class JsonHandler extends BaseReader
{
    /**
     * Read schema from JSON file
     */
    public function read(string $path): Schema
    {
        $schema = new Schema();
        
        if (!is_file($path) || !is_readable($path)) {
            return $schema;
        }
        
        try {
            $content = file_get_contents($path);
            if ($content === false) {
                return $schema;
            }
            
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($this->config->logging['enabled'] ?? false) {
                    log_message('error', 'Invalid JSON in schema file: ' . $path . ' - ' . json_last_error_msg());
                }
                return $schema;
            }
            
            if (is_array($data)) {
                return $this->arrayToSchema($data);
            }
            
        } catch (\Throwable $e) {
            // Log error if logging is enabled
            if ($this->config->logging['enabled'] ?? false) {
                log_message('error', 'Failed to read JSON schema file: ' . $path . ' - ' . $e->getMessage());
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
