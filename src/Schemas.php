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

namespace Daycry\Schemas;

use CodeIgniter\Debug\Timer;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Exceptions\SchemasException;
use Daycry\Schemas\Structures\Schema;

// Events removed in minimal core

class Schemas
{
    /**
     * The current config.
     */
    protected SchemasConfig $config;

    /**
     * The current schema.
     */
    protected ?Schema $schema;

    /**
     * The timer service for benchmarking.
     */
    protected Timer $timer;

    /**
     * Array of error messages assigned on failure.
     *
     * @var list<string>
     */
    protected array $errors = [];

    /**
     * Initiates the library.
     */
    public function __construct(SchemasConfig $config, ?Schema $schema = null)
    {
        $this->config = $config;

        // Store initial schema
        $this->schema = $schema;

        // Grab the Timer service for benchmarking
        $this->timer = service('timer');

        // Plugin system removed in core simplification
    }

    /**
     * Return and clear any error messages.
     *
     * @return list<string>
     */
    public function getErrors(): array
    {
        $tmpErrors    = $this->errors;
        $this->errors = [];

        return $tmpErrors;
    }

    /**
     * Set the current schema
     *
     * @param Schema $schema The schema to set
     *
     * @return $this
     */
    public function setSchema(Schema $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Reset the current schema and errors
     *
     * @return $this
     */
    public function reset(): static
    {
        $this->schema = null;
        $this->errors = [];

        return $this;
    }

    /**
     * Generates a new schema from the current handlers and merges it into the current schema.
     *
     * @param list<string>|string|null $handlers Handler key name or array of handler keys
     *
     * @return $this
     */
    public function draft(array|string|null $handlers = null): static
    {
        $this->timer->start('schema draft');

        // (Events removed)

        if (empty($handlers)) {
            $handlers = array_keys($this->config->draftHandlers);
        }

        // Wrap singletons
        if (! is_array($handlers)) {
            $handlers = [$handlers];
        }

        $this->schema ??= new Schema();

        // Read in the schema from each handler
        foreach ($handlers as $key => $handler) {
            // Ensure handler is a string before using it as array key
            if (! is_string($handler)) {
                continue;
            }

            // Check if handler is a key in draftHandlers or already a class name
            if (array_key_exists($handler, $this->config->draftHandlers)) {
                $className = $this->config->draftHandlers[$handler];
            } elseif (class_exists($handler)) {
                $className = $handler;
            } else {
                // Skip invalid handlers
                continue;
            }

            $class = new $className($this->config);
            if (! method_exists($class, 'draft')) {
                continue; // Not a drafter-like handler
            }
            $schema = $class->draft();
            if ($schema instanceof Schema) {
                $this->schema->merge($schema);
            }
        }

        $this->timer->stop('schema draft');

        // (Events removed)

        return $this;
    }

    /**
     * Archives the current schema to all archivers in the designated mode.
     *
     * @param array<int,object>|string $mode Archive mode, corresponds to $archiveHandlers index, or array of archiver instances
     *
     * @throws SchemasException
     */
    public function archive(array|string $mode = 'cache'): static
    {
        $this->timer->start('schema archive');

        // Must have a schema to archive
        if (empty($this->schema)) {
            throw SchemasException::forMissingSchema();
        }

        // (Events removed)

        // Handle different input types
        if (is_array($mode)) {
            // Array of archiver instances
            foreach ($mode as $archiver) {
                if (is_object($archiver) && method_exists($archiver, 'archive')) {
                    if (! $archiver->archive($this->schema) && method_exists($archiver, 'getErrors')) {
                        $this->errors = array_merge($this->errors, $archiver->getErrors());
                    }
                }
            }
        } else {
            // String mode - use configured handlers
            // Verify the requested archive mode
            if (! array_key_exists($mode, $this->config->archiveHandlers)) {
                throw SchemasException::forMissingArchiveHandler($mode);
            }

            // Archive to each handler in the mode
            $handlers = $this->config->archiveHandlers[$mode];
            // If handlers is not an array, convert it to array for consistency
            if (! is_array($handlers)) {
                $handlers = [$handlers];
            }

            foreach ($handlers as $className) {
                $class = new $className($this->config);
                if (! method_exists($class, 'archive')) {
                    continue;
                }
                if (! $class->archive($this->schema) && method_exists($class, 'getErrors')) {
                    $this->errors = array_merge($this->errors, $class->getErrors());
                }
            }
        }

        $this->timer->stop('schema archive');

        // (Events removed)

        return $this;
    }

    /**
     * Read schema from the designated path(s).
     *
     * @param list<string>|string $path Path to files or folders to read
     *
     * @return $this
     */
    public function read(array|string $path): static
    {
        $this->timer->start('schema read');

        // Wrap singletons
        if (! is_array($path)) {
            $path = [$path];
        }

        $this->schema ??= new Schema();

        // Read each path via the matching Reader
        foreach ($path as $onePath) {
            $reader = $this->readerFromPath($onePath);
            $schema = $reader->read($onePath);
            $this->schema->merge($schema);
        }

        $this->timer->stop('schema read');

        return $this;
    }

    /**
     * Get the current schema.
     */
    public function get(): ?Schema
    {
        return $this->schema;
    }

    /**
     * Returns an appropriate Reader based on the given file or directory.
     */
    protected function readerFromPath(string $path): mixed
    {
        $extension = is_file($path) ? pathinfo($path, PATHINFO_EXTENSION) : 'directory';

        if (! isset($this->config->readHandlers[$extension])) {
            throw SchemasException::forMissingReadHandler($extension);
        }

        $className = $this->config->readHandlers[$extension];

        return new $className($this->config);
    }

    // Plugin system removed; no plugin manager or event emission
}
