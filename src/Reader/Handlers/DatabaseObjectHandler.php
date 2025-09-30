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

namespace Daycry\Schemas\Reader\Handlers;

use CodeIgniter\Database\BaseConnection;
use Daycry\Schemas\Config\Schemas as SchemasConfig;
use Daycry\Schemas\Reader\BaseReader;
use Daycry\Schemas\Reader\ReaderInterface;
use Daycry\Schemas\Structures\Mergeable;
use Daycry\Schemas\Structures\Procedure;
use Daycry\Schemas\Structures\Trigger;
use Daycry\Schemas\Structures\View;

/**
 * Database Objects Handler
 *
 * Reads database views, stored procedures, and triggers
 */
class DatabaseObjectHandler extends BaseReader implements ReaderInterface
{
    /**
     * The main database connection.
     *
     * @var BaseConnection<mixed,mixed>
     */
    protected BaseConnection $db;

    /**
     * Database objects container
     */
    protected ?Mergeable $objects;

    /**
     * Save the config and set up the database connection
     *
     * @param SchemasConfig $config The library config
     * @param string        $db     A database connection, or null to use the default
     */
    public function __construct(?SchemasConfig $config = null, $db = null)
    {
        parent::__construct($config);

        // Use injected database connection, or start a new one with the default group
        $this->db = db_connect($db);

        $this->objects = new Mergeable();
        $this->ready   = true;
    }

    /**
     * Get all database objects
     */
    public function getObjects(): ?Mergeable
    {
        return $this->objects;
    }

    /**
     * Fetch specified objects from the database
     *
     * @param array<int,string>|string $objects
     */
    public function fetch(array|string $objects): static
    {
        if (! $this->ensureReady()) {
            return $this;
        }
        $list = is_string($objects) ? [$objects] : $objects;

        foreach ($list as $objectType) {
            switch (strtolower($objectType)) {
                case 'views':
                    $this->fetchViews();
                    break;

                case 'procedures':
                case 'functions':
                    $this->fetchProcedures();
                    break;

                case 'triggers':
                    $this->fetchTriggers();
                    break;
            }
        }

        return $this;
    }

    /**
     * Fetch all available database objects
     */
    public function fetchAll(): static
    {
        if (! $this->ensureReady()) {
            return $this;
        }
        $this->fetchViews();
        $this->fetchProcedures();
        $this->fetchTriggers();

        return $this;
    }

    /**
     * Fetch database views
     */
    protected function fetchViews(): void
    {
        $driver = get_class($this->db);

        if (str_contains($driver, 'MySQLi')) {
            $this->fetchMySQLViews();
        } elseif (str_contains($driver, 'Postgre')) {
            $this->fetchPostgreViews();
        } elseif (str_contains($driver, 'SQLite3')) {
            $this->fetchSQLiteViews();
        } else {
            // Generic approach
            $this->fetchGenericViews();
        }
    }

    /**
     * Fetch MySQL views
     */
    protected function fetchMySQLViews(): void
    {
        $query = 'SELECT
                    TABLE_NAME as view_name,
                    VIEW_DEFINITION as definition,
                    IS_UPDATABLE as updatable,
                    SECURITY_TYPE as security_type
                  FROM INFORMATION_SCHEMA.VIEWS
                  WHERE TABLE_SCHEMA = DATABASE()';
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->views) {
            $this->objects->views = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $view             = new View($row['view_name']);
            $view->definition = $row['definition'];
            $view->updatable  = strtoupper($row['updatable']) === 'YES';
            $view->security   = $row['security_type'];

            // Parse dependencies from definition
            $this->parseViewDependencies($view);

            $this->objects->views->{$row['view_name']} = $view;
        }
    }

    /**
     * Fetch PostgreSQL views
     */
    protected function fetchPostgreViews(): void
    {
        $query = "SELECT
                    viewname as view_name,
                    definition
                  FROM pg_views
                  WHERE schemaname = 'public'";
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->views) {
            $this->objects->views = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $view             = new View($row['view_name']);
            $view->definition = $row['definition'];

            // Parse dependencies from definition
            $this->parseViewDependencies($view);

            $this->objects->views->{$row['view_name']} = $view;
        }
    }

    /**
     * Fetch SQLite views
     */
    protected function fetchSQLiteViews(): void
    {
        $query  = "SELECT name, sql FROM sqlite_master WHERE type = 'view'";
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->views) {
            $this->objects->views = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $view             = new View($row['name']);
            $view->definition = $row['sql'];

            // Parse dependencies from definition
            $this->parseViewDependencies($view);

            $this->objects->views->{$row['name']} = $view;
        }
    }

    /**
     * Generic view fetching fallback
     */
    protected function fetchGenericViews(): void
    {
        // Fallback - try to get view information from system tables
        // This is a basic implementation
        if (! $this->objects->views) {
            $this->objects->views = new Mergeable();
        }
    }

    /**
     * Fetch stored procedures and functions
     */
    protected function fetchProcedures(): void
    {
        $driver = get_class($this->db);

        if (str_contains($driver, 'MySQLi')) {
            $this->fetchMySQLProcedures();
        } elseif (str_contains($driver, 'Postgre')) {
            $this->fetchPostgreProcedures();
        } else {
            // Generic approach
            $this->fetchGenericProcedures();
        }
    }

    /**
     * Fetch MySQL procedures and functions
     */
    protected function fetchMySQLProcedures(): void
    {
        $query = 'SELECT
                    ROUTINE_NAME as name,
                    ROUTINE_TYPE as type,
                    ROUTINE_DEFINITION as definition,
                    ROUTINE_COMMENT as comment,
                    SECURITY_TYPE as security,
                    IS_DETERMINISTIC as deterministic,
                    SQL_DATA_ACCESS as data_access
                  FROM INFORMATION_SCHEMA.ROUTINES
                  WHERE ROUTINE_SCHEMA = DATABASE()';
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->procedures) {
            $this->objects->procedures = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $procedure                = new Procedure($row['name']);
            $procedure->type          = $row['type'];
            $procedure->definition    = $row['definition'];
            $procedure->comment       = $row['comment'];
            $procedure->security      = $row['security'];
            $procedure->deterministic = $row['deterministic'] === 'YES';
            $procedure->dataAccess    = $row['data_access'];

            $this->objects->procedures->{$row['name']} = $procedure;
        }
    }

    /**
     * Fetch PostgreSQL procedures and functions
     */
    protected function fetchPostgreProcedures(): void
    {
        $query = "SELECT
                    proname as name,
                    CASE WHEN prokind = 'f' THEN 'FUNCTION' ELSE 'PROCEDURE' END as type,
                    prosrc as definition,
                    prolang::regclass::text as language
                  FROM pg_proc p
                  JOIN pg_namespace n ON p.pronamespace = n.oid
                  WHERE n.nspname = 'public'";
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->procedures) {
            $this->objects->procedures = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $procedure             = new Procedure($row['name']);
            $procedure->type       = $row['type'];
            $procedure->definition = $row['definition'];
            $procedure->language   = $row['language'];

            $this->objects->procedures->{$row['name']} = $procedure;
        }
    }

    /**
     * Generic procedure fetching fallback
     */
    protected function fetchGenericProcedures(): void
    {
        if (! $this->objects->procedures) {
            $this->objects->procedures = new Mergeable();
        }
    }

    /**
     * Fetch database triggers
     */
    protected function fetchTriggers(): void
    {
        $driver = get_class($this->db);

        if (str_contains($driver, 'MySQLi')) {
            $this->fetchMySQLTriggers();
        } elseif (str_contains($driver, 'Postgre')) {
            $this->fetchPostgreTriggers();
        } elseif (str_contains($driver, 'SQLite3')) {
            $this->fetchSQLiteTriggers();
        } else {
            $this->fetchGenericTriggers();
        }
    }

    /**
     * Fetch MySQL triggers
     */
    protected function fetchMySQLTriggers(): void
    {
        $query = 'SELECT
                    TRIGGER_NAME as name,
                    EVENT_OBJECT_TABLE as table_name,
                    ACTION_TIMING as timing,
                    EVENT_MANIPULATION as event,
                    ACTION_STATEMENT as definition
                  FROM INFORMATION_SCHEMA.TRIGGERS
                  WHERE TRIGGER_SCHEMA = DATABASE()';
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->triggers) {
            $this->objects->triggers = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $trigger             = new Trigger($row['name']);
            $trigger->table      = $row['table_name'];
            $trigger->timing     = $row['timing'];
            $trigger->events     = [$row['event']];
            $trigger->definition = $row['definition'];

            $this->objects->triggers->{$row['name']} = $trigger;
        }
    }

    /**
     * Fetch PostgreSQL triggers
     */
    protected function fetchPostgreTriggers(): void
    {
        $query = "SELECT
                    t.tgname as name,
                    c.relname as table_name,
                    CASE t.tgtype & 66
                        WHEN 2 THEN 'BEFORE'
                        WHEN 64 THEN 'INSTEAD OF'
                        ELSE 'AFTER'
                    END as timing,
                    pg_get_triggerdef(t.oid) as definition
                  FROM pg_trigger t
                  JOIN pg_class c ON t.tgrelid = c.oid
                  JOIN pg_namespace n ON c.relnamespace = n.oid
                  WHERE NOT t.tgisinternal AND n.nspname = 'public'";
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->triggers) {
            $this->objects->triggers = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $trigger             = new Trigger($row['name']);
            $trigger->table      = $row['table_name'];
            $trigger->timing     = $row['timing'];
            $trigger->definition = $row['definition'];

            $this->objects->triggers->{$row['name']} = $trigger;
        }
    }

    /**
     * Fetch SQLite triggers
     */
    protected function fetchSQLiteTriggers(): void
    {
        $query  = "SELECT name, tbl_name as table_name, sql FROM sqlite_master WHERE type = 'trigger'";
        $result = $this->db->query($query);
        if (! is_object($result) || ! method_exists($result, 'getResultArray')) {
            return;
        }

        if (! $this->objects->triggers) {
            $this->objects->triggers = new Mergeable();
        }

        foreach ($result->getResultArray() as $row) {
            $trigger             = new Trigger($row['name']);
            $trigger->table      = $row['table_name'];
            $trigger->definition = $row['sql'];

            $this->objects->triggers->{$row['name']} = $trigger;
        }
    }

    /**
     * Generic trigger fetching fallback
     */
    protected function fetchGenericTriggers(): void
    {
        if (! $this->objects->triggers) {
            $this->objects->triggers = new Mergeable();
        }
    }

    /**
     * Parse view dependencies from definition
     */
    protected function parseViewDependencies(View $view): void
    {
        if (! $view->definition) {
            return;
        }

        // Simple regex to find table references in SELECT statements
        // This is a basic implementation and could be improved
        preg_match_all('/FROM\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $view->definition, $matches);
        if (! empty($matches[1])) {
            $view->dependencies = array_unique($matches[1]);
        }

        preg_match_all('/JOIN\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $view->definition, $matches);
        if (! empty($matches[1])) {
            $view->dependencies = array_unique(array_merge($view->dependencies, $matches[1]));
        }
    }

    /**
     * Return the count of all objects
     */
    public function count(): int
    {
        if ($this->objects === null) {
            return 0;
        }

        $count = 0;
        if (property_exists($this->objects, 'views')) {
            $count += count($this->objects->views);
        }
        if (property_exists($this->objects, 'procedures')) {
            $count += count($this->objects->procedures);
        }
        if (property_exists($this->objects, 'triggers')) {
            $count += count($this->objects->triggers);
        }

        return $count;
    }

    /**
     * Return the objects for iteration
     */
    public function getIterator(): Mergeable
    {
        if ($this->objects === null) {
            $this->objects = new Mergeable();
        }
        $this->fetchAll();

        return $this->objects;
    }

    /**
     * Magic getter for accessing objects
     */
    public function __get(string $name): mixed
    {
        if ($this->objects && property_exists($this->objects, $name)) {
            return $this->objects->{$name};
        }

        return null;
    }

    /**
     * Magic checker for object existence
     */
    public function __isset(string $name): bool
    {
        return $this->objects && property_exists($this->objects, $name);
    }
}
