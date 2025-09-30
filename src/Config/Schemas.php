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

namespace Daycry\Schemas\Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\Schemas\Archiver\Handlers\CacheHandler as CacheArchiveHandler;
use Daycry\Schemas\Archiver\Handlers\JsonHandler as JsonArchiveHandler;
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;
use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;
use Daycry\Schemas\Drafter\Handlers\DirectoryHandlers\PhpHandler;
use Daycry\Schemas\Drafter\Handlers\ModelHandler;
use Daycry\Schemas\Reader\Handlers\CacheHandler as CacheReadHandler;
use Daycry\Schemas\Reader\Handlers\JsonHandler;

class Schemas extends BaseConfig
{
    /**
     * Default database group to use
     */
    public string $defaultGroup = 'default';

    /**
     * Tables to ignore when creating the schema
     */
    /**
     * @var list<string>
     */
    public array $ignoredTables = ['migrations'];

    /**
     * Specific tables to include (if set, only these tables will be processed)
     */
    /**
     * @var list<string>
     */
    public array $includedTables = [];

    /**
     * Table prefix to add/remove from table names
     */
    public string $tablePrefix = '';

    /**
     * Whether to continue instead of throwing exceptions
     */
    public bool $silent = true;

    /**
     * Cache configuration
     */
    /**
     * @var array{enabled:bool,handler:string,ttl:int,prefix:string}
     */
    public array $cache = [
        'enabled' => false,
        'handler' => 'file',
        'ttl'     => 3600,
        'prefix'  => 'schemas_',
    ];

    // Logging removed in minimal core (was: enabled, level)

    // Relationships simplified: single on/off flag
    public bool $relationships = true;

    // Validation removed entirely (previous flags: enabled, strict_mode)

    // Plugin system removed entirely (legacy placeholder removed)

    // ========================================
    // Legacy Configuration (for backward compatibility)
    // ========================================

    /**
     * Which tasks to automate when a schema is not available from the service
     */
    /**
     * @var array{draft:bool,archive:bool,read:bool}
     */
    public array $automate = [
        'draft'   => true,
        'archive' => true,
        'read'    => true,
    ];

    /**
     * Default handler used to return and read a schema
     */
    public string $readHandler = CacheReadHandler::class;

    /**
     * Read handlers for different file types and sources
     */
    /**
     * @var array<string,class-string>
     */
    public array $readHandlers = [
        'cache'     => CacheReadHandler::class,
        'directory' => \Daycry\Schemas\Reader\Handlers\DirectoryHandler::class,
        'php'       => \Daycry\Schemas\Reader\Handlers\PhpHandler::class,
        'json'      => JsonHandler::class,
    ];

    /**
     * Default handlers used to create a schema (order sensitive)
     * (Probably shouldn't change this unless you really know what you're doing)
     */
    /**
     * @var array<string,class-string>
     */
    public array $draftHandlers = [
        'database'  => DatabaseHandler::class,
        'model'     => ModelHandler::class,
        'directory' => DirectoryHandler::class,
    ];

    /**
     * Directory handlers for different file types
     */
    /**
     * @var array<string,class-string>
     */
    public array $directoryHandlers = [
        'php' => PhpHandler::class,
    ];

    /**
     * Path the directoryHandler should scan for schema files
     */
    public string $schemasDirectory = APPPATH . 'Schemas';

    /**
     * Default handlers to archive copies of the schema
     */
    /**
     * @var array<string,array<int,class-string>|class-string>
     */
    public array $archiveHandlers = [
        'cache' => CacheArchiveHandler::class,
        'json'  => JsonArchiveHandler::class,
    ];

    /**
     * Namespaces to ignore (mostly for ModelHandler)
     */
    /**
     * @var list<string>
     */
    public array $ignoredNamespaces = [
        'Tests\Support',
        'CodeIgniter\Commands\Generators',
    ];

    // Minimal core: remove environment/profile/override system
    /**
     * @var array{schema_diff_tool:bool,migration_generator:bool,debug:bool}
     */
    public array $development = [
        'schema_diff_tool'    => true,
        'migration_generator' => true,
        'debug'               => false,
    ];

    public function __construct()
    {
        parent::__construct();
    }

    // Minimal helper to fetch nested values from current object properties
    /**
     * Generic nested accessor.
     *
     * @template TDefault
     *
     * @param TDefault $default
     *
     * @return mixed|TDefault
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current  = $this;

        foreach ($segments as $seg) {
            if (is_object($current) && isset($current->{$seg})) {
                $current = $current->{$seg};

                continue;
            }
            if (is_array($current) && array_key_exists($seg, $current)) {
                $current = $current[$seg];

                continue;
            }

            return $default;
        }

        return $current;
    }

    // Removed export/import/snapshot/stats systems for minimal core

    // ========================================
    // Private Configuration Management Methods
    // ========================================

    /**
     * Get the compiled configuration for current environment
     */
    // getCompiledConfig removed; direct properties used

    /**
     * Initialize default configuration profiles
     */
    // Profiles removed

    /**
     * Initialize configuration validation rules
     */
    // Validation rules removed

    /**
     * Validate configuration against defined rules
     */
    // Configuration validation removed

    /**
     * Merge two configuration arrays
     */
    // mergeConfigurations removed

    /**
     * Merge configuration (helper for import)
     */
    // mergeConfiguration removed

    /**
     * Apply environment variables to configuration
     */
    // Environment variable overlay removed

    /**
     * Parse environment variable value
     */
    // parseEnvValue removed

    // Removed nested array helpers (get/set/has) in minimal core

    /**
     * Notify configuration change listeners
     */
    // notifyListeners removed

    /**
     * Count configuration keys recursively
     */
    // countConfigKeys removed
}
