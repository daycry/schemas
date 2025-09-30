# Examples and Tutorials

This directory contains comprehensive examples and step-by-step tutorials for using the Daycry Schemas library in various scenarios.

## Available Examples

### Basic Usage Examples
- [**basic-usage.md**](basic-usage.md) - Fundamental operations and simple use cases
- [**schema-reading.md**](schema-reading.md) - Reading schemas from different sources
- [**schema-validation.md**](schema-validation.md) - Validating database schemas
- [**caching-examples.md**](caching-examples.md) - Using caching for performance

### Advanced Examples
- [**performance-analysis.md**](performance-analysis.md) - Analyzing and optimizing database performance
- [**relationship-detection.md**](relationship-detection.md) - Detecting and working with table relationships
- [**custom-handlers.md**](custom-handlers.md) - Creating custom readers, drafters, and archivers
- [**integration-examples.md**](integration-examples.md) - Integrating with CodeIgniter applications

### Real-World Scenarios
- [**migration-generation.md**](migration-generation.md) - Generating database migrations from schemas
- [**api-documentation.md**](api-documentation.md) - Auto-generating API documentation from database schemas
- [**database-comparison.md**](database-comparison.md) - Comparing schemas between different environments
- [**backup-restore.md**](backup-restore.md) - Backing up and restoring database schemas

### Framework Integration
- [**codeigniter-integration.md**](codeigniter-integration.md) - Complete CodeIgniter 4 integration guide
- [**laravel-adapter.md**](laravel-adapter.md) - Using the library with Laravel (via adapter)
- [**symfony-integration.md**](symfony-integration.md) - Integration with Symfony applications

### Use Case Tutorials
- [**e-commerce-schema.md**](e-commerce-schema.md) - E-commerce database schema analysis
- [**cms-optimization.md**](cms-optimization.md) - Content Management System optimization
- [**multi-tenant.md**](multi-tenant.md) - Multi-tenant application schema management
- [**microservices.md**](microservices.md) - Schema management in microservices architecture

## Quick Start Examples

### 1. Basic Schema Reading
```php
use Daycry\Schemas\Schemas;
echo "Database has " . count($schema->tables) . " tables";
 - [**basic-usage.md**](basic-usage.md) - Fundamental operations and simple use cases
 - [**cache-reader.md**](cache-reader.md) - Loading tables from a cache archive
 - [**database-drafter.md**](database-drafter.md) - Drafting from a live database
 - [**archiver-usage.md**](archiver-usage.md) - Persisting and restoring schemas
 - [**fluent-reader.md**](fluent-reader.md) - Chaining reader fetch calls
```

 - custom-handlers.md (only if you implement custom logic; not part of minimal core)
$config = config('Schemas');

 Legacy scenario files referencing removed subsystems were pruned; create focused examples using current handlers only.
$analyzer = new \Daycry\Schemas\PerformanceAnalyzer();
```
 Primary target remains CodeIgniter 4. External framework adapter examples were removed to reduce maintenance.


 High-level domain tutorials removed. Focus on composing small targeted examples locally.
$schemas = new Schemas();
$schema = $schemas->get();

$validator = new SchemaValidator();
$result = $validator->validateSchema($schema);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "Error: $error\n";
    }
}
```

### 4. Caching Implementation
```php
use Daycry\Schemas\Schemas;

$config = config('Schemas');
$config->cache['enabled'] = true;
$config->cache['ttl'] = 3600;

$schemas = new Schemas($config);
$schema = $schemas->get(); // First call - loads from database
$schema = $schemas->get(); // Second call - loads from cache
```

### 5. Custom Configuration
```php
use Daycry\Schemas\Schemas;

 Compose your own examples focusing on custom drafting filters, archiving strategies, or framework-specific bootstrapping as needed.
$config = config('Schemas');
$config->ignoredTables = ['migrations', 'cache'];
$config->enableRelationDetection = true;
$config->enableValidation = true;

$schemas = new Schemas($config);
$schemas->setDatabase('production');
$schema = $schemas->get();
```

## Tutorial Categories

### Beginner Tutorials
Perfect for developers new to the library:
- Setting up the library
- Basic schema operations
- Understanding the structure classes
- Simple caching implementation

### Intermediate Tutorials
For developers with basic experience:
- Performance optimization techniques
- Advanced caching strategies
- Custom validation rules
- Integration with existing applications

### Advanced Tutorials
For experienced developers:
- Creating custom handlers
- Multi-database schema management
- Performance monitoring and alerting
- Enterprise deployment strategies

### Specialized Use Cases
Domain-specific implementations:
- E-commerce platforms
- Content management systems
- Multi-tenant SaaS applications
- Microservices architectures

## Code Examples Structure

Each example file follows this structure:

1. **Overview** - What the example demonstrates
2. **Prerequisites** - Required setup and dependencies
3. **Step-by-Step Implementation** - Detailed code walkthrough
4. **Complete Code** - Full working example
5. **Expected Output** - What results to expect
6. **Variations** - Alternative approaches and modifications
7. **Best Practices** - Recommendations and tips
8. **Troubleshooting** - Common issues and solutions

## Running the Examples

### Prerequisites
```bash
# Install the library
composer require daycry/schemas

# Set up CodeIgniter 4 (if using CI4 examples)
composer create-project codeigniter4/appstarter your-app
```

### Basic Setup
```php
// app/Config/Schemas.php
<?php namespace Config;

use Daycry\Schemas\Config\Schemas as BaseSchemas;

class Schemas extends BaseSchemas
{
    public string $defaultGroup = 'default';
    public bool $enableValidation = true;
    public bool $enablePerformanceAnalysis = true;
    
    public array $cache = [
        'enabled' => true,
        'handler' => 'file',
        'ttl' => 3600
    ];
}
```

### Running Examples
Each example can be run as:

1. **Standalone PHP Script**
```bash
php examples/basic-usage.php
```

2. **CodeIgniter Controller**
```php
// app/Controllers/SchemaExample.php
public function index()
{
    // Example code here
}
```

3. **Command Line Tool**
```bash
php spark schemas:example basic-usage
```

## Contributing Examples

We welcome contributions of new examples and tutorials! Please:

1. Follow the established structure
2. Include complete, working code
3. Add clear explanations and comments
4. Test all examples thoroughly
5. Update this index when adding new examples

## Support and Questions

If you have questions about any examples:

- Check the main documentation
- Review the troubleshooting sections
- Open an issue on GitHub
- Join our community discussions

Each example is designed to be educational and practical, helping you understand both basic concepts and advanced techniques for working with database schemas in your applications.
