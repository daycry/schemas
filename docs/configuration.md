# Configuration Reference

The Schemas library uses a streamlined configuration system that provides powerful functionality while maintaining simplicity and performance.

## Overview

The main configuration file is located at `app/Config/Schemas.php`. You can copy the example from `vendor/daycry/schemas/examples/Schemas.php` as a starting point.

## Basic Configuration

### Core Settings

```php
<?php

namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    /**
     * Default database group to use
     */
    public string $defaultGroup = 'default';

    /**
     * Tables to ignore when creating the schema
     */
    public array $ignoredTables = ['migrations'];

    /**
     * Specific tables to include (if set, only these tables will be processed)
     */
    public array $includedTables = [];

    /**
     * Whether to continue instead of throwing exceptions
     */
    public bool $silent = true;
}
```

### Automation Settings

```php
/**
 * Which tasks to automate when a schema is not available from the service
 */
public array $automate = [
    'draft'   => true,  // Auto-draft when schema missing
    'archive' => true,  // Auto-archive generated schemas  
    'read'    => true,  // Auto-read from archives
];
```

## Advanced Configuration

### Cache Configuration

```php
/**
 * Cache configuration
 */
public array $cache = [
    'enabled' => false,           // Enable/disable caching
    'handler' => 'file',          // Cache handler (file, database, redis, etc.)
    'ttl' => 3600,               // Time to live in seconds
    'prefix' => 'schemas_'        // Cache key prefix
];
```

**Available cache handlers:**
- `file` - File-based caching (default)
- `database` - Database-based caching
- `redis` - Redis caching
- `memcached` - Memcached caching

### Logging Configuration

```php
/**
 * Logging configuration
 */
public array $logging = [
    'enabled' => false,           // Enable/disable logging
    'level' => 'info'            // Log level (debug, info, warning, error)
];
```

### Relationship Detection

```php
/**
 * Relationship detection settings
 */
public array $relationships = [
    'enabled' => true,                    // Enable relationship detection
    'detect_polymorphic' => true,         // Detect polymorphic relationships
    'detect_many_to_many' => true         // Detect many-to-many relationships
];
```

### Schema Validation

```php
/**
 * Validation settings
 */
public array $validation = [
    'enabled' => false,           // Enable schema validation
    'strict_mode' => false        // Use strict validation rules
];
```

### Plugin System

```php
/**
 * Plugin system configuration
 */
public array $plugins = [
    'enabled' => true,                    // Enable plugin system
    'auto_discovery' => true,             // Auto-discover plugins
    'discovery_paths' => [                // Plugin discovery paths
        APPPATH . 'Plugins/Schemas'
    ],
    'auto_load' => []                     // Plugins to auto-load
];
```

### Async Processing

```php
/**
 * Async processing configuration
 */
public array $async = [
    'enabled' => false,           // Enable async processing
    'max_concurrent_jobs' => 3,   // Maximum concurrent jobs
    'job_timeout' => 300          // Job timeout in seconds
];
```

### Development Tools

```php
/**
 * Development tools configuration
 */
public array $development = [
    'schema_diff_tool' => true,       // Enable schema diff tool
    'migration_generator' => true     // Enable migration generator
];
```

## Handler Configuration

### Draft Handlers

```php
/**
 * Handlers for the "draft" command
 */
public array $draftHandlers = [
    'database'  => DatabaseHandler::class,
    'directory' => DirectoryHandler::class,
    'model'     => ModelHandler::class,
];
```

### Archive Handlers

```php
/**
 * Handlers for the "archive" command
 */
public array $archiveHandlers = [
    'cache'     => CacheArchiveHandler::class,
    'file'      => JsonHandler::class,
];
```

### Read Handlers

```php
/**
 * Handlers for the "read" command
 */
public array $readHandlers = [
    'cache'     => CacheReadHandler::class,
    'json'      => JsonHandler::class,
    'directory' => DirectoryHandler::class,
];
```

## Environment-Specific Configuration

You can override configuration values for different environments:

```php
public function __construct()
{
    parent::__construct();
    
    if (ENVIRONMENT === 'production') {
        $this->cache['enabled'] = true;
        $this->cache['ttl'] = 7200;
        $this->logging['enabled'] = true;
    }
    
    if (ENVIRONMENT === 'development') {
        $this->development['schema_diff_tool'] = true;
        $this->logging['level'] = 'debug';
    }
}
```

## Performance Considerations

### Caching Best Practices

1. **Enable caching in production:**
   ```php
   $this->cache['enabled'] = true;
   $this->cache['ttl'] = 3600; // 1 hour
   ```

2. **Use appropriate cache handlers:**
   - `file` for single-server setups
   - `redis` or `memcached` for multi-server setups

3. **Set reasonable TTL values:**
   - Short TTL (300-600s) for frequently changing schemas
   - Long TTL (3600-7200s) for stable schemas

### Async Processing

Enable async processing for large databases:

```php
$this->async['enabled'] = true;
$this->async['max_concurrent_jobs'] = 5;
```

### Memory Optimization

For large schemas, consider:

```php
$this->silent = false; // Enable detailed output
$this->ignoredTables = ['logs', 'sessions', 'temp_*']; // Ignore temporary tables
```

## Migration from Older Versions

### Deprecated Properties

The following properties are deprecated but still supported:

- `$ttl` → Use `$cache['ttl']` instead

### Configuration Updates

Update your existing configuration:

```php
// Old format (still works)
public int $ttl = 3600;

// New format (recommended)
public array $cache = [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'schemas_'
];
```

## Troubleshooting

### Common Issues

1. **Cache not working:**
   - Verify cache handler is properly configured
   - Check file permissions for file-based cache
   - Ensure Redis/Memcached services are running

2. **Relationships not detected:**
   - Verify foreign key constraints exist
   - Check naming conventions (table_id format)
   - Enable relationship detection in config

3. **Performance issues:**
   - Enable caching
   - Use ignored tables to skip unnecessary tables
   - Consider async processing for large schemas

### Debug Mode

Enable debug logging for troubleshooting:

```php
$this->logging['enabled'] = true;
$this->logging['level'] = 'debug';
$this->silent = false;
```

## Examples

### Basic Setup
```php
public array $cache = ['enabled' => false];
public array $automate = ['draft' => true, 'archive' => false, 'read' => true];
```

### Production Setup
```php
public array $cache = [
    'enabled' => true,
    'handler' => 'redis',
    'ttl' => 3600,
    'prefix' => 'app_schemas_'
];

public array $logging = [
    'enabled' => true,
    'level' => 'warning'
];

public array $async = [
    'enabled' => true,
    'max_concurrent_jobs' => 5,
    'job_timeout' => 600
];
```

### Development Setup
```php
public array $development = [
    'schema_diff_tool' => true,
    'migration_generator' => true
];

public array $logging = [
    'enabled' => true,
    'level' => 'debug'
];

public bool $silent = false;
```
