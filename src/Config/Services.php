<?php

declare(strict_types=1);

namespace Daycry\Schemas\Config;

use Config\Services as BaseService;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Schemas;

class Services extends BaseService
{
    public static function schemas(?SchemasConfig $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('schemas', $config);
        }

        return new Schemas($config ?? config('Schemas'));
    }
}