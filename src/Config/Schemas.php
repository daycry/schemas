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
     * Cache configuration
     */
    public array $cache = [
        'enabled' => false,
        'handler' => 'file',
        'ttl' => 3600,
        'prefix' => 'schemas_'
    ];

    /**
     * Logging configuration
     */
    public array $logging = [
        'enabled' => false,
        'level' => 'info'
    ];

    /**
     * Relationship detection settings
     */
    public array $relationships = [
        'enabled' => true,
        'detect_polymorphic' => true,
        'detect_many_to_many' => true
    ];

    /**
     * Validation settings
     */
    public array $validation = [
        'enabled' => false,
        'strict_mode' => false
    ];

    /**
     * Plugin system configuration
     */
    public array $plugins = [
        'enabled' => true,
        'auto_discovery' => true,
        'discovery_paths' => [
            APPPATH . 'Plugins/Schemas'
        ],
        'auto_load' => []
    ];

    // ========================================
    // Async Configuration
    // ========================================

    /**
     * Async processing configuration
     */
    public array $async = [
        'enabled' => false,
        'max_concurrent_jobs' => 3,
        'job_timeout' => 300
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

    // ========================================
    // Environment Configuration Management
    // ========================================

    /**
     * Current environment
     */
    private string $currentEnvironment;

    /**
     * Configuration profiles
     */
    private array $configProfiles = [];

    /**
     * Runtime configuration overrides
     */
    private array $runtimeOverrides = [];

    /**
     * Configuration validation rules
     */
    private array $validationRules = [];

    /**
     * Configuration change listeners
     */
    private array $configListeners = [];

    /**
     * Development tools configuration
     */
    public array $development = [
        'schema_diff_tool' => true,
        'migration_generator' => true
    ];

    /**
     * Initialize the configuration system
     */
    public function __construct()
    {
        parent::__construct();
        
        // Initialize environment configuration
        $this->initializeEnvironmentConfiguration();
        
        // Apply environment-specific configuration
        $this->applyEnvironmentConfiguration();
    }

    /**
     * Initialize the environment configuration system
     */
    private function initializeEnvironmentConfiguration(): void
    {
        $this->currentEnvironment = ENVIRONMENT ?? 'development';
        $this->initializeDefaultProfiles();
        $this->initializeValidationRules();
    }

    /**
     * Apply environment-specific configuration
     */
    private function applyEnvironmentConfiguration(): void
    {
        $compiledConfig = $this->getCompiledConfig();

        // Update properties with compiled configuration
        $this->plugins = $compiledConfig['plugins'] ?? $this->plugins;
        $this->async = $compiledConfig['async'] ?? $this->async;
        $this->cache = $compiledConfig['cache'] ?? $this->cache;
        $this->logging = $compiledConfig['logging'] ?? $this->logging;
        $this->validation = $compiledConfig['validation'] ?? $this->validation;
        $this->development = $compiledConfig['development'] ?? $this->development;
    }

    /**
     * Get configuration value with dot notation
     */
    public function get(string $key, $default = null)
    {
        $config = $this->getCompiledConfig();
        return $this->getNestedValue($config, $key, $default);
    }

    /**
     * Set runtime configuration override
     */
    public function setRuntimeConfig(string $key, $value): void
    {
        $this->setNestedValue($this->runtimeOverrides, $key, $value);
        $this->applyEnvironmentConfiguration();
        $this->notifyListeners('runtime_override', ['key' => $key, 'value' => $value]);
    }

    /**
     * Get current environment
     */
    public function getEnvironment(): string
    {
        return $this->currentEnvironment;
    }

    /**
     * Switch to different environment
     */
    public function switchEnvironment(string $environment): void
    {
        $this->currentEnvironment = $environment;
        $this->applyEnvironmentConfiguration();
        $this->notifyListeners('environment_changed', ['environment' => $environment]);
    }

    /**
     * Create configuration profile
     */
    public function createProfile(string $name, array $config): void
    {
        $this->validateConfiguration($config);
        $this->configProfiles[$name] = $config;
        $this->notifyListeners('profile_updated', ['profile' => $name, 'config' => $config]);
    }

    /**
     * Export configuration to file
     */
    public function exportConfig(string $filepath, string $format = 'json'): bool
    {
        $config = $this->getCompiledConfig();
        
        try {
            switch ($format) {
                case 'json':
                    file_put_contents($filepath, json_encode($config, JSON_PRETTY_PRINT));
                    break;
                case 'php':
                    file_put_contents($filepath, "<?php\n\nreturn " . var_export($config, true) . ";\n");
                    break;
                case 'yaml':
                    if (function_exists('yaml_emit_file')) {
                        yaml_emit_file($filepath, $config);
                    } else {
                        throw new \RuntimeException('YAML extension not available');
                    }
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported format: {$format}");
            }
            
            $this->notifyListeners('config_exported', ['filepath' => $filepath, 'format' => $format]);
            return true;
        } catch (\Exception $e) {
            $this->notifyListeners('config_export_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Import configuration from file
     */
    public function importConfig(string $filepath, string $format = 'json'): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }

        try {
            switch ($format) {
                case 'json':
                    $config = json_decode(file_get_contents($filepath), true);
                    break;
                case 'php':
                    $config = include $filepath;
                    break;
                case 'yaml':
                    if (function_exists('yaml_parse_file')) {
                        $config = yaml_parse_file($filepath);
                    } else {
                        throw new \RuntimeException('YAML extension not available');
                    }
                    break;
                default:
                    throw new \InvalidArgumentException("Unsupported format: {$format}");
            }

            if (is_array($config)) {
                // Merge with current configuration
                $this->mergeConfiguration($config);
                $this->notifyListeners('config_imported', ['filepath' => $filepath, 'format' => $format]);
                return true;
            }
        } catch (\Exception $e) {
            $this->notifyListeners('config_import_failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Add configuration change listener
     */
    public function addConfigListener(string $event, callable $listener): void
    {
        if (!isset($this->configListeners[$event])) {
            $this->configListeners[$event] = [];
        }
        $this->configListeners[$event][] = $listener;
    }

    /**
     * Create configuration snapshot
     */
    public function createSnapshot(): array
    {
        return [
            'environment' => $this->currentEnvironment,
            'profiles' => $this->configProfiles,
            'runtime_overrides' => $this->runtimeOverrides,
            'timestamp' => time()
        ];
    }

    /**
     * Restore from configuration snapshot
     */
    public function restoreSnapshot(array $snapshot): void
    {
        $this->currentEnvironment = $snapshot['environment'] ?? 'development';
        $this->configProfiles = $snapshot['profiles'] ?? [];
        $this->runtimeOverrides = $snapshot['runtime_overrides'] ?? [];
        
        $this->applyEnvironmentConfiguration();
        $this->notifyListeners('config_restored', ['snapshot' => $snapshot]);
    }

    /**
     * Get configuration statistics
     */
    public function getConfigStats(): array
    {
        $compiledConfig = $this->getCompiledConfig();
        
        return [
            'environment' => $this->getEnvironment(),
            'profiles_available' => array_keys($this->configProfiles),
            'total_config_keys' => $this->countConfigKeys($compiledConfig),
            'cache_enabled' => $this->cache['enabled'] ?? false,
            'async_enabled' => $this->async['enabled'] ?? false,
            'plugins_enabled' => $this->plugins['enabled'] ?? false,
            'debug_mode' => $this->development['debug_mode'] ?? false,
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }

    // ========================================
    // Private Configuration Management Methods
    // ========================================

    /**
     * Get the compiled configuration for current environment
     */
    private function getCompiledConfig(): array
    {
        $config = [
            'plugins' => $this->plugins,
            'async' => $this->async,
            'cache' => $this->cache,
            'logging' => $this->logging,
            'validation' => $this->validation,
            'development' => $this->development
        ];

        // Apply environment-specific profile
        if (isset($this->configProfiles[$this->currentEnvironment])) {
            $config = $this->mergeConfigurations($config, $this->configProfiles[$this->currentEnvironment]);
        }

        // Apply runtime overrides
        $config = $this->mergeConfigurations($config, $this->runtimeOverrides);

        // Apply environment variables
        $config = $this->applyEnvironmentVariables($config);

        return $config;
    }

    /**
     * Initialize default configuration profiles
     */
    private function initializeDefaultProfiles(): void
    {
        // Development profile
        $this->configProfiles['development'] = [
            'debug' => true,
            'cache' => [
                'enabled' => false,
                'ttl' => 300
            ],
            'async' => [
                'enabled' => false,
                'max_concurrent_jobs' => 1,
                'job_timeout' => 30
            ],
            'plugins' => [
                'enabled' => true,
                'auto_discover' => true
            ],
            'logging' => [
                'level' => 'debug',
                'channels' => ['file', 'console']
            ]
        ];

        // Production profile
        $this->configProfiles['production'] = [
            'debug' => false,
            'cache' => [
                'enabled' => true,
                'ttl' => 3600
            ],
            'async' => [
                'enabled' => true,
                'max_concurrent_jobs' => 5,
                'job_timeout' => 300,
                'cleanup_interval' => 3600
            ],
            'plugins' => [
                'enabled' => true,
                'auto_discover' => false
            ],
            'logging' => [
                'level' => 'error',
                'channels' => ['file']
            ]
        ];

        // Testing profile
        $this->configProfiles['testing'] = [
            'debug' => true,
            'cache' => [
                'enabled' => false,
                'ttl' => 60
            ],
            'async' => [
                'enabled' => false,
                'max_concurrent_jobs' => 1,
                'job_timeout' => 10
            ],
            'plugins' => [
                'enabled' => false,
                'auto_discover' => false
            ],
            'logging' => [
                'level' => 'info',
                'channels' => ['memory']
            ]
        ];
    }

    /**
     * Initialize configuration validation rules
     */
    private function initializeValidationRules(): void
    {
        $this->validationRules['cache.ttl'] = [
            'validator' => function($value) {
                return is_int($value) && $value > 0;
            },
            'message' => 'Cache TTL must be a positive integer'
        ];

        $this->validationRules['async.max_concurrent_jobs'] = [
            'validator' => function($value) {
                return is_int($value) && $value > 0 && $value <= 10;
            },
            'message' => 'Max concurrent jobs must be between 1 and 10'
        ];

        $this->validationRules['logging.level'] = [
            'validator' => function($value) {
                return in_array($value, ['debug', 'info', 'warning', 'error', 'critical']);
            },
            'message' => 'Logging level must be one of: debug, info, warning, error, critical'
        ];
    }

    /**
     * Validate configuration against defined rules
     */
    private function validateConfiguration(array $config): void
    {
        foreach ($this->validationRules as $key => $rule) {
            if ($this->hasNestedKey($config, $key)) {
                $value = $this->getNestedValue($config, $key);
                if (!$rule['validator']($value)) {
                    throw new \InvalidArgumentException($rule['message']);
                }
            }
        }
    }

    /**
     * Merge two configuration arrays
     */
    private function mergeConfigurations(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfigurations($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Merge configuration (helper for import)
     */
    private function mergeConfiguration(array $config): void
    {
        foreach ($config as $section => $values) {
            if (property_exists($this, $section) && is_array($values)) {
                $this->$section = $this->mergeConfigurations($this->$section, $values);
            }
        }
    }

    /**
     * Apply environment variables to configuration
     */
    private function applyEnvironmentVariables(array $config): array
    {
        // Apply SCHEMAS_ prefixed environment variables
        foreach ($_ENV as $key => $value) {
            if (strpos($key, 'SCHEMAS_') === 0) {
                $configKey = strtolower(str_replace(['SCHEMAS_', '_'], ['', '.'], $key));
                $this->setNestedValue($config, $configKey, $this->parseEnvValue($value));
            }
        }

        return $config;
    }

    /**
     * Parse environment variable value
     */
    private function parseEnvValue(string $value)
    {
        // Handle boolean values
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }

        // Handle numeric values
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        // Handle JSON values
        if (($json = json_decode($value, true)) !== null) {
            return $json;
        }

        return $value;
    }

    /**
     * Get nested array value using dot notation
     */
    private function getNestedValue(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set nested array value using dot notation
     */
    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Check if nested key exists using dot notation
     */
    private function hasNestedKey(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return false;
            }
            $current = $current[$k];
        }

        return true;
    }

    /**
     * Notify configuration change listeners
     */
    private function notifyListeners(string $event, array $data = []): void
    {
        if (isset($this->configListeners[$event])) {
            foreach ($this->configListeners[$event] as $listener) {
                $listener($data);
            }
        }
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
