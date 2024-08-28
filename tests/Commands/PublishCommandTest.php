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
use Tests\Support\TestCase;

/**
 * @internal
 */
final class PublishCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testPublish(): void
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);

        command('schemas:publish');

        $output = $io->getOutput();

        $expected = 'Config files Created';
        $this->assertStringContainsString($expected, $output);

        // Remove MockInputOutput.
        CLI::resetInputOutput();
    }
}
