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

namespace Tests\Support\Traits;

use CodeIgniter\Cache\CacheInterface;
use Config\Services;
use Daycry\Schemas\Archiver\Handlers\CacheHandler as CacheArchiver;

/**
 * Cache Trait
 *
 * @mixin SchemasTestCase
 */
trait CacheTrait
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var CacheArchiver
     */
    private $archiver;

    /**
     * Sets up the Cache driver and
     * the Schemas Cache handlers.
     */
    public function setUpCacheTrait(): void
    {
        $this->cache    = Services::cache();
        $this->archiver = new CacheArchiver($this->config, $this->cache);
    }
}
