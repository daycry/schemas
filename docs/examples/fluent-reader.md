# Fluent Reader Example

Demonstrates chaining `fetch` and `fetchAll` on a reader handler.

```php
use Daycry\Schemas\Reader\Handlers\CacheHandler;

$config = config('Schemas');
$cache  = \Config\Services::cache();

$reader = new CacheHandler($config, $cache);

$reader
    ->fetch(['users'])
    ->fetch(['posts','comments'])
    ->fetchAll(); // ensure any remaining tables materialize

foreach ($reader as $table) {
    echo $table->name . PHP_EOL;
}
```

## Benefits
- Consistent fluent style with other modern PHP libraries.
- Encourages incremental population without losing chain flow.

## Tips
- Avoid redundant duplicate fetch calls; underlying implementation should ignore already loaded tables.
- Call `fetchAll()` at the end when you need completeness (e.g., enumeration or counting).
