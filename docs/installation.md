# Installation

This guide will walk you through installing the Daycry Schemas library in your CodeIgniter 4 application.

## Requirements

Before installing, ensure your system meets these requirements:

- **PHP**: 8.1 or higher
- **CodeIgniter**: 4.x (latest stable recommended)
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.7+
- **Memory**: Minimum 128MB (256MB+ recommended for large schemas)

## Installation Methods

### Via Composer (Recommended)

The easiest way to install the library is through Composer:

```bash
composer require daycry/schemas
```

This will automatically:
- Download the library and its dependencies
- Register the autoloader
- Make the library available in your application

### Manual Installation

If you prefer manual installation:

1. Download the latest release from GitHub
2. Extract to `app/ThirdParty/daycry/schemas/`
3. Add the namespace to `app/Config/Autoload.php`:

```php
public $psr4 = [
    APP_NAMESPACE => APPPATH,
    'Config'      => APPPATH . 'Config',
    'Daycry\Schemas' => APPPATH . 'ThirdParty/daycry/schemas/src',
];
```

## Configuration

### Basic Configuration

Create a configuration file at `app/Config/Schemas.php`:

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
     * Tables to ignore during schema reading
     */
    public array $ignoredTables = [
        'migrations',
        'ci_sessions',
    ];

    /**
     * Enable silent mode (suppress exceptions)
     */
    public bool $silent = false;

    /**
     * Cache configuration
     */
    public array $cache = [
        'enabled' => true,
        'handler' => 'file',
        'ttl' => 3600, // 1 hour
    ];
}
```

### Advanced Configuration

For advanced features, add these options:

```php
class Schemas extends BaseSchemas
{
    // ... basic config ...

    /**
     * Enable schema validation
     */
    public bool $enableValidation = true;

    /**
     * Enable performance analysis
     */
    public bool $enablePerformanceAnalysis = true;

    /**
     * Enable intelligent caching
     */
    public bool $enableIntelligentCache = true;

    /**
     * Logging configuration
     */
    public array $logging = [
        'enabled' => true,
        'level' => 'info',
        'handler' => 'file',
    ];

    /**
     * Performance analyzer settings
     */
    public array $performance = [
        'enabled' => true,
        'threshold_score' => 70,
        'max_recommendations' => 10,
    ];

    /**
     * Relationship detection settings
     */
    public array $relationships = [
        'detect_polymorphic' => true,
        'detect_hierarchical' => true,
        'detect_many_to_many' => true,
        'field_patterns' => [
            'foreign_key' => '/^.+_id$/',
            'polymorphic_type' => '/^.+_type$/',
            'parent' => '/^parent_id$/',
        ],
    ];
}
```

## Database Setup

### Ensure Database Connection

Make sure your database is properly configured in `app/Config/Database.php`:

```php
public array $default = [
    'DSN'      => '',
    'hostname' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
    'database' => 'your_database',
    'DBDriver' => 'MySQLi', // or 'Postgre', 'SQLite3'
    // ... other settings
];
```

### Database Permissions

Ensure your database user has the following permissions:

**For MySQL:**
```sql
GRANT SELECT ON information_schema.* TO 'your_user'@'localhost';
GRANT SELECT ON your_database.* TO 'your_user'@'localhost';
```

**For PostgreSQL:**
```sql
GRANT USAGE ON SCHEMA information_schema TO your_user;
GRANT SELECT ON ALL TABLES IN SCHEMA information_schema TO your_user;
```

## Verification

### Quick Test

Create a simple test to verify the installation:

```php
<?php

// app/Controllers/TestSchemas.php
namespace App\Controllers;

use Daycry\Schemas\Schemas;

class TestSchemas extends BaseController
{
    public function index()
    {
        try {
            $schemas = new Schemas();
            $schema = $schemas->get();
            
            $tableCount = count($schema->tables);
            echo "Successfully read {$tableCount} tables from the database.";
            
            // List first few tables
            $count = 0;
            foreach ($schema->tables as $table) {
                if ($count++ >= 5) break;
                echo "<br>Table: {$table->name}";
            }
            
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}
```

Visit `/testschemas` in your browser to verify the installation.

### Command Line Test

You can also test from the command line:

```bash
php spark make:command TestSchemas
```

Add this to your command:

```php
public function run(array $params)
{
    $schemas = new \Daycry\Schemas\Schemas();
    $schema = $schemas->get();
    
    CLI::write("Schema loaded successfully!");
    CLI::write("Tables found: " . count($schema->tables));
}
```

Run with:
```bash
php spark TestSchemas
```

## Troubleshooting

### Common Installation Issues

**Issue: Class not found**
- Verify Composer autoloader is included
- Check namespace configuration in `Autoload.php`
- Run `composer dump-autoload`

**Issue: Database connection errors**
- Verify database credentials
- Check database permissions
- Ensure database server is running

**Issue: Memory limit exceeded**
- Increase PHP memory limit: `ini_set('memory_limit', '256M')`
- Enable caching to reduce memory usage
- Use pagination for large schemas

**Issue: Permission denied on schema reading**
- Grant proper database permissions
- Check user has access to `information_schema`
- Verify connection string and credentials

### Getting Help

If you encounter issues during installation:

1. Check the [Common Issues](../troubleshooting/common-issues.md) documentation
2. Search existing [GitHub Issues](https://github.com/daycry/schemas/issues)
3. Create a new issue with:
   - PHP version
   - CodeIgniter version
   - Database type and version
   - Error messages
   - Relevant configuration

## Next Steps

Once installed and verified:

1. Read the [Quick Start Guide](quick-start.md)
2. Explore [Configuration Options](configuration.md)
3. Try the [Basic Examples](../examples/basic-usage.md)
4. Learn about [Advanced Features](../advanced/)

## Development Installation

For development and contributing:

```bash
git clone https://github.com/daycry/schemas.git
cd schemas
composer install
composer test
```

See [Development Setup](../contributing/development.md) for detailed development instructions.
