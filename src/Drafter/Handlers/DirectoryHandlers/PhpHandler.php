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

namespace Daycry\Schemas\Drafter\Handlers\DirectoryHandlers;

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Drafter\BaseDrafter;
use Daycry\Schemas\Drafter\DrafterInterface;
use Daycry\Schemas\Structures\Schema;
use Exception;

class PhpHandler extends BaseDrafter implements DrafterInterface
{
    /**
     * The path to the file.
     *
     * @var string
     */
    protected $path;

    /**
     * Save the config and the path to the file
     *
     * @param SchemasConfig $config The library config
     * @param string        $path   Path to the file to process
     */
    public function __construct(?SchemasConfig $config = null, $path = null)
    {
        parent::__construct($config);

        // Save the path
        $this->path = $path;
    }

    /**
     * Read in data from the file and fit it into a schema
     */
    public function draft(): ?Schema
    {
        $contents = $this->getContents($this->path);
        if (null === $contents) {
            $this->errors[] = lang('Schemas.emptySchemaFile', [$this->path]);

            return null;
        }

        // PHP files should contain pre-built schemas in the $schema variable
        // So the path just needs to be included and the variable checked
        try {
            require $this->path;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();

            return null;
        }

        return $schema ?? null; // @phpstan-ignore-line
    }
}
