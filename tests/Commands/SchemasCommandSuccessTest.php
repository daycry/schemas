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

namespace Tests\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\Mock\MockInputOutput;
use Daycry\Schemas\Archiver\Handlers\CliArchiver;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Structures\Schema;
use PHPUnit\Framework\TestCase;

// Internal minimal drafter used for success path
class TestDrafter
{
    public function __construct($c)
    {
    }

    public function draft(): Schema
    {
        return new Schema();
    }
}

/** @internal */
final class SchemasCommandSuccessTest extends TestCase
{
    private static ?SchemasConfig $cfg = null;

    private static function overrideGlobalConfig(): void
    {
        if (function_exists('Daycry\\Schemas\\Commands\\config')) {
            return;
        }
        eval('namespace Daycry\\Schemas\\Commands; function config(string $name){ return \\Tests\\Commands\\SchemasCommandSuccessTest::getTestConfig(); }'); // @phpstan-ignore-line
    }

    public static function getTestConfig(): SchemasConfig
    {
        if (self::$cfg instanceof SchemasConfig) {
            return self::$cfg;
        }
        $config = new SchemasConfig();
        // Minimal drafter so draft() succeeds
        $config->draftHandlers   = ['test' => TestDrafter::class];
        $config->archiveHandlers = ['cli' => CliArchiver::class];
        $config->automate        = ['draft' => false, 'archive' => false, 'read' => false];

        return self::$cfg = $config;
    }

    public function testSuccessfulArchivePath(): void
    {
        self::overrideGlobalConfig();
        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        // Use the framework helper so options get parsed similarly to real usage
        command('schemas -draft test -archive cli');
        $output = $io->getOutput();
        $this->assertStringContainsString('success', (string) $output);
        CLI::resetInputOutput();
    }
}
