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
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Mock\MockInputOutput;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class SchemaCommandTest extends TestCase
{
    use DatabaseTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSchemaDatabaseDraft(): void
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        command('schemas -draft database');

        $output = $io->getOutput();

        $expected = 'success';
        $this->assertStringContainsString($expected, $output);

        CLI::resetInputOutput();
    }

    public function testSchemaModelDraft(): void
    {
        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        command('schemas -draft model');

        $output = $io->getOutput();

        $expected = 'success';
        $this->assertStringContainsString($expected, $output);

        CLI::resetInputOutput();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
