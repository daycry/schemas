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

namespace Daycry\Schemas\Archiver\Handlers;

use CodeIgniter\Cache\CacheInterface;
use Daycry\Schemas\Archiver\ArchiverInterface;
use Daycry\Schemas\Archiver\BaseArchiver;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Traits\CacheHandlerTrait;

class CacheHandler extends BaseArchiver implements ArchiverInterface
{
    use CacheHandlerTrait;

    /**
     * Save the config and set up the cache
     *
     * @param SchemasConfig  $config The library config
     * @param CacheInterface $cache  The cache handler to use, null to load a new default
     */
    public function __construct(?SchemasConfig $config = null, ?CacheInterface $cache = null)
    {
        parent::__construct($config);

        $this->cacheInit($cache);
    }

    /**
     * Store the scaffold and each individual table to cache
     *
     * @return bool Success or failure
     */
    public function archive(Schema $schema): bool
    {
        // Grab the tables to store separately
        $tables         = $schema->tables;
        $schema->tables = new Mergeable();

        // Save each individual table
        foreach ($tables as $table) {
            $schema->tables->{$table->name} = true;
            $this->cache->save($this->cacheKey . '-' . $table->name, $table, $this->config->ttl);
        }

        // Save the scaffold version of the schema
        return $this->cache->save($this->cacheKey, $schema, $this->config->ttl);
    }
}
