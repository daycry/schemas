# Troubleshooting (Minimal Core)

Only core drafting, archiving, and reading remain. This guide drops sections about validation systems, performance analyzers, advanced relation detection, logging frameworks, async processing, and plugin infrastructure – those features were removed.

## Sections
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Connection](#database-connection)
- [Cache](#cache)
- [Memory / Timeouts](#memory--timeouts)
- [Common Errors](#common-errors)
- [Debug Basics](#debug-basics)
- [Best Practices](#best-practices)

## Installation

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

## Configuration

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

## Database Connection

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

## Cache

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

## Memory / Timeouts

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

## (Removed Validation System)
Foreign key / relation validation helpers were removed. Trust the database constraints; the drafter captures structure best-effort.

## Common Errors

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

## Debug Basics

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

Logging subsystem was removed. Use your framework/app logger around your own calls if needed.

Exceptions are thrown unless `silent = true` (then operations fail quietly – prefer `silent = false` while debugging).

### Database Query Debugging
```php
// Enable query logging in Database config
public bool $DBDebug = true;

// Or use database debug toolbar
// composer require kint-php/kint
```

## Best Practices

## Best Practices
1. Cache drafted schema (enable cache only after first successful draft).
2. Limit scope with `includedTables` for very large databases during development.
3. Keep `silent = false` while integrating; switch to `true` in production if you prefer resilience.
4. Re-draft only when structural changes occur; otherwise read from cache/file.
5. Normalize naming in migrations so drafter picks up consistent foreign keys.

If something here still references a removed feature, open an issue – the docs should remain minimal.
