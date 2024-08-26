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

namespace Daycry\Schemas\Archiver\Handlers;

use Daycry\Schemas\Archiver\ArchiverInterface;
use Daycry\Schemas\Archiver\BaseArchiver;
use Daycry\Schemas\Structures\Schema;

class CliHandler extends BaseArchiver implements ArchiverInterface
{
    /**
     * Write out the schema to standard output via Kint
     *
     * @return bool true
     */
    public function archive(Schema $schema): bool
    {
        // @phpstan-ignore-next-line
        +d($schema); // plus disables Kint's depth limit

        return true;
    }
}
