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

class DirectoryHandler extends BaseDrafter implements DrafterInterface
{
    /**
     * Path to the schemas directory.
     *
     * @var string
     */
    protected $path;

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
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Scan the schemas directory and process any files found via their handler
     */
    public function draft(): ?Schema
    {
        helper('filesystem');
        $files = get_filenames($this->path, true);

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

            $class = new $class($this->config, $path);

            return new $class($this->config, $path);
        }

        return null;
    }
}
