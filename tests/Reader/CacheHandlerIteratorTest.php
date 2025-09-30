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

namespace Tests\Reader;

use CodeIgniter\Cache\CacheInterface;
use Daycry\Schemas\Config\Schemas;
use Daycry\Schemas\Reader\Handlers\CacheHandler;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Table;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CacheHandlerIteratorTest extends TestCase
{
    private function memoryCache(): CacheInterface
    {
        return new class () implements CacheInterface {
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
    }

    public function testIteratorAndIsset(): void
    {
        $config = new Schemas();
        $cache  = $this->memoryCache();
        $key    = 'schema-' . ENVIRONMENT;

        // Scaffold with placeholders
        $scaffold                = new Mergeable();
        $scaffold->tables        = new Mergeable();
        $scaffold->tables->alpha = true;
        $scaffold->tables->beta  = true;
        $cache->save($key, $scaffold, 60);
        $alpha       = new Table();
        $alpha->name = 'alpha';
        $beta        = new Table();
        $beta->name  = 'beta';
        $cache->save($key . '-alpha', $alpha, 60);
        $cache->save($key . '-beta', $beta, 60);

        $handler = new CacheHandler($config, $cache);
        $this->assertTrue(isset($handler->alpha)); // placeholder exists
        $this->assertNull($handler->getTables()->alpha instanceof Table ? null : null); // just access chain
        $handler->fetch('alpha'); // load single
        $this->assertInstanceOf(Table::class, $handler->alpha);

        // Iterate triggers fetchAll for remaining placeholders
        $names = [];

        foreach ($handler as $n => $t) {
            $names[] = $n;
        }
        sort($names);
        $this->assertSame(['alpha', 'beta'], $names);
        $this->assertInstanceOf(Table::class, $handler->beta);
    }
}
