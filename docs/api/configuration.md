# API Reference - Configuration

This document provides comprehensive information about all configuration options available in the Daycry Schemas library.

## Main Configuration Class

### Schemas Config

The primary configuration class located at `Config\Schemas.php`:

```php
namespace Daycry\Schemas\Config;

use CodeIgniter\Config\BaseConfig;

class Schemas extends BaseConfig
{
    // Database Configuration
    public string $defaultGroup = 'default';
    public array $ignoredTables = [];
    public array $includedTables = [];
    public string $tablePrefix = '';
    
    // Core Settings
    public bool $silent = false;
    public bool $enableValidation = false;
    public bool $enablePerformanceAnalysis = false;
    public bool $enableIntelligentCache = false;
    public bool $enableRelationDetection = true;
    
    // Cache Configuration
    public array $cache = [
        'enabled' => false,
        'handler' => 'file',
        'ttl' => 3600,
        'prefix' => 'schemas_',
        'tags' => ['schemas'],
        'versioning' => false,
        'compression' => false
    ];
    
    // Logging Configuration
    public array $logging = [
        'enabled' => false,
        'level' => 'info',
        'channels' => ['file'],
        'performance_metrics' => true,
        'query_logging' => false
    ];
    
    // Performance Analysis Settings
    public array $performance = [
        'enabled' => false,
        'analysis_depth' => 'full',
        'score_weights' => [
            'indexes' => 0.3,
            'foreign_keys' => 0.2,
            'data_types' => 0.2,
            'table_structure' => 0.15,
            'query_patterns' => 0.15
        ],
        'recommendations' => true,
        'auto_optimize' => false
    ];
    
    // Relationship Detection Settings
    public array $relationships = [
        'enabled' => true,
        'detect_polymorphic' => true,
        'detect_self_referencing' => true,
        'detect_many_to_many' => true,
        'detect_hierarchical' => true,
        'naming_conventions' => [
            'foreign_key_suffix' => '_id',
            'pivot_table_pattern' => '{table1}_{table2}',
            'polymorphic_type_suffix' => '_type',
            'polymorphic_id_suffix' => '_id'
        ]
    ];
    
    // Validation Settings
    public array $validation = [
        'enabled' => false,
        'strict_mode' => false,
        'rules' => [
            'circular_references' => true,
            'foreign_key_consistency' => true,
            'data_type_validation' => true,
            'index_validation' => true,
            'constraint_validation' => true,
            'naming_conventions' => false
        ],
        'auto_fix' => false,
        'custom_rules' => []
    ];
    
    // Advanced Features
    public array $advanced = [
        'schema_versioning' => false,
        'migration_support' => false,
        'backup_schemas' => false,
        'compression' => false,
        'encryption' => false
    ];
}
```

## Detailed Configuration Options

### Database Configuration

#### Default Group
```php
public string $defaultGroup = 'default';
```
- **Description**: Default database group to use from `Config\Database`
- **Default**: `'default'`
- **Example**: `'production'`, `'testing'`

#### Ignored Tables
```php
public array $ignoredTables = [];
```
# API Reference - Configuration (Minimal Core)

This document describes only the configuration that still applies to the trimmed core. All legacy feature flags (validation, performance analysis, intelligent cache manager, advanced relation detection, logging metrics, versioning, advanced relationships, migration generation, encryption/compression toggles) have been removed from code and SHOULD NOT appear in your `Config\Schemas` class anymore.

Minimal example `Config\Schemas.php`:
public array $includedTables = [];

Minimal example `Config\Schemas.php`:
class Schemas extends BaseConfig
{
    public string $defaultGroup = 'default';
    public array  $ignoredTables = [];
    public string $tablePrefix   = '';

    // Silent mode: collect errors instead of throwing
    public bool $silent = false;

    // Basic cache settings (used by CacheArchiver / CacheHandler if present)
    public array $cache = [
        'enabled' => false,
        'handler' => 'file', // or 'redis', 'memcached' depending on CI services
        'ttl'     => 3600,
        'prefix'  => 'schemas_',
    ];
}
    'query_logging' => false          // Log database queries
];

Legacy feature toggles (validation, performance, intelligent cache, relation detection) were removed; delete them from older configs.
- `'critical'`: Critical conditions
### Cache Configuration (Basic)
- `'error'`: Error conditions
public array $cache = [
    'enabled' => false,
    'handler' => 'file',
    'ttl'     => 3600,
    'prefix'  => 'schemas_',
];
- `'slack'`: Slack notifications
Supported handlers depend on CI4 cache configuration (e.g., file, redis, memcached). No tags, versioning, or compression layer in minimal core.
    'enabled' => false,                 // Enable performance analysis
    'analysis_depth' => 'full',        // Analysis depth
### Logging
No built-in logging layer; wrap operations with your own PSR-3 logger if desired.
- `query_patterns`: Query pattern analysis score

### Removed Advanced Settings
Performance scoring, validation rule sets, advanced relation pattern detection, schema versioning, encryption/compression flags were removed.
    ]
Advanced sections intentionally omitted.
- `pivot_table_pattern`: Pattern for pivot tables (e.g., `users_roles`)
- `polymorphic_type_suffix`: Suffix for polymorphic type columns
- `polymorphic_id_suffix`: Suffix for polymorphic ID columns
### Development Environment
Disable cache for iterative schema changes:
```php
public array $cache = ['enabled' => false];
```
        'index_validation' => true,
        'constraint_validation' => true,
        'naming_conventions' => false
### Production Environment
```php
public bool $silent = true; // collect non-critical errors silently
public array $cache = [ 'enabled' => true, 'handler' => 'redis', 'ttl' => 86400 ];
```
- `naming_conventions`: Validate naming conventions

#### Custom Rules Example
### Testing Environment
```php
public array $cache = ['enabled' => false];
public array $ignoredTables = ['migrations'];
```
```

### Advanced Features
### Custom Configuration
```php
$config = config('Schemas');
$config->cache['enabled'] = true;
$schemas = new \Daycry\Schemas\Schemas($config);
    'backup_schemas' => false,         // Auto-backup schemas
    'compression' => false,            // Compress stored schemas
// Invalid configuration examples:
$config->cache['ttl'] = -1;            // Invalid TTL
$config->defaultGroup = 'nonexistent'; // Invalid DB group
## Environment-Specific Configuration
2. **Cache Strategy**: Enable caching in production, disable in development for freshness.
3. **Keep Lean**: Avoid re-adding removed flags unless you reintroduce corresponding code.
4. **Security**: Treat archived schemas like metadata; handle according to your app's policies.

This configuration reference reflects the currently supported minimal options.
    'enabled' => true,
    'level' => 'debug',
    'performance_metrics' => true,
    'query_logging' => true
];
public array $cache = [
    'enabled' => false  // Disable cache for fresh data
];
```

### Production Environment
```php
// Config/Schemas.php (Production)
public bool $silent = true;
public array $cache = [
    'enabled' => true,
    'handler' => 'redis',
    'ttl' => 86400,
    'compression' => true
];
public array $logging = [
    'enabled' => true,
    'level' => 'error',
    'channels' => ['file', 'email']
];
```

### Testing Environment
```php
// Config/Schemas.php (Testing)
public array $cache = [
    'enabled' => false
];
public array $logging = [
    'enabled' => false
];
public bool $enableValidation = true;
public array $ignoredTables = ['migrations', 'cache'];
```

## Configuration Loading

### Default Loading
```php
// Uses default configuration
$schemas = new \Daycry\Schemas\Schemas();
```

### Custom Configuration
```php
// Load custom configuration
$config = config('Schemas');
$config->enableValidation = true;
$config->cache['enabled'] = true;

$schemas = new \Daycry\Schemas\Schemas($config);
```

### Runtime Configuration Changes
```php
$schemas = new \Daycry\Schemas\Schemas();

// Change database group
$schemas->setDatabase('testing');

// Get current configuration
$config = $schemas->getConfig();
$config->silent = true;
```

## Configuration Validation

The library automatically validates configuration settings and will throw `SchemasException` for invalid configurations:

```php
// Invalid configuration examples that will throw exceptions:
$config->cache['ttl'] = -1;                    // Invalid TTL
$config->performance['score_weights'] = [];    // Missing weights
$config->defaultGroup = 'nonexistent';         // Invalid database group
```

## Configuration Best Practices

1. **Environment-Specific Settings**: Use different configurations for development, testing, and production
2. **Cache Strategy**: Enable caching in production, disable in development
3. **Logging Levels**: Use debug logging in development, error logging in production
4. **Performance Analysis**: Enable in development for optimization insights
5. **Validation**: Enable during development and testing to catch schema issues early
6. **Security**: Enable encryption for sensitive schema data in production

This configuration reference provides complete control over all aspects of the Daycry Schemas library behavior and features.
