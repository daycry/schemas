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

/**
 * Configuration Environment Manager
 * 
 * Handles environment-specific configurations with hierarchical override support
 */
class ConfigEnvironment
{
    /**
     * Current environment
     */
    private string $environment;

    /**
     * Configuration profiles
     */
    private array $profiles = [];

    /**
     * Base configuration
     */
    private array $baseConfig = [];

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
    private array $listeners = [];

    public function __construct(string $environment = 'development')
    {
        $this->environment = $environment;
        $this->initializeDefaultProfiles();
        $this->initializeValidationRules();
    }

    /**
     * Set the current environment
     */
    public function setEnvironment(string $environment): void
    {
        $this->environment = $environment;
        $this->notifyListeners('environment_changed', ['environment' => $environment]);
    }

    /**
     * Get the current environment
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Set a configuration profile
     */
    public function setProfile(string $name, array $config): void
    {
        $this->validateConfiguration($config);
        $this->profiles[$name] = $config;
        $this->notifyListeners('profile_updated', ['profile' => $name, 'config' => $config]);
    }

    /**
     * Get a configuration profile
     */
    public function getProfile(string $name): ?array
    {
        return $this->profiles[$name] ?? null;
    }

    /**
     * Get all available profiles
     */
    public function getProfiles(): array
    {
        return array_keys($this->profiles);
    }

    /**
     * Set base configuration
     */
    public function setBaseConfig(array $config): void
    {
        $this->validateConfiguration($config);
        $this->baseConfig = $config;
        $this->notifyListeners('base_config_updated', ['config' => $config]);
    }

    /**
     * Get the compiled configuration for current environment
     */
    public function getCompiledConfig(): array
    {
        $config = $this->baseConfig;

        // Apply environment-specific profile
        if (isset($this->profiles[$this->environment])) {
            $config = $this->mergeConfigurations($config, $this->profiles[$this->environment]);
        }

        // Apply runtime overrides
        $config = $this->mergeConfigurations($config, $this->runtimeOverrides);

        // Apply environment variables
        $config = $this->applyEnvironmentVariables($config);

        return $config;
    }

    /**
     * Set runtime configuration override
     */
    public function setRuntimeOverride(string $key, $value): void
    {
        $this->setNestedValue($this->runtimeOverrides, $key, $value);
        $this->notifyListeners('runtime_override', ['key' => $key, 'value' => $value]);
    }

    /**
     * Remove runtime configuration override
     */
    public function removeRuntimeOverride(string $key): void
    {
        $this->unsetNestedValue($this->runtimeOverrides, $key);
        $this->notifyListeners('runtime_override_removed', ['key' => $key]);
    }

    /**
     * Get configuration value with dot notation support
     */
    public function get(string $key, $default = null)
    {
        $config = $this->getCompiledConfig();
        return $this->getNestedValue($config, $key, $default);
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        $config = $this->getCompiledConfig();
        return $this->hasNestedKey($config, $key);
    }

    /**
     * Add configuration validation rule
     */
    public function addValidationRule(string $key, callable $validator, string $message = ''): void
    {
        $this->validationRules[$key] = [
            'validator' => $validator,
            'message' => $message ?: "Validation failed for key: {$key}"
        ];
    }

    /**
     * Add configuration change listener
     */
    public function addListener(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }

    /**
     * Create configuration snapshot
     */
    public function createSnapshot(): array
    {
        return [
            'environment' => $this->environment,
            'base_config' => $this->baseConfig,
            'profiles' => $this->profiles,
            'runtime_overrides' => $this->runtimeOverrides,
            'timestamp' => time()
        ];
    }

    /**
     * Restore from configuration snapshot
     */
    public function restoreFromSnapshot(array $snapshot): void
    {
        $this->environment = $snapshot['environment'] ?? 'development';
        $this->baseConfig = $snapshot['base_config'] ?? [];
        $this->profiles = $snapshot['profiles'] ?? [];
        $this->runtimeOverrides = $snapshot['runtime_overrides'] ?? [];
        
        $this->notifyListeners('config_restored', ['snapshot' => $snapshot]);
    }

    /**
     * Export configuration to file
     */
    public function exportToFile(string $filepath, string $format = 'json'): bool
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
    public function importFromFile(string $filepath, string $format = 'json'): bool
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
                $this->setBaseConfig($config);
                $this->notifyListeners('config_imported', ['filepath' => $filepath, 'format' => $format]);
                return true;
            }
        } catch (\Exception $e) {
            $this->notifyListeners('config_import_failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Initialize default configuration profiles
     */
    private function initializeDefaultProfiles(): void
    {
        // Development profile
        $this->profiles['development'] = [
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
        $this->profiles['production'] = [
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
        $this->profiles['testing'] = [
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
        $this->addValidationRule('cache.ttl', function($value) {
            return is_int($value) && $value > 0;
        }, 'Cache TTL must be a positive integer');

        $this->addValidationRule('async.max_concurrent_jobs', function($value) {
            return is_int($value) && $value > 0 && $value <= 10;
        }, 'Max concurrent jobs must be between 1 and 10');

        $this->addValidationRule('logging.level', function($value) {
            return in_array($value, ['debug', 'info', 'warning', 'error', 'critical']);
        }, 'Logging level must be one of: debug, info, warning, error, critical');
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
     * Unset nested array value using dot notation
     */
    private function unsetNestedValue(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                return;
            }
            $current = &$current[$k];
        }

        unset($current[$lastKey]);
    }

    /**
     * Notify configuration change listeners
     */
    private function notifyListeners(string $event, array $data = []): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener($data);
            }
        }
    }
}
