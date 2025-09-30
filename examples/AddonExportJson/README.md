## Example Addon: JSON Export Archiver

This folder demonstrates how to extend the minimal core by adding a custom archive handler (in this case exporting a schema snapshot to a JSON file).

Core intentionally ships only with cache archiving. External export formats belong in addons or application code.

### Steps to Integrate (Application-Level)

1. Copy `JsonFileArchiver.php` into your application (e.g. `app/Libraries/Schemas/JsonFileArchiver.php`) or create a separate Composer package.
2. Update your `app/Config/Schemas.php` to register a new archive mode, for example:

```php
public array $archiveHandlers = [
    'cache' => \Daycry\Schemas\Archiver\Handlers\CacheHandler::class,
    // New export mode (single handler)
    'export_json' => \Examples\Schemas\AddonExportJson\JsonFileArchiver::class,
];
```

3. Archive using the new mode:

```php
$schemas = service('schemas');
$schemas->draft()->archive('export_json');
```

This will create (by default) `writable/schema-export.json`.

### Packaging as an Addon

If distributing as a Composer package:

* Namespace the class (e.g. `Daycry\SchemasExportJson`)
* Provide a small README with the config snippet
* (Optional) Add a spark command: `schemas:export-json` that resolves the service and runs the archive mode

### Design Notes

* Implements `ArchiverInterface` only – no import logic to keep scope tiny
* Avoids adding dependencies; relies on core structures only
* Mirrors selective logic from the full JSON handler pattern without baking it into core

### Extending Further

* Add an `import(string $path): ?Schema` helper in a separate Reader handler
* Support compression (e.g. GZip) by appending `.gz` and piping through `gzencode`
* Include a checksum block for integrity validation

---

This is an example only; feel free to tailor it to your application's deployment & distribution needs.
