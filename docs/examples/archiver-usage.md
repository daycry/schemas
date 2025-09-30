# Archiver Usage Example

Persist a drafted schema and reload it later for faster startup.

## Cache Archiver
```php
use Daycry\Schemas\Archiver\Handlers\CacheArchiver;
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;

$config = config('Schemas');
$cache  = \Config\Services::cache();

// Draft fresh schema
$drafter = new DatabaseHandler($config, \Config\Database::connect());
$schema  = $drafter->draft();

// Archive
$archiver = new CacheArchiver($config, $cache);
$archiver->archive($schema);

echo "Archived schema to cache." . PHP_EOL;

// Later retrieval
$restored = $archiver->retrieve();
```

## File Archiver (JSON)
If a file archiver exists in the build you can do:
```php
use Daycry\Schemas\Archiver\Handlers\FileArchiver;

$fileArchiver = new FileArchiver($config);
$fileArchiver->archive($schema);
$fromFile = $fileArchiver->retrieve();
```

## Round Trip Verification
```php
foreach ($schema->tables as $name => $table) {
    if (!property_exists($restored->tables, $name)) {
        echo "Missing after restore: {$name}\n";
    }
}
```

## Notes
- Only currently present archiver handlers are supported; removed formats are intentionally undocumented.
- Re-archive after structural DB changes to keep cache synchronized.
