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
use Daycry\Schemas\Plugins\PluginManager;
use Daycry\Schemas\Events\SchemaEvents;
use Daycry\Schemas\Events\BaseEvent;
use Daycry\Schemas\Async\AsyncManager;

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
     * Plugin manager instance.
     */
    protected ?PluginManager $pluginManager = null;

    /**
     * Async manager instance.
     */
    protected ?AsyncManager $asyncManager = null;

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

        // Initialize plugin system if enabled
        if ($this->config->plugins['enabled'] ?? false) {
            $this->initializePluginSystem();
        }
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
    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Reset the current schema and errors
     *
     * @return $this
     */
    public function reset()
    {
        $this->schema = null;
        $this->errors = [];

        return $this;
    }

    /**
     * Generates a new schema from the current handlers and merges it into the current schema.
     *
     * @param string|string[]|null $handlers Handler key name or array of handler keys
     *
     * @return $this
     */
    public function draft(array|string|null $handlers = null)
    {
        $this->timer->start('schema draft');
        
        // Emit before draft event
        $this->emitEvent(SchemaEvents::SCHEMA_BEFORE_DRAFT, [
            'handlers' => $handlers,
            'current_schema' => $this->schema
        ]);

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
            if (!is_string($handler)) {
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

            $schema = $class->draft($this->schema->tables);
            $this->schema->merge($schema);
        }

        $this->timer->stop('schema draft');

        // Emit after draft event
        $this->emitEvent(SchemaEvents::SCHEMA_AFTER_DRAFT, [
            'handlers' => $handlers,
            'resulting_schema' => $this->schema
        ]);

        return $this;
    }

    /**
     * Archives the current schema to all archivers in the designated mode.
     *
     * @param string|array $mode Archive mode, corresponds to $archiveHandlers index, or array of archiver instances
     *
     * @throws SchemasException
     *
     * @return $this
     */
    public function archive(string|array $mode = 'cache')
    {
        $this->timer->start('schema archive');

        // Must have a schema to archive
        if (empty($this->schema)) {
            throw SchemasException::forMissingSchema();
        }

        // Emit before archive event
        $this->emitEvent(SchemaEvents::SCHEMA_BEFORE_ARCHIVE, [
            'mode' => $mode,
            'schema' => $this->schema
        ]);

        // Handle different input types
        if (is_array($mode)) {
            // Array of archiver instances
            foreach ($mode as $archiver) {
                if (! $archiver->archive($this->schema)) {
                    $this->errors = array_merge($this->errors, $archiver->getErrors());
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
            if (!is_array($handlers)) {
                $handlers = [$handlers];
            }
            
            foreach ($handlers as $className) {
                $class = new $className($this->config);

                if (! $class->archive($this->schema)) {
                    $this->errors = array_merge($this->errors, $class->getErrors());
                }
            }
        }

        $this->timer->stop('schema archive');

        // Emit after archive event
        $this->emitEvent(SchemaEvents::SCHEMA_AFTER_ARCHIVE, [
            'mode' => $mode,
            'schema' => $this->schema
        ]);

        return $this;
    }

    /**
     * Read schema from the designated path(s).
     *
     * @param string|string[] $path Path to files or folders to read
     *
     * @return $this
     */
    public function read(array|string $path)
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

    /**
     * Initialize the plugin system
     */
    protected function initializePluginSystem(): void
    {
        $this->pluginManager = new PluginManager(
            service('logger'),
            '1.0.0' // Library version
        );

        // Add configured discovery paths
        foreach ($this->config->plugins['discovery_paths'] ?? [] as $path) {
            if (is_dir($path)) {
                $this->pluginManager->addDiscoveryPath($path);
            }
        }

        // Auto-discover plugins if enabled
        if ($this->config->plugins['auto_discover'] ?? true) {
            $this->pluginManager->discoverPlugins();
        }

        // Auto-load plugins if enabled
        if ($this->config->plugins['auto_load'] ?? true) {
            $this->pluginManager->loadPlugins();
        }

        // Initialize async manager if enabled
        $this->initializeAsyncManager();
    }

    /**
     * Initialize async manager for background operations
     */
    protected function initializeAsyncManager(): void
    {
        if (!($this->config->async['enabled'] ?? false)) {
            return;
        }

        $asyncConfig = $this->config->async ?? [];
        $this->asyncManager = new AsyncManager($this, $asyncConfig);
    }

    /**
     * Emit an event to registered plugins
     */
    protected function emitEvent(string $eventName, array $data = []): ?BaseEvent
    {
        if ($this->pluginManager === null) {
            return null;
        }

        return $this->pluginManager->emit($eventName, $data);
    }

    /**
     * Get the plugin manager instance
     */
    public function getPluginManager(): ?PluginManager
    {
        return $this->pluginManager;
    }

    /**
     * Get the async manager instance
     */
    public function getAsyncManager(): ?AsyncManager
    {
        return $this->asyncManager;
    }

    /**
     * Process schema reading asynchronously
     */
    public function readAsync($tables, array $options = [], ?callable $callback = null): ?string
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled. Enable it in configuration.');
        }

        return $this->asyncManager->processAsync(
            'read',
            $tables,
            $options,
            $callback
        );
    }

    /**
     * Process schema archiving asynchronously
     */
    public function archiveAsync($schemas, array $options = [], ?callable $callback = null): ?string
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled. Enable it in configuration.');
        }

        return $this->asyncManager->processAsync(
            'archive',
            $schemas,
            $options,
            $callback
        );
    }

    /**
     * Process schema drafting asynchronously
     */
    public function draftAsync($schemas, array $options = [], ?callable $callback = null): ?string
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled. Enable it in configuration.');
        }

        return $this->asyncManager->processAsync(
            'draft',
            $schemas,
            $options,
            $callback
        );
    }

    /**
     * Validate schemas asynchronously
     */
    public function validateAsync($schemas, array $options = [], ?callable $callback = null): ?string
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled. Enable it in configuration.');
        }

        return $this->asyncManager->processAsync(
            'validate',
            $schemas,
            $options,
            $callback
        );
    }

    /**
     * Compare schemas asynchronously
     */
    public function compareAsync(array $sourceSchema, array $targetSchema, array $options = [], ?callable $callback = null): ?string
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled. Enable it in configuration.');
        }

        $data = [
            'source' => $sourceSchema,
            'target' => $targetSchema,
        ];

        return $this->asyncManager->processAsync(
            'compare',
            $data,
            $options,
            $callback
        );
    }

    /**
     * Merge schemas asynchronously
     */
    public function mergeAsync(array $schemas, array $options = [], ?callable $callback = null): ?string
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled. Enable it in configuration.');
        }

        $data = ['schemas' => $schemas];

        return $this->asyncManager->processAsync(
            'merge',
            $data,
            $options,
            $callback
        );
    }

    /**
     * Get status of an async job
     */
    public function getJobStatus(string $jobId): ?array
    {
        if (!$this->asyncManager) {
            return null;
        }

        try {
            return $this->asyncManager->getJobStatus($jobId);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Cancel an async job
     */
    public function cancelJob(string $jobId): bool
    {
        if (!$this->asyncManager) {
            return false;
        }

        return $this->asyncManager->cancelJob($jobId);
    }

    /**
     * Wait for an async job to complete
     */
    public function waitForJob(string $jobId, int $timeout = 0)
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled.');
        }

        return $this->asyncManager->waitForJob($jobId, $timeout);
    }

    /**
     * Get result of a completed async job
     */
    public function getJobResult(string $jobId)
    {
        if (!$this->asyncManager) {
            throw new SchemasException('Async manager is not enabled.');
        }

        return $this->asyncManager->getJobResult($jobId);
    }
}
