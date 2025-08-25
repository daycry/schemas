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

use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Events\EventInterface;

/**
 * Base interface for all Schemas plugins
 */
interface PluginInterface
{
    /**
     * Get the plugin name
     */
    public function getName(): string;

    /**
     * Get the plugin version
     */
    public function getVersion(): string;

    /**
     * Get the plugin description
     */
    public function getDescription(): string;

    /**
     * Get the plugin author
     */
    public function getAuthor(): array;

    /**
     * Get supported events this plugin listens to
     *
     * @return array<string, int> Event name => Priority
     */
    public function getSubscribedEvents(): array;

    /**
     * Initialize the plugin
     */
    public function initialize(): void;

    /**
     * Handle an event
     */
    public function handleEvent(EventInterface $event): void;

    /**
     * Check if plugin is compatible with current schema version
     */
    public function isCompatible(string $schemasVersion): bool;

    /**
     * Get plugin configuration schema
     *
     * @return array<string, mixed>
     */
    public function getConfigSchema(): array;

    /**
     * Validate plugin configuration
     *
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config): bool;

    /**
     * Get plugin dependencies
     *
     * @return array<string> List of required plugin names
     */
    public function getDependencies(): array;

    /**
     * Plugin cleanup on disable
     */
    public function disable(): void;

    /**
     * Check if plugin is enabled
     */
    public function isEnabled(): bool;

    /**
     * Get plugin metadata
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if plugin is initialized
     */
    public function isInitialized(): bool;

    /**
     * Check compatibility with library version
     */
    public function checkCompatibility(string $version): bool;

    /**
     * Validate the plugin configuration
     */
    public function validateConfiguration(array $config): bool;

    /**
     * Shutdown the plugin
     */
    public function shutdown(): void;
}
