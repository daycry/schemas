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

namespace Tests\Support;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Security;
use Daycry\Schemas\Config\Schemas;

/**
 * @internal
 */
abstract class TestCase extends CIUnitTestCase
{
    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();

        // Set Config\Security::$csrfProtection to 'session'
        /** @var Security $config */
        $config                 = config('Security');
        $config->csrfProtection = 'session';
        Factories::injectMock('config', 'Security', $config);
    }

    protected function inkectMockAttributes(array $attributes = []): void
    {
        $config = config(Schemas::class);

        foreach ($attributes as $attribute => $value) {
            $config->{$attribute} = $value;
        }

        Factories::injectMock('config', 'Schemas', $config);
    }
}
