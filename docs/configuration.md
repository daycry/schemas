# Configuration (Minimal Core)

This file intentionally replaces the previous large configuration reference. The canonical and only maintained configuration documentation now lives at:

`docs/api/configuration.md`

Removed legacy areas:
- plugins / auto discovery
- async processing / job queues
- development tooling (diff tool, migration generator)
- logging levels
- extended relationship detection toggles
- validation strict modes
- model handlers / handler registration arrays
- performance tuning flags

If upgrading, delete those properties from `app/Config/Schemas.php`; they are ignored by the minimal core.

Minimal example (`app/Config/Schemas.php`):

```php
<?php
namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public string $defaultGroup = 'default';
    public array  $ignoredTables = ['migrations'];
    public array  $includedTables = [];
    public bool   $silent = true; // suppress non-critical exceptions
    public array  $cache = [
        'enabled' => false,
        'ttl'     => 3600,
        'prefix'  => 'schemas_'
    ];
}
```

See the API configuration document for field explanations.
