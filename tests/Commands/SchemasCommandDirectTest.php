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
use CodeIgniter\CLI\Commands;
use CodeIgniter\Test\Mock\MockInputOutput;
use Daycry\Schemas\Commands\Schemas as SchemasCommand;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @internal
 */
final class SchemasCommandDirectTest extends TestCase
{
    public function testRunWithPrint(): void
    {
        $logger   = new NullLogger();
        $commands = new Commands();
        $cmd      = new SchemasCommand($logger, $commands);
        $params   = ['draft' => 'database', '-print' => true]; // mimic CLI options
        $io       = new MockInputOutput();
        CLI::setInputOutput($io);
        $cmd->run($params);
        $output = $io->getOutput();
        // In minimal core CliHandler is not present in archiveHandlers so expect failure output
        $this->assertStringContainsString('Archive failed', $output);
        CLI::resetInputOutput();
    }
}
