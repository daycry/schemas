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

namespace Tests\Config;

use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SchemasAdvancedConfigTest extends TestCase
{
    private SchemasConfig $schemasConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemasConfig = new SchemasConfig();
    }

    public function testInitialization(): void
    {
        $this->assertNotNull($this->schemasConfig);
        $this->assertIsArray($this->schemasConfig->cache);
    }

    public function testGetConfigurationValue(): void
    {
        // Removed plugin system; requesting a removed key returns null
        $this->assertNull($this->schemasConfig->get('plugins.enabled'));
        $this->assertSame('default', $this->schemasConfig->get('nonexistent.key', 'default'));
    }

    // Removed subsystems: runtime overrides, env switching, profiles, export/import, snapshots, stats

    public function testCacheConfigShape(): void
    {
        $c = $this->schemasConfig->cache;
        $this->assertArrayHasKey('enabled', $c);
        $this->assertArrayHasKey('ttl', $c);
        $this->assertArrayHasKey('prefix', $c);
        $this->assertIsBool($c['enabled']);
        $this->assertIsInt($c['ttl']);
        $this->assertIsString($c['prefix']);
    }

    public function testDevelopmentConfigShape(): void
    {
        $d = $this->schemasConfig->development;
        $this->assertArrayHasKey('schema_diff_tool', $d);
        $this->assertArrayHasKey('migration_generator', $d);
        $this->assertIsBool($d['schema_diff_tool']);
        $this->assertIsBool($d['migration_generator']);
    }

    // Listeners, hierarchy, validation removed
}
