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

namespace Daycry\Schemas\Drafter\Handlers;

use CodeIgniter\Config\BaseConfig;
use Daycry\Schemas\Drafter\BaseDrafter;
use Daycry\Schemas\Drafter\DrafterInterface;
use Daycry\Schemas\Structures\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Final directory drafter handler.
 * Iterates PHP schema definition files inside a directory and merges them.
 */
final class DirectoryHandler extends BaseDrafter implements DrafterInterface
{
    /**
     * Path to the schemas directory.
     */
    protected string $path;

    /**
     * Save the directory path or load the default from the config
     *
     * @param mixed|null $path
     */
    public function __construct(?BaseConfig $config = null, $path = null)
    {
        parent::__construct($config);

        $this->path = $path ?? $this->config->schemasDirectory;
    }

    /**
     * Change the schemas directory path
     *
     * @param string $path Path to the directory with the schema files.
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Scan the schemas directory and process any files found via their handler
     */
    public function draft(): ?Schema
    {
        $files = [];
        if (is_dir($this->path)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));

            foreach ($iterator as $fileInfo) {
                if ($fileInfo instanceof SplFileInfo && $fileInfo->isFile()) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }

        if (empty($files)) {
            $this->errors[] = lang('Schemas.emptySchemaDirectory', [$this->config->schemasDirectory]);

            return null;
        }

        // Try each file
        foreach ($files as $path) {
            // Make sure there is a handler for this extension
            $handler = $this->getHandlerForFile($path);

            if (null === $handler) {
                $this->errors[] = lang('Schemas.unsupportedHandler', [pathinfo($path, PATHINFO_EXTENSION)]);

                continue;
            }

            if (empty($schema)) {
                $schema = $handler->draft();
            } else {
                $schema->merge($handler->draft());
            }
        }

        return $schema ?? null;
    }

    /**
     * Try to match a file to its handler by the extension
     */
    protected function getHandlerForFile(string $path): ?DrafterInterface
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (array_key_exists($extension, $this->config->directoryHandlers)) {
            $class = $this->config->directoryHandlers[$extension];
            if (! class_exists($class)) {
                return null;
            }
            $instance = new $class($this->config, $path);
            if ($instance instanceof DrafterInterface) {
                return $instance;
            }

            return null;
        }

        return null;
    }
}
