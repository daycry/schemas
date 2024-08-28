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

use Daycry\Schemas\Schemas;
use Tests\Support\TestCase;
use Tests\Support\Traits\MockSchemaTrait;

/**
 * @internal
 */
final class SchemasTest extends TestCase
{
    use MockSchemaTrait;

    public function testGetErrors()
    {
        $this->assertSame([], $this->schemas->getErrors());
    }

    public function testStartsWithoutSchema()
    {
        $this->config->silent = true;
        $this->assertNull($this->schemas->get());
    }

    public function testStartsWithSchema()
    {
        $this->config->silent = true;
        $this->schemas->setSchema($this->schema);

        $this->assertNotNull($this->schemas->get());

        $this->schemas->reset();
        $this->assertSame([], $this->schemas->getErrors());
    }

    public function testStartsWithAutomatedWithoutSchema()
    {
        $this->config->silent   = true;
        $this->config->automate = [
            'draft'   => true,
            'archive' => true,
            'read'    => true,
        ];

        $this->schemas = new Schemas($this->config);
        $this->assertNotNull($this->schemas->get());
    }
}
