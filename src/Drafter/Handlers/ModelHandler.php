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

namespace Daycry\Schemas\Drafter\Handlers;

use CodeIgniter\Model;
use Config\Services;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Drafter\BaseDrafter;
use Daycry\Schemas\Drafter\DrafterInterface;
use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Structures\Schema;
use Daycry\Schemas\Structures\Table;
use Exception;
use ReflectionClass;

/**
 * Final model drafter handler.
 * Derives schema information from CodeIgniter model metadata & properties.
 */
final class ModelHandler extends BaseDrafter implements DrafterInterface
{
    /**
     * The default database group.
     */
    protected string $defaultGroup;

    /**
     * The database group to constrain by.
     */
    protected ?string $group = null;

    /**
     * Save the config and set the initial database group
     *
     * @param SchemasConfig $config The library config
     * @param string        $group  A database group to use as a filter; null = default group, false = no filtering
     */
    public function __construct(?SchemasConfig $config = null, $group = null)
    {
        parent::__construct($config);

        // Load the default database group
        /** @var object $config */
        $config             = config('Database');
        $this->defaultGroup = $config->defaultGroup;
        unset($config);

        // If nothing was specified then constrain to the default database group
        if (null === $group) {
            $this->group = $this->defaultGroup;
        } elseif (! empty($group)) {
            $this->group = $group;
        }
    }

    /**
     * Change the name of the database group constraint
     *
     * @param string $group A database group to use as a filter; false = no filtering
     */
    public function setGroup(string $group): static
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get the name of the database group constraint
     *
     * @return string|null The current group
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * Load models and build table data off their properties
     */
    public function draft(): ?Schema
    {
        // Start with an empty schema
        $schema = new Schema();

        foreach ($this->getModels() as $class) {
            $instance = new $class();

            // Safely read properties using reflection to avoid protected access issues
            $ref      = new ReflectionClass($instance);
            $readProp = static function (object $obj, ReflectionClass $ref, string $prop): mixed {
                if ($ref->hasProperty($prop)) {
                    $p = $ref->getProperty($prop);
                    $p->setAccessible(true);

                    return $p->getValue($obj);
                }

                return null;
            };

            $tableName = (string) ($readProp($instance, $ref, 'table') ?? '');
            if ($tableName === '') {
                continue; // skip models without table name
            }
            $returnType     = (string) ($readProp($instance, $ref, 'returnType') ?? 'array');
            $primaryKey     = (string) ($readProp($instance, $ref, 'primaryKey') ?? 'id');
            $allowed        = $readProp($instance, $ref, 'allowedFields');
            $useTimestamps  = (bool) ($readProp($instance, $ref, 'useTimestamps') ?? false);
            $useSoftDeletes = (bool) ($readProp($instance, $ref, 'useSoftDeletes') ?? false);
            $createdField   = (string) ($readProp($instance, $ref, 'createdField') ?? 'created_at');
            $updatedField   = (string) ($readProp($instance, $ref, 'updatedField') ?? 'updated_at');
            $deletedField   = (string) ($readProp($instance, $ref, 'deletedField') ?? 'deleted_at');
            $dateFormat     = (string) ($readProp($instance, $ref, 'dateFormat') ?? 'datetime');

            $table             = new Table($tableName);
            $table->model      = $class;
            $table->returnType = $returnType;

            $field                         = new Field($primaryKey);
            $field->primary_key            = true;
            $table->fields->{$field->name} = $field;

            if (is_array($allowed)) {
                foreach ($allowed as $fieldName) {
                    $field                       = new Field($fieldName);
                    $table->fields->{$fieldName} = $field;
                }
            }

            $timestamps = $useTimestamps ? ['createdField' => $createdField, 'updatedField' => $updatedField] : [];
            if ($useSoftDeletes) {
                $timestamps['deletedField'] = $deletedField;
            }

            foreach ($timestamps as $fieldName) {
                $field                       = new Field($fieldName);
                $field->type                 = $dateFormat;
                $table->fields->{$fieldName} = $field;
            }

            $schema->tables->{$table->name} = $table;
        }

        return $schema;
    }

    /**
     * Load model class names from all namespaces, filtered by group
     *
     * @return array of model class names
     */
    /**
     * @return array<int, class-string<Model>>
     */
    protected function getModels(): array
    {
        $loader   = Services::autoloader();
        $locator  = Services::locator();
        $models   = [];
        $readProp = static function (object $obj, string $prop): mixed {
            $ref = new ReflectionClass($obj);
            if ($ref->hasProperty($prop)) {
                $p = $ref->getProperty($prop);
                $p->setAccessible(true);

                return $p->getValue($obj);
            }

            return null;
        };

        // Get each namespace
        foreach ($loader->getNamespace() as $namespace => $path) {
            // Skip namespaces that are ignored
            if (in_array($namespace, $this->config->ignoredNamespaces, true)) {
                continue;
            }

            // Get files under this namespace's "/Models" path
            foreach ($locator->listNamespaceFiles($namespace, '/Models/') as $file) {
                if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    // Load the file
                    require_once $file;
                }
            }
        }

        // Filter loaded class on likely models
        $classes = preg_grep('/model$/i', get_declared_classes()) ?: [];

        // Try to load each class
        foreach ($classes as $class) {
            if (! is_string($class)) {
                continue;
            }

            // Check for ignored namespaces
            foreach ($this->config->ignoredNamespaces as $namespace) {
                if (str_starts_with($class, $namespace)) {
                    continue 2;
                }
            }

            // Make sure it's really a model
            if (! is_a($class, Model::class, true)) {
                continue;
            }

            // Try to instantiate
            try {
                $instance = new $class();
            } catch (Exception $e) {
                continue;
            }

            // Make sure it has a valid table
            // Access model table name (public in CI4 models; guard in case of extension changes)
            $table = (string) ($readProp($instance, 'table') ?? '');
            if (empty($table)) {
                continue;
            }

            // Filter by group
            $group = $instance->DBGroup ?? $this->defaultGroup; // @phpstan-ignore-line
            if (empty($this->group) || $group === $this->group) {
                $models[] = $class; // class-string<Model>
            }
            unset($instance);
        }

        return $models;
    }
}
