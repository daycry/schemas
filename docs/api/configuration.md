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
- **Description**: Tables to exclude from schema operations
- **Default**: `[]` (empty array)
- **Example**: `['migrations', 'cache', 'sessions']`

#### Included Tables
```php
public array $includedTables = [];
```
- **Description**: Specific tables to include (if set, only these tables will be processed)
- **Default**: `[]` (empty array - includes all tables)
- **Example**: `['users', 'posts', 'categories']`

#### Table Prefix
```php
public string $tablePrefix = '';
```
- **Description**: Prefix to add/remove from table names
- **Default**: `''` (empty string)
- **Example**: `'app_'`, `'cms_'`

### Core Settings

#### Silent Mode
```php
public bool $silent = false;
```
- **Description**: Suppress error output and exceptions
- **Default**: `false`
- **Values**: `true` (silent), `false` (verbose)

#### Enable Validation
```php
public bool $enableValidation = false;
```
- **Description**: Enable schema validation features
- **Default**: `false`
- **Impact**: Enables `SchemaValidator` functionality

#### Enable Performance Analysis
```php
public bool $enablePerformanceAnalysis = false;
```
- **Description**: Enable performance analysis features
- **Default**: `false`
- **Impact**: Enables `PerformanceAnalyzer` functionality

#### Enable Intelligent Cache
```php
public bool $enableIntelligentCache = false;
```
- **Description**: Enable advanced caching with versioning and tags
- **Default**: `false`
- **Impact**: Enables `IntelligentCacheManager` functionality

#### Enable Relation Detection
```php
public bool $enableRelationDetection = true;
```
- **Description**: Enable automatic relationship detection
- **Default**: `true`
- **Impact**: Enables `AdvancedRelationDetector` functionality

### Cache Configuration

```php
public array $cache = [
    'enabled' => false,              // Enable caching
    'handler' => 'file',             // Cache handler (file, redis, memcached)
    'ttl' => 3600,                  // Time to live in seconds
    'prefix' => 'schemas_',         // Cache key prefix
    'tags' => ['schemas'],          // Cache tags for invalidation
    'versioning' => false,          // Enable cache versioning
    'compression' => false          // Enable cache compression
];
```

#### Cache Handler Options
- `'file'`: File-based caching
- `'redis'`: Redis caching
- `'memcached'`: Memcached caching
- `'database'`: Database caching
- `'array'`: In-memory caching (for testing)

#### Cache TTL Examples
- `3600`: 1 hour
- `86400`: 24 hours
- `604800`: 1 week
- `0`: No expiration

### Logging Configuration

```php
public array $logging = [
    'enabled' => false,                 // Enable logging
    'level' => 'info',                 // Log level
    'channels' => ['file'],            // Log channels
    'performance_metrics' => true,     // Log performance metrics
    'query_logging' => false          // Log database queries
];
```

#### Log Levels
- `'emergency'`: System is unusable
- `'alert'`: Action must be taken immediately
- `'critical'`: Critical conditions
- `'error'`: Error conditions
- `'warning'`: Warning conditions
- `'notice'`: Normal but significant condition
- `'info'`: Informational messages
- `'debug'`: Debug-level messages

#### Log Channels
- `'file'`: File logging
- `'database'`: Database logging
- `'email'`: Email notifications
- `'slack'`: Slack notifications

### Performance Analysis Settings

```php
public array $performance = [
    'enabled' => false,                 // Enable performance analysis
    'analysis_depth' => 'full',        // Analysis depth
    'score_weights' => [               // Scoring weights
        'indexes' => 0.3,
        'foreign_keys' => 0.2,
        'data_types' => 0.2,
        'table_structure' => 0.15,
        'query_patterns' => 0.15
    ],
    'recommendations' => true,          // Generate recommendations
    'auto_optimize' => false           // Auto-apply optimizations
];
```

#### Analysis Depth Options
- `'basic'`: Basic analysis (indexes and foreign keys)
- `'standard'`: Standard analysis (includes data types)
- `'full'`: Full analysis (all components)
- `'custom'`: Custom analysis (specify components)

#### Score Weights
Each component's weight in the overall performance score (must sum to 1.0):
- `indexes`: Index optimization score
- `foreign_keys`: Foreign key performance score
- `data_types`: Data type efficiency score
- `table_structure`: Table structure score
- `query_patterns`: Query pattern analysis score

### Relationship Detection Settings

```php
public array $relationships = [
    'enabled' => true,                  // Enable relationship detection
    'detect_polymorphic' => true,      // Detect polymorphic relationships
    'detect_self_referencing' => true, // Detect self-referencing relationships
    'detect_many_to_many' => true,     // Detect many-to-many relationships
    'detect_hierarchical' => true,     // Detect hierarchical structures
    'naming_conventions' => [          // Naming convention patterns
        'foreign_key_suffix' => '_id',
        'pivot_table_pattern' => '{table1}_{table2}',
        'polymorphic_type_suffix' => '_type',
        'polymorphic_id_suffix' => '_id'
    ]
];
```

#### Naming Convention Patterns
- `foreign_key_suffix`: Suffix for foreign key columns (e.g., `user_id`)
- `pivot_table_pattern`: Pattern for pivot tables (e.g., `users_roles`)
- `polymorphic_type_suffix`: Suffix for polymorphic type columns
- `polymorphic_id_suffix`: Suffix for polymorphic ID columns

### Validation Settings

```php
public array $validation = [
    'enabled' => false,                 // Enable validation
    'strict_mode' => false,            // Enable strict validation
    'rules' => [                       // Validation rules
        'circular_references' => true,
        'foreign_key_consistency' => true,
        'data_type_validation' => true,
        'index_validation' => true,
        'constraint_validation' => true,
        'naming_conventions' => false
    ],
    'auto_fix' => false,               // Auto-fix issues
    'custom_rules' => []               // Custom validation rules
];
```

#### Validation Rules
- `circular_references`: Detect circular foreign key references
- `foreign_key_consistency`: Validate foreign key constraints
- `data_type_validation`: Validate data type consistency
- `index_validation`: Validate index effectiveness
- `constraint_validation`: Validate table constraints
- `naming_conventions`: Validate naming conventions

#### Custom Rules Example
```php
'custom_rules' => [
    'table_name_prefix' => function($table) {
        return str_starts_with($table->name, 'app_');
    },
    'required_timestamps' => function($table) {
        return $table->hasField('created_at') && $table->hasField('updated_at');
    }
]
```

### Advanced Features

```php
public array $advanced = [
    'schema_versioning' => false,       // Enable schema versioning
    'migration_support' => false,      // Enable migration generation
    'backup_schemas' => false,         // Auto-backup schemas
    'compression' => false,            // Compress stored schemas
    'encryption' => false             // Encrypt stored schemas
];
```

## Environment-Specific Configuration

### Development Environment
```php
// Config/Schemas.php (Development)
public bool $enableValidation = true;
public bool $enablePerformanceAnalysis = true;
public array $logging = [
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
