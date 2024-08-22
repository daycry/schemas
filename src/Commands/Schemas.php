<?php

declare(strict_types=1);

namespace Daycry\Schemas\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Exception;
use Daycry\Schemas\Schemas as SchemaLibrary;
use Daycry\Schemas\Archiver\Handlers\CliHandler;

class Schemas extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'schemas';
    protected $description = 'Manage database schemas.';
    protected $usage       = 'schemas [-draft handler1,handler2,...] [-archive handler1,... | -print]';
    protected $options     = [
        '-draft'   => 'Handler(s) for drafting the schema ("database", "model", etc)',
        '-archive' => 'Handler(s) for archiving a copy of the schema',
        '-print'   => 'Print out the drafted schema',
    ];

    public function run(array $params)
    {
        // Always use a clean library with automation disabled
        /** @var object $config */
        $config           = config('Schemas');
        $config->automate = [
            'draft'   => false,
            'archive' => false,
            'read'    => false,
        ];
        $schemas = new SchemaLibrary($config, null);

        // Determine draft handlers
        if ($drafters = $params['-draft'] ?? CLI::getOption('draft')) {
            $drafters = explode(',', $drafters);
        } else {
            $drafters = array_keys($config->draftHandlers);
        }

        // Determine archive handlers
        if ($params['-print'] ?? CLI::getOption('print')) {
            $archivers = CliHandler::class;
        } elseif ($archivers = $params['-archive'] ?? CLI::getOption('archive')) {
            $archivers = explode(',', $archivers);
        } else {
            $archivers = array_keys($config->archiveHandlers);
        }

        // Try the draft
        try {
            $schemas->draft($drafters);
        } catch (Exception $e) {
            $this->showError($e);
        }

        // Try the archive
        try {
            $result = $schemas->archive($archivers);
        } catch (Exception $e) {
            $this->showError($e);
        }

        if (empty($result)) {
            CLI::write('Archive failed!', 'red');

            foreach ($schemas->getErrors() as $error) {
                CLI::write($error, 'yellow');
            }

            return;
        }

        CLI::write('success', 'green');
    }
}
