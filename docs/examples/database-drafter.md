# Database Drafter Example

Draft a live database schema into in-memory structures.

## Basic Draft
```php
use Daycry\Schemas\Drafter\Handlers\DatabaseHandler;

$config = config('Schemas');
$db     = \Config\Database::connect($config->defaultGroup);

$drafter = new DatabaseHandler($config, $db);
$schema  = $drafter->draft();

echo 'Tables: ' . count($schema->tables) . PHP_EOL;
```

## Accessing Table Details
```php
$users = $schema->tables->users ?? null;
if ($users) {
    foreach ($users->fields as $field) {
        echo "{$field->name} ({$field->type})" . PHP_EOL;
    }
}
```

## Filtering Tables (if supported)
```php
$drafter->setTableFilter(['users','posts']);
$filtered = $drafter->draft();
```

## Foreign Keys & Normalization
Foreign key column names that appear as driver arrays are normalized to a single string when structures are constructed.

## Relations
Basic relations inferred by the drafter are available under `$table->relations` when detection logic exists in the current build.
