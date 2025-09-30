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

namespace Daycry\Schemas\Config;

use CodeIgniter\Config\BaseService;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Schemas;

class Services extends BaseService
{
    public static function schemas(?SchemasConfig $config = null, bool $getShared = true): Schemas
    {
        if ($getShared) {
            /** @var Schemas */
            return static::getSharedInstance('schemas', $config);
        }

        $cfg = $config ?? config('Schemas');
        if (! $cfg instanceof SchemasConfig) {
            $cfg = new SchemasConfig();
        }

        return new Schemas($cfg);
    }
}
