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

namespace Daycry\Schemas\Plugins;

use Daycry\Schemas\Events\EventInterface;
use Daycry\Schemas\Events\BaseEvent;
use Daycry\Schemas\Exceptions\SchemasException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Plugin Manager for handling plugin registration, discovery, and event dispatching
 */
class PluginManager
{
    /** @var array<string, PluginInterface> */
    protected array $plugins = [];

    /** @var array<string, array<string, int>> */
    protected array $eventListeners = [];

    protected LoggerInterface $logger;
    protected string $schemasVersion;

    /** @var array<string> */
    protected array $discoveryPaths = [];

    public function __construct(LoggerInterface $logger = null, string $schemasVersion = '1.0.0')
    {
        $this->logger = $logger ?? new NullLogger();
        $this->schemasVersion = $schemasVersion;
    }

    /**
     * Register a plugin
     */
    public function registerPlugin(PluginInterface $plugin): void
    {
        $name = $plugin->getName();

        if (isset($this->plugins[$name])) {
            throw SchemasException::forPluginAlreadyRegistered($name);
        }

        // Check compatibility
        if (!$plugin->isCompatible($this->schemasVersion)) {
            throw SchemasException::forIncompatiblePlugin($name, $this->schemasVersion);
        }

        // Check dependencies
        $this->checkDependencies($plugin);

        // Initialize plugin
        try {
            $plugin->initialize();
            $this->plugins[$name] = $plugin;
            
            // Register event listeners
            $this->registerEventListeners($plugin);
            
            $this->logger->info("Plugin '{$name}' registered successfully", [
                'plugin' => $plugin->getMetadata()
            ]);
            
        } catch (\Throwable $e) {
            $this->logger->error("Failed to register plugin '{$name}': " . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Unregister a plugin
     */
    public function unregisterPlugin(string $name): void
    {
        if (!isset($this->plugins[$name])) {
            throw SchemasException::forMissingPlugin($name);
        }

        $plugin = $this->plugins[$name];
        
        // Disable plugin
        $plugin->disable();
        
        // Remove event listeners
        $this->unregisterEventListeners($plugin);
        
        // Remove from registry
        unset($this->plugins[$name]);
        
        $this->logger->info("Plugin '{$name}' unregistered successfully");
    }

    /**
     * Get a registered plugin
     */
    public function getPlugin(string $name): ?PluginInterface
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Get all registered plugins
     *
     * @return array<string, PluginInterface>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Check if a plugin is registered
     */
    public function hasPlugin(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Dispatch an event to all subscribed plugins
     */
    public function dispatchEvent(EventInterface $event): void
    {
        $eventName = $event->getName();
        
        if (!isset($this->eventListeners[$eventName])) {
            return;
        }

        // Sort listeners by priority (higher priority first)
        $listeners = $this->eventListeners[$eventName];
        arsort($listeners);

        foreach ($listeners as $pluginName => $priority) {
            if ($event->isPropagationStopped()) {
                break;
            }

            if (!isset($this->plugins[$pluginName])) {
                continue;
            }

            $plugin = $this->plugins[$pluginName];
            
            try {
                $plugin->handleEvent($event);
                
                $this->logger->debug("Event '{$eventName}' handled by plugin '{$pluginName}'", [
                    'event' => $eventName,
                    'plugin' => $pluginName,
                    'priority' => $priority
                ]);
                
            } catch (\Throwable $e) {
                $this->logger->error("Error handling event '{$eventName}' in plugin '{$pluginName}': " . $e->getMessage(), [
                    'event' => $eventName,
                    'plugin' => $pluginName,
                    'exception' => $e
                ]);
            }
        }
    }

    /**
     * Create and dispatch an event
     */
    public function emit(string $eventName, array $data = [], ?object $target = null): EventInterface
    {
        $event = new BaseEvent($eventName, $data, $target);
        $this->dispatchEvent($event);
        return $event;
    }

    /**
     * Add a directory for plugin discovery
     */
    public function addDiscoveryPath(string $path): void
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException("Directory '{$path}' does not exist");
        }

        $this->discoveryPaths[] = rtrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Auto-discover and register plugins from configured paths
     */
    public function discoverPlugins(): int
    {
        $discovered = 0;

        foreach ($this->discoveryPaths as $path) {
            $discovered += $this->discoverPluginsInPath($path);
        }

        $this->logger->info("Plugin discovery completed", [
            'discovered' => $discovered,
            'paths' => $this->discoveryPaths
        ]);

        return $discovered;
    }

    /**
     * Get plugin statistics
     */
    public function getStatistics(): array
    {
        $enabled = 0;
        $disabled = 0;
        $eventListenerCount = 0;

        foreach ($this->plugins as $plugin) {
            if ($plugin->isEnabled()) {
                $enabled++;
            } else {
                $disabled++;
            }
        }

        foreach ($this->eventListeners as $listeners) {
            $eventListenerCount += count($listeners);
        }

        return [
            'total_plugins' => count($this->plugins),
            'enabled_plugins' => $enabled,
            'disabled_plugins' => $disabled,
            'event_listeners' => $eventListenerCount,
            'discovery_paths' => count($this->discoveryPaths),
        ];
    }

    /**
     * Validate all plugins
     */
    public function validatePlugins(): array
    {
        $results = [];

        foreach ($this->plugins as $name => $plugin) {
            $results[$name] = [
                'compatible' => $plugin->isCompatible($this->schemasVersion),
                'dependencies_met' => $this->checkDependencies($plugin, false),
                'config_valid' => $plugin->validateConfig($plugin->getMetadata()['config'] ?? []),
                'enabled' => $plugin->isEnabled(),
            ];
        }

        return $results;
    }

    /**
     * Register event listeners for a plugin
     */
    protected function registerEventListeners(PluginInterface $plugin): void
    {
        $events = $plugin->getSubscribedEvents();
        
        foreach ($events as $eventName => $priority) {
            if (!isset($this->eventListeners[$eventName])) {
                $this->eventListeners[$eventName] = [];
            }
            
            $this->eventListeners[$eventName][$plugin->getName()] = $priority;
        }
    }

    /**
     * Unregister event listeners for a plugin
     */
    protected function unregisterEventListeners(PluginInterface $plugin): void
    {
        $pluginName = $plugin->getName();
        
        foreach ($this->eventListeners as $eventName => $listeners) {
            unset($this->eventListeners[$eventName][$pluginName]);
            
            // Clean up empty event arrays
            if (empty($this->eventListeners[$eventName])) {
                unset($this->eventListeners[$eventName]);
            }
        }
    }

    /**
     * Check plugin dependencies
     */
    protected function checkDependencies(PluginInterface $plugin, bool $throw = true): bool
    {
        $dependencies = $plugin->getDependencies();
        
        foreach ($dependencies as $dependency) {
            if (!isset($this->plugins[$dependency])) {
                if ($throw) {
                    throw SchemasException::forPluginDependencyNotMet($plugin->getName(), $dependency);
                }
                return false;
            }
        }
        
        return true;
    }

    /**
     * Discover plugins in a specific path
     */
    protected function discoverPluginsInPath(string $path): int
    {
        $discovered = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            try {
                $className = $this->getClassNameFromFile($file->getPathname());
                
                if ($className && class_exists($className) && is_subclass_of($className, PluginInterface::class)) {
                    $plugin = new $className();
                    $this->registerPlugin($plugin);
                    $discovered++;
                }
            } catch (\Throwable $e) {
                $this->logger->warning("Failed to load plugin from '{$file->getPathname()}': " . $e->getMessage());
            }
        }

        return $discovered;
    }

    /**
     * Extract class name from PHP file
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        if (!$content) {
            return null;
        }

        // Simple regex to extract namespace and class name
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches) &&
            preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return $namespaceMatches[1] . '\\' . $classMatches[1];
        }

        return null;
    }

    /**
     * Load discovered plugins
     */
    public function loadPlugins(): void
    {
        // This method can be used to automatically instantiate and register
        // plugins discovered through discoverPlugins()
        // For now, it's a placeholder as plugin loading is done automatically
        // during discovery process
        $this->logger->info("Plugin loading completed", [
            'loaded_plugins' => count($this->plugins)
        ]);
    }
}
