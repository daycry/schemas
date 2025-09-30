# Cache Reader Example

Demonstrates reading schema tables from a cache archive using the fluent reader interface.

## Prerequisites
- A schema previously archived via `CacheArchiver` (see archiver-usage example) OR an initialized cache containing table entries.
- Cache service configured in CodeIgniter.

## Basic Usage
```php
use Daycry\Schemas\Reader\Handlers\CacheHandler;

$config = config('Schemas');
$cache  = \Config\Services::cache();

$reader = new CacheHandler($config, $cache);

// Fluent fetch of specific tables then load all remaining
$reader->fetch(['users'])
       ->fetch(['posts'])
       ->fetchAll();

$tables = $reader->getTables();

echo 'Loaded tables: ' . count($tables) . PHP_EOL;
foreach ($tables as $table) {
    echo "- {$table->name}\n";
}
```

## Handling Cache Miss
```php
if (!$reader->getTables() || $reader->count() === 0) {
    echo "Cache empty – draft and re-archive first.";    
}
```

## Iteration
`CacheHandler` implements `IteratorAggregate` & `Countable`, so you can iterate directly:
```php
foreach ($reader as $table) {
    // $table is a Table structure
}
```

## Notes
- `fetch([])` is a no-op; call `fetchAll()` to ensure remaining tables are materialized.
- Cache key strategy depends on how the archiver stored tables (see archiver example).
