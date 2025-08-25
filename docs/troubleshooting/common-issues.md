# Troubleshooting Guide

This guide helps you resolve common issues when working with the Daycry Schemas library.

## Table of Contents
- [Installation Issues](#installation-issues)
- [Configuration Problems](#configuration-problems)
- [Database Connection Issues](#database-connection-issues)
- [Performance Problems](#performance-problems)
- [Cache Issues](#cache-issues)
- [Memory and Timeout Issues](#memory-and-timeout-issues)
- [Validation Errors](#validation-errors)
- [Common Error Messages](#common-error-messages)
- [Debugging Tips](#debugging-tips)
- [Best Practices](#best-practices)

## Installation Issues

### Problem: Composer Installation Fails
```bash
Problem 1
- daycry/schemas requires php ^8.1
```

**Solution:**
```bash
# Check PHP version
php -v

# Upgrade PHP if needed (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install php8.1

# Or specify minimum stability
composer require daycry/schemas --with-all-dependencies
```

### Problem: Class Not Found After Installation
```php
Fatal error: Class 'Daycry\Schemas\Schemas' not found
```

**Solutions:**
1. **Check Composer Autoload:**
```php
// Make sure this is included
require_once 'vendor/autoload.php';
```

2. **Regenerate Autoload:**
```bash
composer dump-autoload
```

3. **Clear CodeIgniter Cache:**
```bash
# Remove cache files
rm -rf writable/cache/*
```

### Problem: Version Conflicts
```bash
Your requirements could not be resolved to an installable set of packages
```

**Solution:**
```bash
# Update composer
composer self-update

# Clear composer cache
composer clear-cache

# Install with specific version
composer require daycry/schemas:^1.0
```

## Configuration Problems

### Problem: Configuration File Not Found
```php
Config file 'Schemas' not found
```

**Solution:**
Create the configuration file:
```php
// app/Config/Schemas.php
<?php namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public string $defaultGroup = 'default';
    public bool $silent = false;
}
```

### Problem: Invalid Configuration Values
```php
SchemasException: Invalid cache TTL value
```

**Solution:**
Check configuration values:
```php
// app/Config/Schemas.php
public array $cache = [
    'enabled' => true,
    'ttl' => 3600,        // Must be positive integer
    'handler' => 'file'   // Must be valid handler
];
```

### Problem: Database Group Not Found
```php
Database group 'production' not found
```

**Solutions:**
1. **Check Database Configuration:**
```php
// app/Config/Database.php
public array $production = [
    'DSN'      => '',
    'hostname' => 'localhost',
    'username' => 'username',
    'password' => 'password',
    'database' => 'database_name',
    'DBDriver' => 'MySQLi',
    // ... other settings
];
```

2. **Use Existing Group:**
```php
$schemas = new Schemas();
$schemas->setDatabase('default'); // Use 'default' instead
```

## Database Connection Issues

### Problem: Connection Timeout
```php
SQLSTATE[HY000] [2002] Connection timed out
```

**Solutions:**
1. **Increase Timeout:**
```php
// app/Config/Database.php
public array $default = [
    'hostname' => 'localhost',
    'username' => 'username',
    'password' => 'password',
    'database' => 'database',
    'DBDriver' => 'MySQLi',
    'connect_timeout' => 60,
    'read_timeout' => 60,
];
```

2. **Check Database Server:**
```bash
# Test connection
mysql -h localhost -u username -p database_name

# Check if MySQL is running
sudo service mysql status
```

### Problem: Access Denied
```php
SQLSTATE[28000] [1045] Access denied for user 'username'@'localhost'
```

**Solutions:**
1. **Check Credentials:**
```php
// app/Config/Database.php
public array $default = [
    'username' => 'correct_username',
    'password' => 'correct_password',
];
```

2. **Grant Database Permissions:**
```sql
-- MySQL
GRANT ALL PRIVILEGES ON database_name.* TO 'username'@'localhost';
FLUSH PRIVILEGES;
```

### Problem: Database Not Found
```php
SQLSTATE[42000] [1049] Unknown database 'database_name'
```

**Solutions:**
1. **Create Database:**
```sql
CREATE DATABASE database_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Check Database Name:**
```php
// Verify database name in configuration
'database' => 'actual_database_name',
```

## Performance Problems

### Problem: Slow Schema Loading
Schema takes more than 30 seconds to load.

**Solutions:**
1. **Enable Caching:**
```php
// app/Config/Schemas.php
public array $cache = [
    'enabled' => true,
    'ttl' => 3600,
    'handler' => 'redis' // Faster than file cache
];
```

2. **Limit Tables:**
```php
// Only load specific tables
public array $includedTables = ['users', 'posts', 'categories'];

// Or ignore large tables
public array $ignoredTables = ['logs', 'analytics', 'temp_data'];
```

3. **Optimize Database:**
```sql
-- Analyze tables for better performance
ANALYZE TABLE information_schema.tables;
ANALYZE TABLE information_schema.columns;
```

### Problem: High Memory Usage
```php
Fatal error: Allowed memory size exhausted
```

**Solutions:**
1. **Increase Memory Limit:**
```php
ini_set('memory_limit', '512M');
```

2. **Use Pagination:**
```php
// Process tables in batches
$config = config('Schemas');
$config->includedTables = ['users', 'posts']; // First batch
$schemas = new Schemas($config);
```

3. **Disable Unnecessary Features:**
```php
public bool $enablePerformanceAnalysis = false;
public bool $enableRelationDetection = false;
public bool $enableValidation = false;
```

## Cache Issues

### Problem: Cache Not Working
Data is always loaded from database even with cache enabled.

**Solutions:**
1. **Check Cache Configuration:**
```php
// app/Config/Cache.php
public array $redis = [
    'host'     => '127.0.0.1',
    'password' => null,
    'port'     => 6379,
    'timeout'  => 0,
];

// app/Config/Schemas.php
public array $cache = [
    'enabled' => true,
    'handler' => 'redis', // Ensure this matches your setup
];
```

2. **Test Cache Manually:**
```php
$cache = \Config\Services::cache();
$cache->save('test_key', 'test_value', 60);
$value = $cache->get('test_key');
echo $value; // Should output 'test_value'
```

3. **Check File Permissions:**
```bash
# For file cache
chmod -R 755 writable/cache/
chown -R www-data:www-data writable/cache/
```

### Problem: Cache Keys Collision
Different environments sharing cache keys.

**Solution:**
Use environment-specific prefixes:
```php
// app/Config/Schemas.php
public array $cache = [
    'enabled' => true,
    'prefix' => 'schemas_' . ENVIRONMENT . '_',
];
```

### Problem: Stale Cache Data
Cache contains outdated schema information.

**Solutions:**
1. **Manual Cache Clear:**
```php
$schemas = new Schemas();
$schemas->clearCache(); // If method exists

// Or clear manually
$cache = \Config\Services::cache();
$cache->delete('schemas_default_tables');
```

2. **Reduce TTL:**
```php
public array $cache = [
    'enabled' => true,
    'ttl' => 300, // 5 minutes instead of 1 hour
];
```

## Memory and Timeout Issues

### Problem: Script Timeout
```php
Fatal error: Maximum execution time exceeded
```

**Solutions:**
1. **Increase Timeout:**
```php
ini_set('max_execution_time', 300); // 5 minutes
set_time_limit(300);
```

2. **Use Background Processing:**
```php
// For CLI scripts
if (is_cli()) {
    set_time_limit(0); // No timeout for CLI
}
```

### Problem: Out of Memory
```php
Fatal error: Allowed memory size of X bytes exhausted
```

**Solutions:**
1. **Increase Memory:**
```php
ini_set('memory_limit', '1G');
```

2. **Process in Chunks:**
```php
$tableChunks = array_chunk($allTables, 10);
foreach ($tableChunks as $chunk) {
    $config->includedTables = $chunk;
    $schemas = new Schemas($config);
    // Process chunk
    unset($schemas); // Free memory
}
```

## Validation Errors

### Problem: Foreign Key Validation Fails
```php
ValidationError: Foreign key references non-existent table
```

**Solutions:**
1. **Check Table Order:**
```php
// Ensure referenced tables exist
$config->includedTables = [
    'users',      // Referenced table first
    'posts',      // Referencing table second
];
```

2. **Disable Strict Validation:**
```php
public array $validation = [
    'enabled' => true,
    'strict_mode' => false,
];
```

### Problem: Circular Reference Detected
```php
ValidationError: Circular reference detected between tables A and B
```

**Solution:**
This is usually by design. Disable the check if intentional:
```php
public array $validation = [
    'rules' => [
        'circular_references' => false,
    ]
];
```

## Common Error Messages

### "Table 'information_schema.KEY_COLUMN_USAGE' doesn't exist"
**Cause:** Using MySQL version < 5.0 or permissions issue.

**Solution:**
```sql
-- Grant access to information_schema
GRANT SELECT ON information_schema.* TO 'username'@'localhost';
```

### "Call to undefined method"
**Cause:** Version mismatch or missing dependency.

**Solution:**
```bash
composer update daycry/schemas
composer install --no-dev
```

### "Class 'Config\Schemas' not found"
**Cause:** Missing configuration file.

**Solution:**
```bash
# Copy default configuration
cp vendor/daycry/schemas/src/Config/Schemas.php app/Config/
```

## Debugging Tips

### Enable Debug Mode
```php
// app/Config/Schemas.php
public bool $silent = false;

// Enable CI4 debugging
// .env
CI_ENVIRONMENT = development
app.forceGlobalSecureRequests = false
app.CSRFProtection = false
```

### Log Schema Operations
```php
public array $logging = [
    'enabled' => true,
    'level' => 'debug',
    'performance_metrics' => true,
];
```

### Manual Error Checking
```php
$schemas = new Schemas();
$schema = $schemas->get();

// Check for errors
if ($schemas->hasErrors()) {
    foreach ($schemas->getErrors() as $error) {
        log_message('error', 'Schema error: ' . $error);
    }
}
```

### Database Query Debugging
```php
// Enable query logging in Database config
public bool $DBDebug = true;

// Or use database debug toolbar
// composer require kint-php/kint
```

## Best Practices

### Performance Optimization
1. **Always use caching in production**
2. **Limit tables with `includedTables` when possible**
3. **Use Redis cache for better performance**
4. **Disable unnecessary features**

### Error Prevention
1. **Test configurations in development first**
2. **Use proper error handling with try-catch**
3. **Monitor memory usage for large databases**
4. **Set appropriate timeouts**

### Debugging Strategy
1. **Start with simple configurations**
2. **Enable logging for troubleshooting**
3. **Test database connections separately**
4. **Use CLI for debugging large operations**

### Environment Management
1. **Use different configs per environment**
2. **Never cache in development**
3. **Use environment variables for sensitive data**
4. **Test schema changes in staging first**

If you encounter issues not covered in this guide, please:
1. Check the library's GitHub issues
2. Enable debug logging for more details
3. Test with minimal configuration
4. Provide complete error messages when reporting issues
