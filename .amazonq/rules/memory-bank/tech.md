# Technology Stack

## Programming Language

- **PHP**: 7.4, 8.0, 8.1, 8.2, 8.3
- **Type System**: Strict typing enabled (`declare(strict_types=1)`)
- **Coding Standard**: PSR-12 / PER (PHP Evolving Recommendation)

## Required PHP Extensions

- **ext-mbstring**: Multi-byte string functions (core dependency)
- **ext-json**: JSON encoding/decoding (core dependency)

## Optional PHP Extensions

- **ext-intl**: UConverter support (30% performance improvement)
- **ext-iconv**: Transliteration support (//TRANSLIT, //IGNORE)
- **ext-fileinfo**: Advanced encoding detection fallback

## Build System

- **Composer**: Dependency management and autoloading
- **Package Name**: ducks-project/encoding-repair
- **License**: MIT
- **Minimum Stability**: stable

## Development Tools

### Testing

- **PHPUnit**: ^9.5 || ^10.0 - Unit testing framework
- **PHPBench**: ^1.2 - Performance benchmarking

### Static Analysis

- **PHPStan**: ^1.10 (Level 8) - Static analysis
- **PHPStan PHPUnit**: ^1.4 - PHPUnit-specific rules
- **Psalm**: ^4.30 || ^5.0 - Type checking and immutability verification

### Code Quality

- **PHP CS Fixer**: ^3.0 - PSR-12/PER code style enforcement
- **PHP CodeSniffer**: Code style checking
- **PHP Mess Detector**: Code quality analysis

### Refactoring

- **Rector**: Automated refactoring and PHP version upgrades

## Composer Scripts

### Testing

```bash
composer test              # Run all tests (unit + benchmarks)
composer unittest          # Run PHPUnit tests with coverage
composer bench             # Run performance benchmarks
```

### Code Quality

```bash
composer phpstan           # Run PHPStan static analysis (level 8)
composer psalm             # Run Psalm type checking
composer phpcs             # Run PHP CodeSniffer
composer phpcsfixer-check  # Check code style (dry-run)
composer phpmd             # Run PHP Mess Detector
```

### Refactoring

```bash
composer rector            # Run Rector (dry-run)
```

### Full CI Pipeline

```bash
composer ci                # Run all CI checks locally
```

## Development Commands

### Installation

```bash
# Clone repository
git clone https://github.com/ducks-project/encoding-repair.git
cd encoding-repair

# Install dependencies
composer install

# Install optional extensions (Ubuntu/Debian)
sudo apt-get install php-intl php-iconv

# Install optional extensions (macOS)
brew install php@8.2  # Extensions included by default
```

### Testing Workflow

```bash
# Run unit tests
composer unittest

# Run tests with coverage report
composer unittest -- --coverage-html coverage

# Run specific test
./vendor/bin/phpunit tests/phpunit/SpecificTest.php

# Run benchmarks
composer bench
```

### Code Quality Workflow

```bash
# Check code style
composer phpcsfixer-check

# Fix code style automatically
./vendor/bin/php-cs-fixer fix

# Run static analysis
composer phpstan

# Run type checking
composer psalm

# Run all quality checks
composer phpstan && composer psalm && composer phpcsfixer-check
```

### CI/CD Integration

```bash
# Full CI pipeline (local)
composer ci

# Individual CI steps
composer unittest          # Tests with coverage
composer phpstan           # Static analysis
composer psalm             # Type checking
composer phpcsfixer-check  # Style check
composer phpmd             # Mess detection
```

## Configuration Files

### Composer (composer.json)

- **Autoload**: PSR-4 (`Ducks\Component\EncodingRepair\` → ``)
- **Optimize Autoloader**: Enabled for production
- **Sort Packages**: Enabled for consistency

### PHPUnit (phpunit.xml.dist)

- **Process Isolation**: Enabled for test independence
- **Coverage**: XDEBUG_MODE=coverage required
- **Test Directory**: `tests/phpunit/`

### PHPStan (phpstan.neon.dist)

- **Level**: 8 (maximum strictness)
- **Paths**: Analyze all PHP files
- **PHPUnit Extension**: Enabled

### Psalm (psalm.xml.dist)

- **Error Level**: Strict
- **Check Immutability**: Enabled
- **Shepherd**: Enabled for public metrics

### PHP CS Fixer (.php-cs-fixer.dist.php)

- **Rules**: PSR-12 / PER
- **Risky Rules**: Enabled
- **Caching**: Enabled for performance

### PHPBench (phpbench.json.dist)

- **Retry Threshold**: 5 iterations
- **Report**: Aggregate statistics
- **Benchmark Directory**: `tests/benchmark/`

### Rector (rector.php)

- **PHP Version**: Target latest stable
- **Sets**: PHP 8.0, 8.1, 8.2 features
- **Type Coverage**: Level 0 (strict)

## Version Control

- **Git**: Version control system
- **.gitignore**: Excludes vendor/, coverage/, .history/
- **.editorconfig**: Consistent editor settings

## CI/CD Platforms

- **Scrutinizer**: Code quality monitoring
- **StyleCI**: Automated style fixes
- **AppVeyor**: Windows CI pipeline
- **ReadTheDocs**: Documentation hosting

## Package Distribution

- **Packagist**: <https://packagist.org/packages/ducks-project/encoding-repair>
- **GitHub**: <https://github.com/ducks-project/encoding-repair>
- **Composer Install**: `composer require ducks-project/encoding-repair`

## Performance Optimization

- **Optimized Autoloader**: Enabled in production
- **Extension Priority**: UConverter (fastest) → iconv → mbstring
- **Caching**: Detection results should be cached by consumers
- **Benchmarking**: PHPBench for performance regression testing

## Environment Requirements

- **OS**: Linux, macOS, Windows (cross-platform)
- **PHP Memory**: Minimum 128MB recommended
- **PHP Extensions**: See required/optional sections above

## Development Environment Setup

```bash
# 1. Clone and install
git clone https://github.com/ducks-project/encoding-repair.git
cd encoding-repair
composer install

# 2. Verify installation
php -v                     # Check PHP version (>= 7.4)
php -m | grep mbstring     # Verify mbstring
php -m | grep json         # Verify json
php -m | grep intl         # Check intl (optional)

# 3. Run tests
composer test

# 4. Run quality checks
composer phpstan
composer psalm
composer phpcsfixer-check

# 5. Ready to develop!
```

## IDE Integration

- **EditorConfig**: `.editorconfig` for consistent formatting
- **PHPStan**: IDE plugins available for real-time analysis
- **PHP CS Fixer**: IDE plugins for automatic formatting
- **Amazon Q**: Memory bank in `.amazonq/rules/memory-bank/`
