# Revive Adserver REST API Plugin - CRUSH.md

## 🚀 Build/Lint/Test Commands

```bash
# Install dependencies
composer install

# Run all tests
composer test

# Run specific test suites
composer test-unit
composer test-integration
composer test-api

# Run a single test class
vendor/bin/phpunit tests/Unit/Services/TargetingCompilerTest.php

# Run a specific test method
vendor/bin/phpunit --filter testMethodName tests/path/to/TestFile.php

# Generate coverage report
composer test-coverage

# Package plugin for distribution
composer package

# Bump version
composer version-bump

# Create release
composer release
```

## 📝 Code Style Guidelines

### PHP Standards
- PHP 7.4+ required
- PSR-4 autoloading (`App\` namespace for src/)
- PSR-12 coding standards (implicitly followed)

### Naming Conventions
- Classes: PascalCase (`TargetingCompiler`, `ApiTokensController`)
- Methods: camelCase (`compileRules`, `validateInput`)
- Variables: camelCase (`$requestData`, `$pdo`)
- Constants: UPPER_SNAKE_CASE

### Imports & Organization
- Explicit `use` statements for all imported classes
- Alphabetical ordering of imports
- Only import classes that are actually used
- Group related methods together

### Documentation
- PHPDoc comments for all public methods
- Inline comments for complex logic
- Clear parameter and return type hints
- Descriptive variable and method names

### Error Handling
- Try-catch blocks for database operations
- JSON response format with error details
- Appropriate HTTP status codes (200, 400, 401, 403, 404, 500)
- Graceful error recovery when possible

### Database Access
- Use PDO with prepared statements
- Parameter binding to prevent SQL injection
- Proper transaction handling for multi-step operations
- Consistent error handling for database operations

### Security
- Token-based authentication with SHA-256 hashing
- Permission checking for all endpoints
- Input validation and sanitization
- Rate limiting implementation

### Response Format
- Standardized JSON responses with `success`/`error` keys
- Consistent structure with `data`, `meta`, and `message` fields
- Proper HTTP status codes for different scenarios