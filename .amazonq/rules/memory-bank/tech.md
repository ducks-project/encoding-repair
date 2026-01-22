# Technology Stack

## Programming Languages

### PHP

- **Versions Supported**: 7.4, 8.0, 8.1, 8.2, 8.3
- **Language Features Used**:
  - Strict types (`declare(strict_types=1)`)
  - Type hints (scalar, return types, nullable types)
  - Arrow functions (short closures)
  - Null coalescing operator (`??`)
  - Spread operator for arrays
  - Static analysis annotations (Psalm, PHPStan)

## Required Extensions

### Core Requirements

- **ext-json**: JSON encoding/decoding operations
- **ext-mbstring**: Multi-byte string operations, encoding detection

### Optional Extensions (Recommended)

- **ext-intl**: UConverter support (30% performance improvement)
- **ext-iconv**: Transliteration support, alternative conversion method
- **ext-fileinfo**: Advanced encoding detection via finfo

## Dependencies

### Production Dependencies

**None** - Zero runtime dependencies for maximum portability

### Development Dependencies

#### Testing

- **phpunit/phpunit**: ^9.5 || ^10.0
  - Unit testing framework
  - Code coverage reporting
  - Process isolation support

#### Static Analysis

- **phpstan/phpstan**: ^1.10
  - Level 8 static analysis
  - Type inference and validation
- **phpstan/phpstan-phpunit**: ^1.4
  - PHPUnit-specific rules
- **vimeo/psalm**: ^4.30 || ^5.0
  - Type coverage analysis
  - Security analysis

#### Code Quality

- **friendsofphp/php-cs-fixer**: ^3.0
  - PSR-12/PER code style enforcement
  - Automatic code formatting
- **squizlabs/php_codesniffer**: ^3.10
  - Additional code style checks
- **phpmd/phpmd**: ^1.5
  - Mess detection (complexity, unused code)

#### Performance

- **phpbench/phpbench**: ^1.2
  - Performance benchmarking
  - Regression detection

#### Utilities

- **ergebnis/composer-normalize**: ^2.48
  - Composer.json normalization

## Build System

### Composer Scripts

```bash
# Testing
composer unittest              # Run PHPUnit tests with coverage
composer test                  # Run all tests (unit + benchmarks)
composer bench                 # Run performance benchmarks

# Static Analysis
composer phpstan               # PHPStan level 8 analysis
composer psalm                 # Psalm type coverage
composer phpmd                 # PHP Mess Detector

# Code Quality
composer phpcs                 # Check code style (CodeSniffer)
composer phpcsfixer-check      # Check code style (PHP-CS-Fixer)

# Utilities
composer rector                # Rector refactoring (dry-run)
```

### Configuration Files

#### phpunit.xml.dist

```xml
- Process isolation enabled
- Coverage: HTML, Clover, XML formats
- Test suites: phpunit directory
- Bootstrap: vendor/autoload.php
```

#### phpstan.neon.dist

```yaml
- Level: 8 (maximum strictness)
- Paths: root directory, Transcoder/, Detector/
- Excludes: tests/, vendor/, .history/
- PHPUnit extension enabled
```

#### psalm.xml.dist

```xml
- Error level: 1
- Total issues: 0 target
- Report mixed issues
- Shepherd integration
```

#### .php-cs-fixer.dist.php

```php
- Rules: PSR-12, PER coding style
- PHP 7.4+ syntax
- Strict types declaration
- Ordered imports
```

## Development Commands

### Setup

```bash
git clone https://github.com/ducks-project/encoding-repair.git
cd encoding-repair
composer install
```

### Testing Workflow

```bash
# Run unit tests
composer unittest

# Run with coverage report
composer unittest -- --coverage-html coverage

# Run specific test
./vendor/bin/phpunit tests/phpunit/CharsetHelperTest.php

# Run benchmarks
composer bench
```

### Code Quality Workflow

```bash
# Check all quality tools
composer phpstan
composer psalm
composer phpcs
composer phpcsfixer-check
composer phpmd

# Auto-fix code style
./vendor/bin/php-cs-fixer fix
```

### CI/CD Pipeline

```bash
# Full CI check (mimics GitHub Actions)
composer phpstan && \
composer psalm && \
composer phpcs && \
composer unittest && \
composer bench
```

## Documentation Tools

### MkDocs

- **Version**: Latest
- **Theme**: Material for MkDocs
- **Configuration**: mkdocs.yml
- **Build Command**: `mkdocs build`
- **Serve Command**: `mkdocs serve`

### ReadTheDocs

- **Configuration**: .readthedocs.yml
- **Python Requirements**: docs/requirements.txt
- **Build**: Automatic on push to main branch

## Version Control

### Git

- **Repository**: <https://github.com/ducks-project/encoding-repair>
- **Branching**: main branch for stable releases
- **History**: .history/ directory (local development history)

### GitHub Actions

#### ci.yml

- Runs on: push, pull_request
- PHP versions: 7.4, 8.0, 8.1, 8.2, 8.3
- Steps: install, phpstan, psalm, phpunit, coverage upload

#### release.yml

- Runs on: tag push (v*.*.*)
- Creates GitHub release
- Publishes to Packagist

#### security.yml

- Runs on: schedule (weekly)
- Security vulnerability scanning
- Dependency auditing

## Package Distribution

### Packagist

- **Package**: ducks-project/encoding-repair
- **Type**: library
- **License**: MIT
- **Auto-update**: Via GitHub webhook

### Installation Methods

```bash
# Composer (recommended)
composer require ducks-project/encoding-repair

# Specific version
composer require ducks-project/encoding-repair:^1.0

# Development version
composer require ducks-project/encoding-repair:dev-main
```

## IDE Support

### PHPStorm/IntelliJ

- Full type inference support
- Psalm/PHPStan annotations recognized
- Composer scripts integration

### VS Code

- PHP Intelephense extension recommended
- Psalm/PHPStan extensions available
- PHP CS Fixer extension for auto-formatting

## Performance Optimization

### Autoloader Optimization

```bash
composer dump-autoload --optimize
```

### Extension Priority

1. **ext-intl** (UConverter): Fastest, 30% improvement
2. **ext-iconv**: Good performance, transliteration
3. **ext-mbstring**: Baseline, always available

### Benchmarking

```bash
# Run benchmarks with retry threshold
composer bench

# Specific benchmark
./vendor/bin/phpbench run tests/benchmark/ConversionBench.php --report=aggregate
```

## Code Coverage

### Tools

- **PHPUnit**: Code coverage collection
- **Xdebug**: Coverage driver (xdebug.mode=coverage)
- **Coveralls**: Coverage reporting service
- **Codecov**: Alternative coverage service

### Targets

- **Line Coverage**: 95%+ target
- **Branch Coverage**: Tracked
- **Method Coverage**: 100% target

### Reports

```bash
# Generate HTML coverage report
composer unittest -- --coverage-html coverage

# View report
open coverage/index.html
```

## Static Analysis

### PHPStan

- **Level**: 8 (maximum)
- **Rules**: Strict types, no mixed types
- **Extensions**: phpstan-phpunit

### Psalm

- **Level**: 1 (strictest)
- **Features**: Type coverage, security analysis
- **Annotations**: @psalm-api, @psalm-immutable, @psalm-suppress

### Type Coverage

- **Target**: 100% type coverage
- **Tracked**: All public methods fully typed
- **Verified**: Psalm type coverage badge

## Security

### Tools

- **Psalm**: Security analysis
- **GitHub Dependabot**: Dependency updates
- **GitHub Security Scanning**: Vulnerability detection

### Practices

- Whitelisted encodings (prevent injection)
- Strict type checking
- No eval or dynamic code execution
- Input validation on all public methods

## Compatibility

### PHP Version Matrix

| PHP Version | Status | CI Tested |
| ------------- | -------- | ----------- |
| 7.4 | ✅ Supported | ✅ Yes |
| 8.0 | ✅ Supported | ✅ Yes |
| 8.1 | ✅ Supported | ✅ Yes |
| 8.2 | ✅ Supported | ✅ Yes |
| 8.3 | ✅ Supported | ✅ Yes |

### Platform Requirements

- **OS**: Linux, macOS, Windows
- **Architecture**: x86_64, ARM64
- **Web Servers**: Apache, Nginx, PHP built-in server
- **Databases**: Any (library is database-agnostic)
