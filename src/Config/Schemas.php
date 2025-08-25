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
use Daycry\Schemas\Config\ConfigEnvironment;
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;
use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;
use Daycry\Schemas\Drafter\Handlers\DirectoryHandlers\PhpHandler;
use Daycry\Schemas\Drafter\Handlers\ModelHandler;
use Daycry\Schemas\Reader\Handlers\CacheHandler as CacheReadHandler;

class Schemas extends BaseConfig
{
    /**
     * Default database group to use
     */
    public string $defaultGroup = 'default';

    /**
     * Tables to ignore when creating the schema
     */
    public array $ignoredTables = ['migrations'];

    /**
     * Specific tables to include (if set, only these tables will be processed)
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
     * Enable schema validation features
     */
    public bool $enableValidation = false;

    /**
     * Enable performance analysis features
     */
    public bool $enablePerformanceAnalysis = false;

    /**
     * Enable intelligent caching with versioning and tags
     */
    public bool $enableIntelligentCache = false;

    /**
     * Enable automatic relationship detection
     */
    public bool $enableRelationDetection = true;

    /**
     * Cache configuration
     */
    public array $cache = [
        'enabled' => false,
        'handler' => 'file',
        'ttl' => 3600,
        'prefix' => 'schemas_',
        'tags' => ['schemas'],
        'versioning' => false,
        'compression' => false
    ];

    /**
     * Logging configuration
     */
    public array $logging = [
        'enabled' => false,
        'level' => 'info',
        'channels' => ['file'],
        'performance_metrics' => true,
        'query_logging' => false
    ];

    /**
     * Performance analysis settings
     */
    public array $performance = [
        'enabled' => false,
        'analysis_depth' => 'full',
        'score_weights' => [
            'indexes' => 0.3,
            'foreign_keys' => 0.2,
            'data_types' => 0.2,
            'table_structure' => 0.15,
            'query_patterns' => 0.15
        ],
        'recommendations' => true,
        'auto_optimize' => false
    ];

    /**
     * Relationship detection settings
     */
    public array $relationships = [
        'enabled' => true,
        'detect_polymorphic' => true,
        'detect_self_referencing' => true,
        'detect_many_to_many' => true,
        'detect_hierarchical' => true,
        'naming_conventions' => [
            'foreign_key_suffix' => '_id',
            'pivot_table_pattern' => '{table1}_{table2}',
            'polymorphic_type_suffix' => '_type',
            'polymorphic_id_suffix' => '_id'
        ]
    ];

    /**
     * Validation settings
     */
    public array $validation = [
        'enabled' => false,
        'strict_mode' => false,
        'rules' => [
            'circular_references' => true,
            'foreign_key_consistency' => true,
            'data_type_validation' => true,
            'index_validation' => true,
            'constraint_validation' => true,
            'naming_conventions' => false
        ],
        'auto_fix' => false,
        'custom_rules' => []
    ];

    /**
     * Advanced features
     */
    public array $advanced = [
        'schema_versioning' => false,
        'migration_support' => false,
        'backup_schemas' => false,
        'compression' => false,
        'encryption' => false
    ];

    /**
     * Plugin system configuration
     */
    public array $plugins = [
        'enabled' => true,
        'auto_discovery' => true,
        'discovery_paths' => [
            APPPATH . 'Plugins/Schemas',
            APPPATH . 'ThirdParty/SchemasPlugins',
        ],
        'auto_load' => [
            // Plugin class names to auto-load
            // 'Daycry\\Schemas\\Plugins\\Examples\\LoggerPlugin',
        ],
        'config' => [
            // Plugin-specific configurations
            'LoggerPlugin' => [
                'enabled' => true,
                'log_level' => 'info',
                'include_data' => false,
            ],
        ],
    ];

    // ========================================
    // Async Configuration
    // ========================================

    /**
     * Async processing configuration
     */
    public array $async = [
        // Enable async processing
        'enabled' => false,
        
        // Default handler for async operations
        'default_handler' => 'schema',
        
        // Handlers configuration
        'handlers' => [
            'schema' => [
                'class' => 'Daycry\\Schemas\\Async\\Handlers\\AsyncSchemaHandler',
                'config' => [
                    'max_concurrent_jobs' => 3,
                    'job_timeout' => 300, // 5 minutes
                    'max_retries' => 3,
                    'retry_delay' => 5, // seconds
                    'enable_events' => true,
                ],
            ],
        ],
        
        // Cleanup configuration
        'cleanup_interval' => 3600, // 1 hour
        
        // Enable monitoring and statistics
        'enable_monitoring' => true,
        
        // Event listeners for async operations
        'event_listeners' => [
            // Add custom event listeners here
        ],
    ];

    // ========================================
    // Legacy Configuration (for backward compatibility)
    // ========================================

    /**
     * Which tasks to automate when a schema is not available from the service
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
    public array $readHandlers = [
        'cache' => CacheReadHandler::class,
        'directory' => \Daycry\Schemas\Reader\Handlers\DirectoryHandler::class,
        'php' => \Daycry\Schemas\Reader\Handlers\PhpHandler::class,
        'json' => \Daycry\Schemas\Reader\Handlers\JsonHandler::class,
    ];

    /**
     * Default handlers used to create a schema (order sensitive)
     * (Probably shouldn't change this unless you really know what you're doing)
     */
    public array $draftHandlers = [
        'database'  => DatabaseHandler::class,
        'model'     => ModelHandler::class,
        'directory' => DirectoryHandler::class,
    ];

    /**
     * Directory handlers for different file types
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
    public array $archiveHandlers = [
        'cache' => CacheArchiveHandler::class,
    ];

    /**
     * Namespaces to ignore (mostly for ModelHandler)
     */
    public array $ignoredNamespaces = [
        'Tests\Support',
        'CodeIgniter\Commands\Generators',
    ];

    /**
     * Advanced Configuration Environment Manager
     */
    public ?ConfigEnvironment $configEnvironment = null;

    /**
     * Advanced caching configuration (extends existing cache)
     */
    public array $advancedCache = [
        'enabled' => true,
        'ttl' => 3600,
        'prefix' => 'schemas_',
        'driver' => 'file', // file, database, redis, memcached
        'invalidation' => [
            'auto' => true,
            'events' => ['schema_updated', 'table_modified']
        ]
    ];

    /**
     * Security configuration
     */
    public array $security = [
        'enabled' => true,
        'allowed_operations' => ['read', 'archive', 'draft', 'validate', 'compare', 'merge'],
        'restricted_tables' => [],
        'encryption' => [
            'enabled' => false,
            'algorithm' => 'AES-256-CBC',
            'key_rotation' => false
        ]
    ];

    /**
     * Development tools configuration
     */
    public array $development = [
        'debug_mode' => false,
        'query_debugging' => false,
        'profiler' => false,
        'schema_diff_tool' => true,
        'migration_generator' => true
    ];

    /**
     * Initialize the configuration system
     */
    public function __construct()
    {
        parent::__construct();
        
        // Initialize advanced configuration environment
        $this->initializeConfigEnvironment();
        
        // Apply environment-specific configuration
        $this->applyEnvironmentConfiguration();
    }

    /**
     * Initialize the configuration environment manager
     */
    private function initializeConfigEnvironment(): void
    {
        $environment = ENVIRONMENT ?? 'development';
        $this->configEnvironment = new ConfigEnvironment($environment);
        
        // Set base configuration from current properties
        $baseConfig = [
            'plugins' => $this->plugins,
            'async' => $this->async,
            'cache' => $this->cache, // Use existing cache property
            'advancedCache' => $this->advancedCache,
            'logging' => $this->logging,
            'validation' => $this->validation,
            'performance' => $this->performance,
            'security' => $this->security,
            'development' => $this->development
        ];
        
        $this->configEnvironment->setBaseConfig($baseConfig);
    }

    /**
     * Apply environment-specific configuration
     */
    private function applyEnvironmentConfiguration(): void
    {
        if (!$this->configEnvironment) {
            return;
        }

        $compiledConfig = $this->configEnvironment->getCompiledConfig();

        // Update properties with compiled configuration
        $this->plugins = $compiledConfig['plugins'] ?? $this->plugins;
        $this->async = $compiledConfig['async'] ?? $this->async;
        $this->cache = $compiledConfig['cache'] ?? $this->cache;
        $this->advancedCache = $compiledConfig['advancedCache'] ?? $this->advancedCache;
        $this->logging = $compiledConfig['logging'] ?? $this->logging;
        $this->validation = $compiledConfig['validation'] ?? $this->validation;
        $this->performance = $compiledConfig['performance'] ?? $this->performance;
        $this->security = $compiledConfig['security'] ?? $this->security;
        $this->development = $compiledConfig['development'] ?? $this->development;
    }

    /**
     * Get configuration value with dot notation
     */
    public function get(string $key, $default = null)
    {
        if ($this->configEnvironment) {
            return $this->configEnvironment->get($key, $default);
        }

        return $default;
    }

    /**
     * Set runtime configuration override
     */
    public function setRuntimeConfig(string $key, $value): void
    {
        if ($this->configEnvironment) {
            $this->configEnvironment->setRuntimeOverride($key, $value);
            $this->applyEnvironmentConfiguration();
        }
    }

    /**
     * Get current environment
     */
    public function getEnvironment(): string
    {
        return $this->configEnvironment ? $this->configEnvironment->getEnvironment() : 'unknown';
    }

    /**
     * Switch to different environment
     */
    public function switchEnvironment(string $environment): void
    {
        if ($this->configEnvironment) {
            $this->configEnvironment->setEnvironment($environment);
            $this->applyEnvironmentConfiguration();
        }
    }

    /**
     * Create configuration profile
     */
    public function createProfile(string $name, array $config): void
    {
        if ($this->configEnvironment) {
            $this->configEnvironment->setProfile($name, $config);
        }
    }

    /**
     * Export configuration to file
     */
    public function exportConfig(string $filepath, string $format = 'json'): bool
    {
        if ($this->configEnvironment) {
            return $this->configEnvironment->exportToFile($filepath, $format);
        }
        return false;
    }

    /**
     * Import configuration from file
     */
    public function importConfig(string $filepath, string $format = 'json'): bool
    {
        if ($this->configEnvironment) {
            $result = $this->configEnvironment->importFromFile($filepath, $format);
            if ($result) {
                $this->applyEnvironmentConfiguration();
            }
            return $result;
        }
        return false;
    }

    /**
     * Add configuration change listener
     */
    public function addConfigListener(string $event, callable $listener): void
    {
        if ($this->configEnvironment) {
            $this->configEnvironment->addListener($event, $listener);
        }
    }

    /**
     * Create configuration snapshot
     */
    public function createSnapshot(): array
    {
        if ($this->configEnvironment) {
            return $this->configEnvironment->createSnapshot();
        }
        return [];
    }

    /**
     * Restore from configuration snapshot
     */
    public function restoreSnapshot(array $snapshot): void
    {
        if ($this->configEnvironment) {
            $this->configEnvironment->restoreFromSnapshot($snapshot);
            $this->applyEnvironmentConfiguration();
        }
    }

    /**
     * Get configuration statistics
     */
    public function getConfigStats(): array
    {
        if (!$this->configEnvironment) {
            return [];
        }

        $compiledConfig = $this->configEnvironment->getCompiledConfig();
        
        return [
            'environment' => $this->getEnvironment(),
            'profiles_available' => $this->configEnvironment->getProfiles(),
            'total_config_keys' => $this->countConfigKeys($compiledConfig),
            'cache_enabled' => $this->cache['enabled'] ?? false,
            'async_enabled' => $this->async['enabled'] ?? false,
            'plugins_enabled' => $this->plugins['enabled'] ?? false,
            'debug_mode' => $this->development['debug_mode'] ?? false,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Count configuration keys recursively
     */
    private function countConfigKeys(array $config): int
    {
        $count = 0;
        foreach ($config as $key => $value) {
            $count++;
            if (is_array($value)) {
                $count += $this->countConfigKeys($value);
            }
        }
        return $count;
    }
}
