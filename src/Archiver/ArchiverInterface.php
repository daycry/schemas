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

namespace Daycry\Schemas\Archiver;

use Daycry\Schemas\Structures\Schema;

interface ArchiverInterface
{
    /**
     * Store a copy of the schema to its destination
     *
     * @return bool Success or failure
     */
    public function archive(Schema $schema): bool;
}
