<?php

declare(strict_types=1);

namespace Daycry\Schemas\Drafter;

use Daycry\Schemas\Structures\Schema;

interface DrafterInterface
{
    /**
     * Run the handler and return the resulting schema, or null on failure
     */
    public function draft(): ?Schema;
}