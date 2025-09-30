<?php

declare(strict_types=1);

// Simple standalone script to dump the current drafted schema as JSON.
// Usage (from project root):
//   php dump_schema.php [handlers]
// Examples:
//   php dump_schema.php            # uses default draft handlers (config)
//   php dump_schema.php database   # only database handler
//   php dump_schema.php database,model
//
// Does not modify source or tests.

// Bootstrap CodeIgniter (assuming vendor autoload + app Starter)
require __DIR__ . '/vendor/autoload.php';

// Try to load the CodeIgniter framework if present
// Try to load project Paths or fallback to framework's default Paths
$basePaths = [
    __DIR__ . '/app/Config/Paths.php',
    __DIR__ . '/tests/_support/Config/Paths.php',
    __DIR__ . '/vendor/codeigniter4/framework/app/Config/Paths.php',
];
foreach ($basePaths as $pathsFile) {
    if (is_file($pathsFile)) {
        require_once $pathsFile;
        if (class_exists('Config\\Paths')) {
            break;
        }
    }
}

if (! class_exists('Config\\Paths')) {
    fwrite(STDERR, "Unable to locate CodeIgniter Paths class (checked local & vendor). Aborting.\n");
    exit(1);
}

$paths = new Config\Paths();
// Minimal bootstrap (define constants that CI expects and load Common)
chdir(dirname($paths->appDirectory));

define('APPPATH', rtrim($paths->appDirectory, '\\/') . '/');
define('SYSTEMPATH', rtrim($paths->systemDirectory, '\\/') . '/');
define('ROOTPATH', dirname(APPPATH) . '/');
define('WRITEPATH', ROOTPATH . 'writable/');
define('FCPATH', ROOTPATH . 'public/');
define('CONFIGPATH', APPPATH . 'Config/');
define('CI_DEBUG', false);
if (! defined('APP_NAMESPACE')) {
    define('APP_NAMESPACE', 'App');
}

// Core Common helpers
require SYSTEMPATH . 'Common.php';
// Load autoload config if exists (safeguarded)
if (is_file(CONFIGPATH . 'Autoload.php')) {
    require CONFIGPATH . 'Autoload.php';
}
// Register autoload if available
if (class_exists('Config\\Autoload')) {
    $loader = new \CodeIgniter\Autoloader\Autoloader();
    $loader->initialize(new Config\Autoload(), new Config\Modules());
    $loader->register();
}

use Daycry\Schemas\Schemas;
use Daycry\Schemas\Config\Schemas as SchemasConfig;

$config = new SchemasConfig();

// Parse handlers from CLI arg 1 (comma separated)
$handlersArg = $argv[1] ?? null;
$handlers    = null;
if ($handlersArg) {
    $handlers = array_filter(array_map('trim', explode(',', $handlersArg)));
    if ($handlers === []) {
        $handlers = null;
    }
}

$schemas = new Schemas($config, null);

// Try to load from cache first, fall back to draft if needed
$schema = $schemas->load();
if ($schema === null) {
    $schemas->draft($handlers);
    $schema = $schemas->get();
}

// Schema now may be lazy (tables load on access); for JSON export we can force load by iterating
$schema = $schemas->get();
if ($schema && is_object($schema->tables) && method_exists($schema->tables, 'fetchAll')) {
    // Optional: materialize all tables if reader supports it
    try { $schema->tables->fetchAll(); } catch (\Throwable $e) {}
}

if ($schema === null) {
    fwrite(STDERR, "No schema generated.\n");
    exit(2);
}

// Normalization helpers ---------------------------------------------------------------
$filterEmpty = function ($value) use (&$filterEmpty) {
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $k => $v) {
            $vv = $filterEmpty($v);
            if ($vv === null || $vv === [] ) {
                continue;
            }
            $clean[$k] = $vv;
        }
        return $clean;
    }
    if (is_object($value)) {
        $arr = [];
        foreach (get_object_vars($value) as $k => $v) {
            $vv = $filterEmpty($v);
            if ($vv === null || $vv === [] ) {
                continue;
            }
            $arr[$k] = $vv;
        }
        return $arr;
    }
    if ($value === '' || $value === null) {
        return null;
    }
    return $value;
};

$raw = $filterEmpty($schema);

// Pretty JSON (fallback if json_encode fails)
$json = json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';

echo $json . PHP_EOL;
