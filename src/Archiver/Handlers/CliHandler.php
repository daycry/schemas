<?php

declare(strict_types=1);

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
