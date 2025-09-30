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

namespace Daycry\Schemas\Reader;

use Daycry\Schemas\BaseHandler;
use Daycry\Schemas\Exceptions\SchemasException;

/**
 * Base Drafter Class
 *
 * Provides common methods for Reader classes.
 */
abstract class BaseReader extends BaseHandler
{
    /**
     * Whether the reader is in a state to be used
     */
    protected bool $ready = false;

    /**
     * The currently loaded schema.
     * Could be static but since Reader is usually called by
     * the service we'll try it like this.
     */
    protected string $schema = '';

    /**
     * Indicate whether the reader is in a state to be used
     */
    public function ready(): bool
    {
        return $this->ready;
    }

    /**
     * Check that reader is ready before using its functions
     */
    protected function ensureReady(): bool
    {
        if ($this->ready) {
            return true;
        }

        if (! $this->config->silent) {
            throw SchemasException::forReaderNotReady();
        }

        $this->errors[] = lang('Schemas.notReady');

        return false;
    }

    /**
     * Dummy implementation for classes that cannot lazy load.
     *
     * @param array<int,string>|string $tables
     */
    public function fetch(array|string $tables): static
    {
        // No-op default for base; concrete readers may override.
        return $this;
    }

    /**
     * Dummy implementation for classes that only bulk load
     */
    public function fetchAll(): static
    {
        // No-op default for base; concrete readers may override.
        return $this;
    }
}
