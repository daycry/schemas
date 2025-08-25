# Daycry Schemas Library Documentation

Welcome to the comprehensive documentation for the Daycry Schemas library - a powerful CodeIgniter 4 library for database schema management, analysis, and optimization.

## Table of Contents

### Getting Started
- [Installation](installation.md)
- [Quick Start Guide](quick-start.md)
- [Configuration](configuration.md)

### Core Features
- [Schema Reading](core/schema-reading.md)
- [Schema Drafting](core/schema-drafting.md)
- [Schema Archiving](core/schema-archiving.md)
- [Schema Structures](core/structures.md)

### Advanced Features
- [Schema Validation](advanced/validation.md)
- [Intelligent Caching](advanced/caching.md)
- [Performance Analysis](advanced/performance.md)
- [Logging & Metrics](advanced/logging.md)
- [Relationship Detection](advanced/relationships.md)
- [Database Objects](advanced/database-objects.md)

### Handlers
- [Cache Handler](handlers/cache-handler.md)
- [Database Handler](handlers/database-handler.md)
- [Migration Handler](handlers/migration-handler.md)
- [JSON Handler](handlers/json-handler.md)
- [XML Handler](handlers/xml-handler.md)
- [Database Object Handler](handlers/database-object-handler.md)

### API Reference
- [Classes Overview](api/classes.md)
- [Configuration Options](api/configuration.md)
- [Error Handling](api/error-handling.md)
- [Events & Hooks](api/events.md)

### Examples & Tutorials
- [Basic Usage Examples](examples/basic-usage.md)
- [Advanced Scenarios](examples/advanced-scenarios.md)
- [Performance Optimization](examples/performance-optimization.md)
- [Integration Patterns](examples/integration-patterns.md)

### Migration & Upgrading
- [Migration Guide](migration/migration-guide.md)
- [Breaking Changes](migration/breaking-changes.md)
- [Upgrading from v1.x](migration/upgrading.md)

### Troubleshooting
- [Common Issues](troubleshooting/common-issues.md)
- [Performance Issues](troubleshooting/performance.md)
- [Debugging Guide](troubleshooting/debugging.md)

### Contributing
- [Development Setup](contributing/development.md)
- [Testing Guidelines](contributing/testing.md)
- [Code Standards](contributing/standards.md)

## Overview

The Daycry Schemas library provides a comprehensive solution for managing database schemas in CodeIgniter 4 applications. It offers:

### Core Capabilities
- **Schema Discovery**: Automatically detect and analyze database structures
- **Cross-Database Support**: Works with MySQL, PostgreSQL, SQLite, and more
- **Intelligent Caching**: Advanced caching with versioning and invalidation
- **Performance Analysis**: Identify bottlenecks and optimization opportunities
- **Validation & Integrity**: Ensure schema consistency and detect issues

### Advanced Features
- **Relationship Detection**: Discover complex relationships including polymorphic and hierarchical structures
- **Database Objects**: Support for views, stored procedures, and triggers
- **Export/Import**: Multiple format support (JSON, XML, PHP)
- **Migration Integration**: Parse and analyze CodeIgniter migration files
- **Comprehensive Logging**: PSR-3 compatible logging with performance metrics

### Architecture
The library follows a modular architecture with three main components:

1. **Readers**: Extract schema information from various sources
2. **Drafters**: Generate schemas from database connections
3. **Archivers**: Store and retrieve schemas in different formats

Each component can be extended with custom handlers to support additional functionality.

## Quick Examples

### Basic Schema Reading
```php
use Daycry\Schemas\Schemas;

$schemas = new Schemas();
$schema = $schemas->get();

foreach ($schema->tables as $table) {
    echo "Table: {$table->name}\n";
    foreach ($table->fields as $field) {
        echo "  Field: {$field->name} ({$field->type})\n";
    }
}
```

### Performance Analysis
```php
use Daycry\Schemas\PerformanceAnalyzer;

$analyzer = new PerformanceAnalyzer();
$analysis = $analyzer->analyzeSchema($schema);

echo "Performance Score: {$analysis['score']}/100\n";
foreach ($analysis['recommendations'] as $rec) {
    echo "- {$rec['message']}\n";
}
```

### Schema Validation
```php
use Daycry\Schemas\SchemaValidator;

$validator = new SchemaValidator();
$result = $validator->validateSchema($schema);

if (!$result->isValid()) {
    foreach ($result->getErrors() as $error) {
        echo "Error: {$error}\n";
    }
}
```

## Key Benefits

- **Zero Configuration**: Works out-of-the-box with sensible defaults
- **High Performance**: Intelligent caching and optimized queries
- **Extensible**: Plugin architecture for custom functionality
- **Well Tested**: Comprehensive test suite with 125+ tests
- **Production Ready**: Used in production environments
- **Documentation**: Extensive documentation and examples

## Requirements

- PHP 8.1 or higher
- CodeIgniter 4.x
- Supported databases: MySQL 5.7+, PostgreSQL 10+, SQLite 3.7+

## License

This library is open-sourced software licensed under the [MIT License](../LICENSE).

## Support

- [GitHub Issues](https://github.com/daycry/schemas/issues)
- [Discussions](https://github.com/daycry/schemas/discussions)
- [Community Forum](https://forum.codeigniter.com/)

---

**Next Steps**: Start with the [Installation Guide](installation.md) or jump into the [Quick Start Guide](quick-start.md) to begin using the library.
