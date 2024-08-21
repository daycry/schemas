<?php

declare(strict_types=1);

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
