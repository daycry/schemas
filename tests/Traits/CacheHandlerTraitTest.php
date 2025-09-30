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

namespace Tests\Traits;

use CodeIgniter\Cache\CacheInterface;
use Daycry\Schemas\Traits\CacheHandlerTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CacheHandlerTraitTest extends TestCase
{
    public function testSetAndGetKey(): void
    {
        $cache = new class () implements CacheInterface {
            private array $d = [];

            public function initialize()
            {
                return $this;
            }

            public function get($key)
            {
                return $this->d[$key] ?? null;
            }

            public function save($key, $value, $ttl = 60)
            {
                $this->d[$key] = $value;

                return true;
            }

            public function delete($key)
            {
                unset($this->d[$key]);

                return true;
            }

            public function increment($key, $offset = 1)
            {
                return 0;
            }

            public function decrement($key, $offset = 1)
            {
                return 0;
            }

            public function clean()
            {
                $this->d = [];

                return true;
            }

            public function getCacheInfo()
            {
                return [];
            }

            public function getMetaData($key)
            {
                return null;
            }

            public function isSupported(): bool
            {
                return true;
            }
        };

        $consumer = new class ($cache) {
            use CacheHandlerTrait;

            public function __construct($c)
            {
                $this->cacheInit($c);
            }
        };
        $consumer->setKey('schema-test');
        $this->assertSame('schema-test', $consumer->getKey());
    }
}
