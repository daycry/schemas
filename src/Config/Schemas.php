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

use CodeIgniter\Config\BaseConfig;
use Daycry\Schemas\Archiver\Handlers\CacheHandler as CacheArchiveHandler;
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;
use Daycry\Schemas\Drafter\Handlers\DirectoryHandler;
use Daycry\Schemas\Drafter\Handlers\ModelHandler;
use Daycry\Schemas\Reader\Handlers\CacheHandler as CacheReadHandler;

class Schemas extends BaseConfig
{
    // Whether to continue instead of throwing exceptions
    public bool $silent = true;

    // Which tasks to automate when a schema is not available from the service
    public array $automate = [
        'draft'   => true,
        'archive' => true,
        'read'    => true,
    ];

    // Default handler used to return and read a schema
    public string $readHandler = CacheReadHandler::class;

    // Default handlers used to create a schema (order sensitive)
    // (Probably shouldn't change this unless you really know what you're doing)
    public array $draftHandlers = [
        'database'  => DatabaseHandler::class,
        'model'     => ModelHandler::class,
        'directory' => DirectoryHandler::class,
    ];

    // Path the directoryHandler should scan for schema files
    public string $schemasDirectory = APPPATH . 'Schemas';

    // Default handlers to archive copies of the schema
    public array $archiveHandlers = [
        'cache' => CacheArchiveHandler::class,
    ];

    // Default time-to-live for a stored schema (e.g. Cache) in seconds
    public int $ttl = 14400; // 4 hours

    // Tables to ignore when creating the schema
    public array $ignoredTables = ['migrations'];

    // Namespaces to ignore (mostly for ModelHandler)
    public array $ignoredNamespaces = [
        'Tests\Support',
        'CodeIgniter\Commands\Generators',
    ];
}
