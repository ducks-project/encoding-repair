# Development

## Setup

```bash
git clone https://github.com/ducks-project/encoding-repair.git
cd encoding-repair
composer install
```

## Verify Installation

```bash
php -v                     # Check PHP version (>= 7.4)
php -m | grep mbstring     # Verify mbstring
php -m | grep json         # Verify json
php -m | grep intl         # Check intl (optional)
```

## Running Tests

```bash
# Run all tests
composer test

# Run unit tests with coverage
composer unittest

# Run benchmarks
composer bench

# Run specific test
./vendor/bin/phpunit tests/phpunit/CharsetHelperTest.php
```

## Code Quality

```bash
# Static analysis
composer phpstan

# Type checking
composer psalm

# Check code style
composer phpcsfixer-check

# Fix code style
./vendor/bin/php-cs-fixer fix

# Run all quality checks
composer phpstan && composer psalm && composer phpcsfixer-check
```

## Full CI Pipeline

```bash
# Run all CI checks locally
composer ci
```

## Project Structure

```text
encoding-repair/
├── CharsetHelper.php        # Main library class
├── tests/
│   ├── Phpunit/            # Unit tests
│   └── Benchmark/          # Performance benchmarks
├── docs/                   # Documentation
├── composer.json           # Dependencies
└── .github/workflows/      # CI/CD
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure tests pass (`composer test`)
5. Run static analysis (`composer phpstan`)
6. Fix code style (`composer phpcsfixer-check`)
7. Commit your changes (`git commit -m 'Add amazing feature'`)
8. Push to the branch (`git push origin feature/amazing-feature`)
9. Open a Pull Request

## Next Steps

- [Code Quality Standards](code-quality.md)
- [API Reference](../api/CharsetHelper.md)
