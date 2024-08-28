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
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Schemas;
use Daycry\Schemas\Structures\Schema;

/**
 * @internal
 */
abstract class TestCase extends CIUnitTestCase
{
    protected ?SchemasConfig $config = null;

    /**
     * @var Schemas|null
     */
    protected Schemas $schemas;

    protected ?Schema $schema = null;

    protected function setUp(): void
    {
        $this->resetServices();

        parent::setUp();

        $config                    = new SchemasConfig();
        $config->silent            = false;
        $config->ignoredTables     = ['migrations'];
        $config->ignoredNamespaces = ['Tatter\Agents'];
        $config->schemasDirectory  = SUPPORTPATH . 'Schemas';
        $config->automate          = [
            'draft'   => false,
            'archive' => false,
            'read'    => false,
        ];

        $this->config  = $config;
        $this->schemas = new Schemas($config);

        // Set Config\Security::$csrfProtection to 'session'
        /** @var Security $config */
        $config                 = config('Security');
        $config->csrfProtection = 'session';
        Factories::injectMock('config', 'Security', $config);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        cache()->clean();
    }
}
