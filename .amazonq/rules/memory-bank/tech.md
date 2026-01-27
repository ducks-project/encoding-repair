# Technology Stack

## Programming Languages

### PHP

- **Versions**: 7.4, 8.0, 8.1, 8.2, 8.3
- **Type System**: Strict typing enabled (`declare(strict_types=1)`)
- **Standards**: PSR-12/PER coding style, PSR-16 (Simple Cache)

## Required Extensions

### Core Dependencies

- **ext-json** - JSON encoding/decoding operations
- **ext-mbstring** - Multi-byte string handling, encoding detection/conversion

### Optional Extensions (Recommended)

- **ext-intl** - UConverter support (30% faster, best precision)
- **ext-iconv** - Transliteration support
- **ext-fileinfo** - Advanced encoding detection

## Dependencies

### Production

```json
{
  "php": ">=7.4",
  "ext-json": "*",
  "ext-mbstring": "*",
  "psr/simple-cache": "^1.0"
}
```

### Development

- **friendsofphp/php-cs-fixer** ^3.0 - Code style fixer
- **phpunit/phpunit** ^9.5 || ^10.0 - Unit testing
- **phpstan/phpstan** ^1.10 - Static analysis (level 8)
- **phpstan/phpstan-phpunit** ^1.4 - PHPUnit extensions for PHPStan
- **vimeo/psalm** ^4.30 || ^5.0 - Static analysis with type coverage
- **phpbench/phpbench** ^1.2 - Performance benchmarking
- **phpmd/phpmd** ^1.5 - Mess detector
- **squizlabs/php_codesniffer** ^3.10 - Code sniffer
- **ergebnis/composer-normalize** ^2.48 - Composer.json normalization

## Build System

### Composer Scripts

**Testing:**

```bash
composer test           # Run all tests (unit + benchmarks)
composer unittest       # PHPUnit with coverage (xdebug required)
composer bench          # PHPBench performance tests
```

**Code Quality:**

```bash
composer phpstan        # Static analysis (level 8)
composer psalm          # Type coverage analysis
composer phpcs          # Code style check
composer phpcsfixer-check # PHP-CS-Fixer dry-run
composer phpmd          # Mess detection
composer rector         # Automated refactoring (dry-run)
```

**Configuration Files:**

- `phpunit.xml.dist` - PHPUnit configuration with process isolation
- `phpstan.neon.dist` - PHPStan level 8 configuration
- `psalm.xml.dist` - Psalm strict mode configuration
- `phpcs.xml.dist` - PHP_CodeSniffer PSR-12 rules
- `.php-cs-fixer.dist.php` - PHP-CS-Fixer PER rules
- `phpbench.json.dist` - PHPBench configuration
- `phpmd.xml.dist` - PHPMD rulesets

## Development Commands

### Installation

```bash
composer install        # Install dependencies
```

### Testing Workflow

```bash
# Run unit tests with coverage
composer unittest

# Run benchmarks
composer bench

# Full test suite
composer test
```

### Code Quality Workflow

```bash
# Static analysis
composer phpstan
composer psalm

# Code style
composer phpcs
composer phpcsfixer-check

# Mess detection
composer phpmd
```

### CI/CD Integration

- **GitHub Actions**: `.github/workflows/ci.yml`
- **Coverage**: Coveralls, Codecov integration
- **Security**: `.github/workflows/security.yml`
- **Release**: `.github/workflows/release.yml`

## Project Configuration

### Autoloading

```json
{
  "autoload": {
    "psr-4": {
      "Ducks\\Component\\EncodingRepair\\": ""
    },
    "exclude-from-classmap": ["/tests/"]
  },
  "autoload-dev": {
    "psr-4": {
      "Ducks\\Component\\EncodingRepair\\Tests\\": "tests/"
    }
  }
}
```

### Namespace Structure

- **Root**: `Ducks\Component\EncodingRepair`
- **Cache**: `Ducks\Component\EncodingRepair\Cache`
- **Detector**: `Ducks\Component\EncodingRepair\Detector`
- **Interpreter**: `Ducks\Component\EncodingRepair\Interpreter`
- **Traits**: `Ducks\Component\EncodingRepair\Traits`
- **Transcoder**: `Ducks\Component\EncodingRepair\Transcoder`
- **Tests**: `Ducks\Component\EncodingRepair\Tests`

## Documentation

### ReadTheDocs

- **Source**: `docs/` directory (Markdown)
- **Config**: `.readthedocs.yml`
- **Build**: MkDocs (`mkdocs.yml`)
- **URL**: <https://encoding-repair.readthedocs.io>

### API Documentation

- **Location**: `assets/documentation/`
- **Format**: Markdown with class/method details
- **Generation**: Manual (part of update process)

## Version Control

### Git Configuration

- **Ignore**: `.gitignore` (vendor, coverage, cache files)
- **History**: `.history/` (local file history tracking)

### Branching Strategy

- **Main**: Stable releases
- **Feature branches**: New features/fixes
- **Semantic Versioning**: MAJOR.MINOR.PATCH (v1.2.0)

## Performance Tools

### Benchmarking

- **PHPBench**: Micro-benchmarks for critical paths
- **Benchmarks**: `tests/Benchmark/` directory
- **Metrics**: Time, memory, iterations
- **Reports**: Aggregate, comparison, retry threshold

### Profiling

- **Xdebug**: Code coverage and profiling
- **Mode**: `xdebug.mode=coverage` for tests

## Quality Metrics

### Code Coverage

- **Target**: 98%+ line coverage
- **Tools**: PHPUnit with Xdebug
- **Reports**: HTML, Clover, Coveralls

### Static Analysis

- **PHPStan**: Level 8 (strictest)
- **Psalm**: Type coverage tracking
- **Baseline**: No errors allowed

### Code Style

- **Standard**: PSR-12/PER
- **Enforcement**: PHP-CS-Fixer, PHP_CodeSniffer
- **CI**: Automated checks on pull requests

## IDE Support

### VS Code

- **Workspace**: `encoding-repair.code-workspace`
- **Extensions**: PHP Intelephense, PHPUnit, PHP Debug

### Editor Config

- **File**: `.editorconfig`
- **Settings**: Indentation, line endings, charset
