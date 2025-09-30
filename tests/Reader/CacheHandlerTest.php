<?php

declare(strict_types=1);

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
final class CacheHandlerTest extends TestCase
{
    public function testEmptyCacheNotReady(): void
    {
        $cache = new class implements CacheInterface {
            // minimal in-memory cache
            private array $data = [];
            public function initialize() { return $this; }
            public function get($key) { return $this->data[$key] ?? null; }
            public function save($key, $value, $ttl = 60) { $this->data[$key] = $value; return true; }
            public function delete($key) { unset($this->data[$key]); return true; }
            public function increment($key, $offset = 1) { return 0; }
            public function decrement($key, $offset = 1) { return 0; }
            public function clean() { $this->data = []; return true; }
            public function getCacheInfo() { return []; }
            public function getMetaData($key) { return null; }
            public function isSupported(): bool { return true; }
        };

        $handler = new CacheHandler(new Schemas(), $cache);
        // Not ready since no scaffold stored, fetchAll should no-op
        $handler->fetchAll();
        $this->assertNull($handler->getTables());
        $this->assertSame(0, $handler->count());
    }

    public function testScaffoldAndLazyLoad(): void
    {
        $cache = new class implements CacheInterface {
            private array $data = [];
            public function initialize() { return $this; }
            public function get($key) { return $this->data[$key] ?? null; }
            public function save($key, $value, $ttl = 60) { $this->data[$key] = $value; return true; }
            public function delete($key) { unset($this->data[$key]); return true; }
            public function increment($key, $offset = 1) { return 0; }
            public function decrement($key, $offset = 1) { return 0; }
            public function clean() { $this->data = []; return true; }
            public function getCacheInfo() { return []; }
            public function getMetaData($key) { return null; }
            public function isSupported(): bool { return true; }
        };

    $config   = new Schemas();
    $cacheKey = 'schema-' . ENVIRONMENT; // default naming convention
    $scaffold = new Mergeable();
    $scaffold->tables            = new Mergeable();
    $scaffold->tables->users     = true;
    $scaffold->tables->posts     = true;
    $cache->save($cacheKey, $scaffold, 60);
    $cache->save($cacheKey . '-users', (function () { $t = new Table(); $t->name = 'users'; return $t; })(), 60);
    $cache->save($cacheKey . '-posts', (function () { $t = new Table(); $t->name = 'posts'; return $t; })(), 60);
    // Construct handler AFTER seeding cache so constructor ingests scaffold
    $handler = new CacheHandler($config, $cache);
        $this->assertSame(2, $handler->count());
        // Trigger lazy load for one table
        $userTable = $handler->users; // magic __get loads it
        $this->assertInstanceOf(Table::class, $userTable);
        $handler->fetchAll(); // load remaining placeholder
        $tables = $handler->getTables();
        $this->assertInstanceOf(Mergeable::class, $tables);
        $this->assertInstanceOf(Table::class, $tables->posts);
    }
}
