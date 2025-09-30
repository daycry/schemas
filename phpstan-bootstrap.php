<?php
// PHPStan bootstrap stub file for helper functions & constants from CodeIgniter.
// These lightweight shims allow static analysis without pulling full framework context.

namespace {
    if (!defined('APPPATH')) {
        define('APPPATH', __DIR__ . '/app/');
    }
    if (!defined('ENVIRONMENT')) {
        define('ENVIRONMENT', 'testing');
    }

    if (!function_exists('service')) {
        function service(string $name)
        {
            return new class {};
        }
    }

    if (!function_exists('config')) {
        function config(string $name)
        {
            $class = 'Daycry\\Schemas\\Config\\Schemas';
            if ($name === 'Schemas' && class_exists($class)) {
                return new $class();
            }
            return new class {};
        }
    }

    if (!function_exists('db_connect')) {
        function db_connect(?string $group = null)
        {
            return new class {
                public function listTables(): array { return []; }
                public function getIndexData(string $table): array { return []; }
                public function getForeignKeyData(string $table): array { return []; }
                public function getFieldData(string $table): array { return []; }
                public function query(string $sql) { return false; }
            };
        }
    }

    if (!function_exists('helper')) {
        function helper($name): void {}
    }

    if (!function_exists('singular')) {
        function singular(string $word): string { return $word; }
    }
    if (!function_exists('plural')) {
        function plural(string $word): string { return $word . 's'; }
    }

    if (!function_exists('lang')) {
        function lang(string $line, array $args = []): string { return $line; }
    }
}

namespace Config {
    class Services {
        public static function cache() { return new class { public function save($k,$v,$ttl=0){return true;} public function get($k){return null;} }; }
        public static function autoloader() { return new class {}; }
        public static function locator() { return new class { public function listFiles(string $prefix): array { return []; } }; }
    }
}
