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

/**
 * Minimal in-memory archiver used only for command success path testing.
 * Not distributed as documented feature; kept internal to tests.
 *
 * @internal
 */
final class CliArchiver extends BaseArchiver implements ArchiverInterface
{
    protected string $name = 'cli';

    public function archive(Schema $schema): bool
    {
        return true; // Always succeeds for tests
    }
}
