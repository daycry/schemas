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

namespace Daycry\Schemas\Drafter;

use Daycry\Schemas\Structures\Schema;

interface DrafterInterface
{
    /**
     * Run the handler and return the resulting schema, or null on failure
     */
    public function draft(): ?Schema;
}
