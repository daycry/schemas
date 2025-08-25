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
use Daycry\Schemas\Exceptions\SchemasException;

/**
 * Abstract base class for Schemas plugins
 */
abstract class BasePlugin implements PluginInterface
{
    protected string $name;
    protected string $version;
    protected string $description;
    protected array|string $author;
    protected array $config = [];
    protected bool $initialized = false;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return $this->name ?? static::class;
    }

    public function getVersion(): string
    {
        return $this->version ?? '1.0.0';
    }

    public function getDescription(): string
    {
        return $this->description ?? 'A Schemas plugin';
    }

    public function getAuthor(): array
    {
        if (is_array($this->author)) {
            return $this->author;
        }
        return ['name' => $this->author ?? 'Unknown'];
    }

    public function getSubscribedEvents(): array
    {
        return [];
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if (!$this->validateConfig($this->config)) {
            throw SchemasException::forInvalidPluginConfiguration($this->getName());
        }

        $this->doInitialize();
        $this->initialized = true;
    }

    public function handleEvent(EventInterface $event): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $this->doHandleEvent($event);
    }

    public function isCompatible(string $schemasVersion): bool
    {
        // Default implementation - assume compatible
        return true;
    }

    public function getConfigSchema(): array
    {
        return [];
    }

    public function validateConfig(array $config): bool
    {
        // Default implementation - basic validation
        $schema = $this->getConfigSchema();
        
        foreach ($schema as $key => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($config[$key])) {
                return false;
            }
            
            if (isset($config[$key]) && isset($rules['type'])) {
                $type = gettype($config[$key]);
                if ($type !== $rules['type']) {
                    return false;
                }
            }
        }
        
        return true;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function disable(): void
    {
        $this->doDisable();
        $this->initialized = false;
    }

    /**
     * Get plugin configuration value
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set plugin configuration value
     */
    protected function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Plugin-specific initialization logic
     */
    protected function doInitialize(): void
    {
        // Override in child classes
    }

    /**
     * Plugin-specific event handling logic
     */
    protected function doHandleEvent(EventInterface $event): void
    {
        // Override in child classes
    }

    /**
     * Plugin-specific cleanup logic
     */
    protected function doDisable(): void
    {
        // Override in child classes
    }

    /**
     * Check if plugin is enabled
     */
    public function isEnabled(): bool
    {
        return $this->getConfig('enabled', true);
    }

    /**
     * Get plugin metadata
     */
    public function getMetadata(): array
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'description' => $this->getDescription(),
            'author' => $this->getAuthor(),
            'config' => $this->config,
            'dependencies' => $this->getDependencies(),
            'subscribed_events' => array_keys($this->getSubscribedEvents()),
            'enabled' => $this->isEnabled(),
            'initialized' => $this->isInitialized(),
        ];
    }

    /**
     * Check if plugin is initialized
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * Check compatibility with library version
     */
    public function checkCompatibility(string $version): bool
    {
        // Default implementation - assume compatible with all versions
        return true;
    }

    /**
     * Validate the plugin configuration
     */
    public function validateConfiguration(array $config): bool
    {
        return $this->validateConfig($config);
    }

    /**
     * Shutdown the plugin
     */
    public function shutdown(): void
    {
        $this->disable();
    }
}
