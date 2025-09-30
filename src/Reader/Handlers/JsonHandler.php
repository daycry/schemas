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
use Throwable;

/**
 * JsonHandler for reading schemas from JSON files
 *
 * Reads schema from JSON files
 */
/**
 * Final JSON reader handler.
 * Reads a JSON file and converts it to a Schema structure.
 */
final class JsonHandler extends BaseReader
{
    /**
     * Read schema from JSON file
     */
    public function read(string $path): Schema
    {
        $schema = new Schema();

        if (! is_file($path) || ! is_readable($path)) {
            return $schema;
        }

        try {
            $content = file_get_contents($path);
            if ($content === false) {
                return $schema;
            }

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $schema; // Invalid JSON; logging removed.
            }

            if (is_array($data)) {
                return $this->arrayToSchema($data);
            }
        } catch (Throwable $e) {
            // Logging removed; ignore exception and return empty schema.
        }

        return $schema;
    }

    /**
     * Convert array data to Schema object
     */
    /**
     * @param array<string,mixed> $data
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
