# Contributing Guide

Thank you for your interest in contributing to the Daycry Schemas library! This guide will help you get started with contributing to the project.

## Table of Contents
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Submitting Changes](#submitting-changes)
- [Release Process](#release-process)

## Getting Started

### Prerequisites
- PHP 8.1 or higher
- Composer
- Git
- CodeIgniter 4.x knowledge
- MySQL/PostgreSQL/SQLite for testing

### Fork and Clone
1. Fork the repository on GitHub
2. Clone your fork locally:
```bash
git clone https://github.com/your-username/schemas.git
cd schemas
```

3. Add the upstream repository:
```bash
git remote add upstream https://github.com/daycry/schemas.git
```

## Development Setup

### Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install development dependencies
composer install --dev
```

### Environment Setup
1. Copy the example environment file:
```bash
cp .env.example .env
```

2. Configure your database settings in `.env`:
```env
# Database Configuration
database.default.hostname = localhost
database.default.database = schemas_test
database.default.username = your_username
database.default.password = your_password
database.default.DBDriver = MySQLi

# Testing Database
database.tests.hostname = localhost
database.tests.database = schemas_test
database.tests.username = your_username
database.tests.password = your_password
database.tests.DBDriver = MySQLi
```

### Database Setup
Create test databases:
```sql
CREATE DATABASE schemas_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE schemas_test_secondary CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Code Standards

### PHP Standards
We follow PSR-12 coding standards with some additional rules:

#### Class Structure
```php
<?php

declare(strict_types=1);

namespace Daycry\Schemas\Example;

use Daycry\Schemas\BaseHandler;
use Daycry\Schemas\Structures\Schema;

/**
 * Example class demonstrating coding standards.
 */
class ExampleClass extends BaseHandler
{
    /**
     * Class constants in SCREAMING_SNAKE_CASE
     */
    private const MAX_RETRY_ATTEMPTS = 3;
    
    /**
     * Properties with type declarations
     */
    protected array $config = [];
    private ?Schema $schema = null;
    
    /**
     * Constructor with proper type hints and documentation
     *
     * @param array $config Configuration array
     */
    public function __construct(array $config = [])
    {
        parent::__construct();
        $this->config = $config;
    }
    
    /**
     * Method with proper documentation
     *
     * @param string $tableName The table name to process
     * @return bool True if successful
     * @throws SchemasException If table is not found
     */
    public function processTable(string $tableName): bool
    {
        // Method implementation
        return true;
    }
}
```

#### Naming Conventions
- **Classes**: PascalCase (`SchemaValidator`)
- **Methods**: camelCase (`validateSchema`)
- **Properties**: camelCase (`$tableName`)
- **Constants**: SCREAMING_SNAKE_CASE (`MAX_CONNECTIONS`)
- **Files**: PascalCase matching class name (`SchemaValidator.php`)

#### Documentation
All public methods must have PHPDoc comments:
```php
/**
 * Brief description of what the method does
 *
 * Longer description if needed, explaining the purpose,
 * usage examples, or important notes.
 *
 * @param string $param1 Description of parameter
 * @param array  $param2 Description of array parameter
 * @return bool Description of return value
 * @throws ExceptionType When this exception is thrown
 * 
 * @since 1.2.0 Added support for new feature
 */
public function exampleMethod(string $param1, array $param2 = []): bool
{
    // Implementation
}
```

### Code Quality Tools

#### PHP CS Fixer
Format code according to standards:
```bash
# Check code style
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style
vendor/bin/php-cs-fixer fix
```

#### PHPStan
Static analysis for type safety:
```bash
# Run PHPStan analysis
vendor/bin/phpstan analyse src tests
```

#### Rector
Automated code upgrades:
```bash
# Check for possible upgrades
vendor/bin/rector process --dry-run

# Apply upgrades
vendor/bin/rector process
```

## Testing

### Test Structure
Tests are organized in the `tests/` directory:
```
tests/
├── LiveTest.php                 # Live database tests
├── SchemasTest.php             # Main functionality tests
├── _support/                   # Test support files
│   ├── TestCase.php           # Base test case
│   ├── Database/              # Test database files
│   ├── Models/                # Test models
│   └── Schemas/               # Test schemas
├── Archiver/                  # Archiver tests
├── Commands/                  # Command tests
├── Drafter/                   # Drafter tests
├── Reader/                    # Reader tests
└── Structures/                # Structure tests
```

### Writing Tests
All new features must include tests:

#### Unit Test Example
```php
<?php

namespace Daycry\Schemas\Tests\Structures;

use Daycry\Schemas\Structures\Field;
use Daycry\Schemas\Tests\_support\TestCase;

class FieldTest extends TestCase
{
    public function testFieldCreation(): void
    {
        $field = new Field('test_field');
        
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('test_field', $field->name);
    }
    
    public function testFieldTypeDetection(): void
    {
        $field = new Field('id');
        $field->type = 'INT';
        
        $this->assertTrue($field->isNumeric());
        $this->assertFalse($field->isString());
    }
    
    /**
     * @dataProvider typeProvider
     */
    public function testVariousTypes(string $type, bool $isNumeric, bool $isString): void
    {
        $field = new Field('test');
        $field->type = $type;
        
        $this->assertEquals($isNumeric, $field->isNumeric());
        $this->assertEquals($isString, $field->isString());
    }
    
    public function typeProvider(): array
    {
        return [
            'integer' => ['INT', true, false],
            'varchar' => ['VARCHAR', false, true],
            'decimal' => ['DECIMAL', true, false],
            'text'    => ['TEXT', false, true],
        ];
    }
}
```

#### Integration Test Example
```php
<?php

namespace Daycry\Schemas\Tests;

use Daycry\Schemas\Schemas;
use Daycry\Schemas\Tests\_support\TestCase;

class SchemasIntegrationTest extends TestCase
{
    protected Schemas $schemas;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->schemas = new Schemas();
    }
    
    public function testGetCompleteSchema(): void
    {
        $schema = $this->schemas->get();
        
        $this->assertNotNull($schema);
        $this->assertNotNull($schema->tables);
        $this->assertGreaterThan(0, count($schema->tables));
    }
    
    public function testGetSpecificTable(): void
    {
        $table = $this->schemas->getTable('users');
        
        $this->assertNotNull($table);
        $this->assertEquals('users', $table->name);
        $this->assertNotNull($table->fields);
    }
}
```

### Running Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit tests/Structures/FieldTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html build/coverage

# Run specific test method
vendor/bin/phpunit --filter testFieldCreation

# Run live tests (requires database)
vendor/bin/phpunit tests/LiveTest.php
```

### Test Coverage
Maintain high test coverage:
- New features must have 90%+ coverage
- Bug fixes must include regression tests
- Critical paths must have 100% coverage

## Documentation

### Writing Documentation
Documentation is written in Markdown and located in `docs/`:

#### Structure
```
docs/
├── README.md                   # Main documentation
├── installation.md            # Installation guide
├── quick-start.md             # Quick start guide
├── core/                      # Core documentation
├── advanced/                  # Advanced features
├── api/                       # API reference
├── examples/                  # Code examples
└── troubleshooting/           # Troubleshooting guides
```

#### Documentation Standards
1. **Clear Headings**: Use descriptive headings
2. **Code Examples**: Include working code examples
3. **Cross-References**: Link to related sections
4. **Up-to-Date**: Keep examples current with codebase

#### Code Example Format
```markdown
### Example: Basic Usage

```php
use Daycry\Schemas\Schemas;

// Create instance
$schemas = new Schemas();

// Get schema
$schema = $schemas->get();

// Display results
echo "Found " . count($schema->tables) . " tables";
```

**Expected Output:**
```
Found 15 tables
```
```

### API Documentation
Use PHPDoc blocks for API documentation:
```php
/**
 * Validates a database schema against defined rules
 *
 * This method performs comprehensive validation of a database schema,
 * checking for issues like circular references, foreign key consistency,
 * and naming convention compliance.
 *
 * @param Schema $schema The schema to validate
 * @param array  $rules  Optional custom validation rules
 * @return ValidationResult The validation results
 * 
 * @throws SchemasException If validation configuration is invalid
 * 
 * @example
 * ```php
 * $validator = new SchemaValidator();
 * $result = $validator->validateSchema($schema);
 * 
 * if (!$result->isValid()) {
 *     foreach ($result->getErrors() as $error) {
 *         echo "Error: $error\n";
 *     }
 * }
 * ```
 * 
 * @since 1.0.0
 * @version 1.2.0 Added custom rules parameter
 */
public function validateSchema(Schema $schema, array $rules = []): ValidationResult
```

## Submitting Changes

### Branch Naming
Use descriptive branch names:
- `feature/add-postgresql-support`
- `bugfix/fix-cache-invalidation`
- `docs/improve-installation-guide`
- `refactor/simplify-validation-logic`

### Commit Messages
Follow conventional commit format:
```
type(scope): brief description

Longer description explaining the change, motivation,
and any breaking changes.

Fixes #123
Closes #456
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

### Pull Request Process
1. **Create Feature Branch**:
```bash
git checkout -b feature/your-feature-name
```

2. **Make Changes**: Follow coding standards and include tests

3. **Run Quality Checks**:
```bash
# Run tests
vendor/bin/phpunit

# Check code style
vendor/bin/php-cs-fixer fix

# Run static analysis
vendor/bin/phpstan analyse
```

4. **Commit Changes**:
```bash
git add .
git commit -m "feat(validation): add custom validation rules support"
```

5. **Push to Fork**:
```bash
git push origin feature/your-feature-name
```

6. **Create Pull Request**: 
   - Use descriptive title and description
   - Reference related issues
   - Include screenshots if UI changes
   - Mark as draft if work in progress

### Pull Request Template
```markdown
## Description
Brief description of the changes made.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added for new functionality
- [ ] Existing tests updated if needed

## Checklist
- [ ] My code follows the style guidelines
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings

## Related Issues
Closes #(issue number)
```

## Release Process

### Version Numbering
We follow Semantic Versioning (SemVer):
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist
1. Update version numbers
2. Update CHANGELOG.md
3. Run full test suite
4. Generate documentation
5. Create release tag
6. Publish release notes

### Changelog Format
```markdown
## [1.2.0] - 2024-01-15

### Added
- New PostgreSQL support
- Custom validation rules
- Performance monitoring

### Changed
- Improved cache invalidation logic
- Updated documentation structure

### Fixed
- Fixed memory leak in large schema processing
- Corrected foreign key detection

### Removed
- Deprecated methods from v1.0

### Security
- Fixed potential SQL injection in custom queries
```

Thank you for contributing to the Daycry Schemas library! Your contributions help make this tool better for everyone.
