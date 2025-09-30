<?php

declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\CLI\CLI;
use Daycry\Schemas\Commands\Schemas as SchemasCommand;
use Psr\Log\NullLogger;
use CodeIgniter\CLI\Commands;
use PHPUnit\Framework\TestCase;

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
        $io       = new \CodeIgniter\Test\Mock\MockInputOutput();
        CLI::setInputOutput($io);
        $cmd->run($params);
        $output = $io->getOutput();
    // In minimal core CliHandler is not present in archiveHandlers so expect failure output
    $this->assertStringContainsString('Archive failed', $output);
        CLI::resetInputOutput();
    }
}
