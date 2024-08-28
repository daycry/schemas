<?php

/**
 * This file is part of Daycry Schemas.
 *
 * (c) Daycry <daycry9@proton.me>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Support\Traits;

use Daycry\Schemas\Structures\Schema;

/**
 * Mock Schema Trait
 *
 * Loads a schema from Tests\Support
 * to save on expensive database calls.
 *
 * @mixin SchemasTestCase
 */
trait MockSchemaTrait
{
    protected ?Schema $schema = null;

    /**
     * Loads the Industrial Schema.
     *
     * @retun void
     */
    protected function setUpMockSchemaTrait(): void
    {
        // Include the file which will place the Schema into $schema
        require SUPPORTPATH . 'Schemas' . DIRECTORY_SEPARATOR . 'MockSchema.php';

        // @phpstan-ignore-next-line
        $this->schema = $schema;
    }
}
