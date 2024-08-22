<?php

declare(strict_types=1);

namespace Daycry\Schemas\Commands;

use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Publisher\Publisher;
use Throwable;

class ConfigPublish extends BaseCommand
{
    protected $group       = 'Schemas';
    protected $name        = 'schemas:publish';
    protected $description = 'Publish Schemas components into the current application.';

    public function run(array $params)
    {
        // Use the Autoloader to figure out the module path
        $source = service('autoloader')->getNamespace('Daycry\\Schemas')[0];

        $publisher = new Publisher($source, APPPATH);

        $overwrite = (bool) CLI::getOption('f');

        try {
            // Add only the desired components
            $publisher->addPaths([
                'Config/Schemas.php',
            ])->merge($overwrite); // Be careful not to overwrite anything
        } catch (Throwable $e) {
            $this->showError($e);

            return;
        }

        // If publication succeeded then update namespaces
        foreach ($publisher->getPublished() as $file) {
            // Replace the namespace
            $contents = file_get_contents($file);
            $contents = str_replace('namespace Daycry\\Schemas', 'namespace Config', $contents);
            $contents = str_replace('extends BaseConfig', 'extends \\Daycry\\Schemas\\Config\\Schemas', $contents);
            file_put_contents($file, $contents);
        }

        $this->write(CLI::color(' Config files Created: ', 'green') . implode(', ', $publisher->getPublished()));
    }

    protected function write(
        string $text = '',
        ?string $foreground = null,
        ?string $background = null
    ): void {
        CLI::write($text, $foreground, $background);
    }
}