# Migration Guide

This guide helps existing users migrate to the new configuration system while maintaining backward compatibility.

## Overview

The Schemas library has been modernized with a new structured configuration system. **All existing code will continue to work without changes**, but we recommend migrating to the new configuration format for better organization and new features.

## Configuration Changes

### Old Configuration (Still Supported)
```php
<?php

namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public bool $silent = true;
    public int $ttl = 14400; // ❌ DEPRECATED: This property has been removed
    // Other legacy configurations...
}
```

### New Configuration (Recommended)
```php
<?php

namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public bool $silent = true; // ✅ Still supported
    
    // ✅ New structured configuration
    public array $cache = [
        'enabled' => true,
        'handler' => 'file',
        'ttl' => 3600,              // ✅ Replaces the old $ttl property
        'prefix' => 'schemas_',
        'tags' => ['schemas'],
        'versioning' => false,
        'compression' => false
    ];
    
    public array $logging = [
        'enabled' => true,
        'level' => 'info',
        'channels' => ['file'],
        'performance_metrics' => true,
        'query_logging' => false
    ];
    
    // Additional new configurations available...
}
```

## Breaking Changes

### ❌ Removed Properties

1. **`public int $ttl`** - This property has been removed
   - **Migration:** Use `$cache['ttl']` instead
   - **Impact:** If you were directly accessing `$config->ttl`, update to `$config->cache['ttl']`

### Code Migration Examples

#### Before (Deprecated)
```php
// ❌ This will no longer work
$ttl = $config->ttl;
```

#### After (New Way)
```php
// ✅ Use the new cache configuration
$ttl = $config->cache['ttl'];

// ✅ With fallback for safety
$ttl = $config->cache['ttl'] ?? 3600;
```

## New Features Available

### 1. Enhanced Cache Control
```php
public array $cache = [
    'enabled' => true,          // Enable/disable caching
    'handler' => 'file',        // Cache handler type
    'ttl' => 3600,             // Cache lifetime (replaces old $ttl)
    'prefix' => 'schemas_',     // Cache key prefix
    'tags' => ['schemas'],      // Cache tags for invalidation
    'versioning' => false,      // Version-based cache invalidation
    'compression' => false      // Compress cached data
];
```

### 2. Detailed Logging
```php
public array $logging = [
    'enabled' => true,              // Enable logging
    'level' => 'info',              // Log level (debug, info, warning, error)
    'channels' => ['file'],         // Log channels
    'performance_metrics' => true,  // Log performance data
    'query_logging' => false        // Log database queries
];
```

### 3. Performance Analysis
```php
public array $performance = [
    'enabled' => false,             // Enable performance analysis
    'analysis_depth' => 'full',     // Analysis depth (basic, full, detailed)
    'score_weights' => [...],       // Custom scoring weights
    'recommendations' => true,      // Generate recommendations
    'auto_optimize' => false        // Automatic optimizations
];
```

### 4. Advanced Relationship Detection
```php
public array $relationships = [
    'enabled' => true,                      // Enable relationship detection
    'detect_polymorphic' => true,           // Detect polymorphic relationships
    'detect_self_referencing' => true,      // Detect self-referencing tables
    'detect_many_to_many' => true,          // Detect many-to-many relationships
    'detect_hierarchical' => true,          // Detect hierarchical structures
    'naming_conventions' => [...]           // Relationship naming rules
];
```

### 5. Schema Validation
```php
public array $validation = [
    'enabled' => false,                 // Enable validation
    'strict_mode' => false,             // Strict validation mode
    'rules' => [...],                   // Validation rules
    'auto_fix' => false,                // Auto-fix issues
    'custom_rules' => []                // Custom validation rules
];
```

### 6. Advanced Features
```php
public array $advanced = [
    'schema_versioning' => false,       // Enable schema versioning
    'migration_support' => false,       // Migration generation support
    'backup_schemas' => false,          // Automatic schema backups
    'compression' => false,             // Schema compression
    'encryption' => false               // Schema encryption
];
```

## Migration Steps

### Step 1: Update Your Configuration File

1. **Keep existing properties** - They will continue to work
2. **Add new array configurations** - Start with cache configuration
3. **Remove deprecated properties** - Remove `public int $ttl` if present

### Step 2: Update Code References

If your code directly accesses configuration properties:

```php
// ❌ Update this
$ttl = $config->ttl;

// ✅ To this
$ttl = $config->cache['ttl'];
```

### Step 3: Enable New Features Gradually

Start with basic new features:

```php
// Enable caching with new configuration
public array $cache = [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'my_app_schemas_'
];

// Enable basic logging
public array $logging = [
    'enabled' => true,
    'level' => 'info'
];
```

### Step 4: Test Your Application

1. Run your existing tests
2. Check that schema operations work as expected
3. Verify cache behavior with new configuration
4. Monitor logs for any issues

## Compatibility Matrix

| Feature | Old Config | New Config | Status |
|---------|------------|------------|--------|
| `$silent` | ✅ | ✅ | Fully compatible |
| `$ttl` | ❌ | ✅ `$cache['ttl']` | **Migrated** |
| `$automate` | ✅ | ✅ | Fully compatible |
| Caching | Basic | Enhanced | **Improved** |
| Logging | Limited | Full control | **New** |
| Performance | N/A | Analysis & optimization | **New** |
| Relationships | Basic | Advanced detection | **Enhanced** |
| Validation | N/A | Comprehensive rules | **New** |

## Support

- **Backward Compatibility:** Your existing code will continue to work
- **Gradual Migration:** You can migrate features one at a time
- **No Breaking Changes:** Only the deprecated `$ttl` property was removed
- **Enhanced Features:** New configurations provide more control and features

## Testing Your Migration

```php
// Test that your configuration works
$schemas = new \Daycry\Schemas\Schemas();

// Verify cache is working
$schema = $schemas->get('your_database');

// Check new features are available
if ($config->cache['enabled']) {
    echo "Cache is enabled with TTL: " . $config->cache['ttl'];
}

if ($config->logging['enabled']) {
    echo "Logging is enabled at level: " . $config->logging['level'];
}
```

---

**Need Help?** Check the [Configuration Guide](configuration.md) for detailed explanations of all new options.
