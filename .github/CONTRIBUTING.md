# Contributing to CharsetHelper

Thank you for your interest in contributing to CharsetHelper! This document provides guidelines and instructions for contributing.

## 🚀 Getting Started

### Prerequisites

- PHP 7.4, 8.0, 8.1, 8.2, or 8.3
- Composer
- Git

### Development Setup

```bash
# Clone the repository
git clone https://github.com/ducks-project/encoding-repair.git
cd encoding-repair

# Install dependencies
composer install

# Run full CI checks locally
composer ci
```

## 📋 Contribution Workflow

1. **Fork the repository**
   - Click the "Fork" button on GitHub
   - Clone your fork locally

2. **Create a feature branch**

   ```bash
   git checkout -b feature/amazing-feature
   ```

3. **Make your changes**

   - Follow the [Code Quality Standards](#code-quality-standards)
   - Write tests for your changes
   - Update documentation if needed

4. **Test your changes**

   ```bash
   # Run all tests
   composer test

   # Run unit tests with coverage
   composer unittest

   # Run static analysis
   composer phpstan
   composer psalm

   # Check code style
   composer phpcs
   composer phpcsfixer-check
   ```

5. **Commit your changes**

   ```bash
   git commit -m 'Add amazing feature'
   ```

   - Use clear, descriptive commit messages
   - Reference issue numbers when applicable

6. **Push to your fork**

   ```bash
   git push origin feature/amazing-feature
   ```

7. **Open a Pull Request**
   - Go to the original repository
   - Click "New Pull Request"
   - Select your branch
   - Provide a clear description of your changes

## <a name="code-quality-standards"></a> 🎯 Code Quality Standards

### PSR-12 / PER Coding Style

- **Indentation**: 4 spaces (NO tabs)
- **Line length**: Soft limit 120 characters, hard limit 200 characters
- **Braces**: Opening brace on same line for methods/functions
- **Visibility**: ALWAYS declare visibility (public/protected/private)
- **Array syntax**: Use short array syntax `[]` instead of `array()`

### Strict Typing

- **MANDATORY**: Every PHP file MUST start with `declare(strict_types=1);` after the opening tag
- All function parameters and return types MUST be explicitly typed
- Use nullable types (`?Type`) when null is acceptable
- Use union types for mixed but constrained types (PHP 8.0+)

### File Headers

Every PHP file MUST include this exact header format:

```php
<?php

/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ducks\Component\EncodingRepair;
```

### Documentation Standards

- **PHPDoc blocks REQUIRED** for all public/protected methods
- Include `@param` with type and description for each parameter
- Include `@return` with type and description
- Include `@throws` for all exceptions that can be thrown
- Use `@psalm-api` annotation for public API methods

Example:

```php
/**
 * Converts a single value to target encoding.
 *
 * @param mixed $value Value to convert
 * @param string $to Target encoding
 * @param string $from Source encoding
 * @param array<string, mixed> $options Conversion configuration
 *
 * @return mixed
 */
private function convertValue($value, string $to, string $from, array $options)
```

### Static Analysis

- **PHPStan**: Level 8 (strictest)
- **Psalm**: Type coverage tracking
- **No errors allowed**: All checks must pass

### Test Coverage

- **Minimum coverage**: 90% line coverage
- **Critical paths**: 100% coverage for public API methods
- **Edge cases**: Test boundary conditions, empty inputs, invalid inputs
- **Exception testing**: Use `expectException()` and `expectExceptionMessage()`

## 🧪 Testing

### Running Tests

```bash
# Run all tests (unit + benchmarks)
composer test

# Run unit tests with coverage
composer unittest

# Run unit tests with HTML coverage report
composer unittest -- --coverage-html coverage

# Run benchmarks
composer bench
```

### Writing Tests

- **Test class naming**: `{ClassName}Test` (e.g., `CharsetProcessorTest`)
- **Test method naming**: `test{MethodName}{Scenario}` (e.g., `testToUtf8WithInvalidEncoding`)
- **Assertions**: Use specific assertions (`assertSame`, `assertContains`) over generic ones
- **Test isolation**: Each test MUST be independent and create its own fixtures

Example test pattern:

```php
public function testToUtf8(): void
{
    $processor = new CharsetProcessor();

    $result = $processor->toUtf8('test', 'UTF-8');

    $this->assertSame('test', $result);
}

public function testInvalidEncodingThrowsException(): void
{
    $processor = new CharsetProcessor();

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid target encoding');

    $processor->toCharset('test', 'INVALID');
}
```

## 🔧 Available Commands

### Testing

```bash
composer test           # Run all tests (unit + benchmarks)
composer unittest       # PHPUnit with coverage (xdebug required)
composer bench          # PHPBench performance tests
```

### Code Quality

```bash
composer phpstan        # Static analysis (level 8)
composer psalm          # Type coverage analysis
composer phpcs          # Code style check
composer phpcsfixer-check # PHP-CS-Fixer dry-run
composer cs-fix         # Auto-fix code style
composer phpmd          # Mess detection
composer rector         # Automated refactoring (dry-run)
```

### Full CI Check

```bash
composer ci             # Run all checks (tests + quality)
```

## 📝 Documentation Updates

When adding or modifying features, please update:

1. **README.md** - Add usage examples if applicable
2. **CHANGELOG.md** - Follow "Keep a Changelog" format
3. **assets/documentation/** - Update relevant documentation files
4. **PHPDoc comments** - Keep inline documentation up to date

## 🐛 Reporting Bugs

When reporting bugs, please include:

- PHP version
- CharsetHelper version
- Minimal code to reproduce the issue
- Expected behavior
- Actual behavior
- Error messages (if any)

## 💡 Suggesting Features

When suggesting features, please:

- Check if the feature already exists
- Explain the use case
- Provide examples of how it would be used
- Consider backward compatibility

## 📜 Code of Conduct

- Be respectful and inclusive
- Focus on constructive feedback
- Help others learn and grow
- Follow the project's coding standards

## 📄 License

By contributing, you agree that your contributions will be licensed under the MIT License.

## 🙏 Thank You

Your contributions make CharsetHelper better for everyone. Thank you for taking the time to contribute!

---

For questions or help, please:

- 📧 Email: <adrien.loyant@gmail.com>
- 💬 Discussions: <https://github.com/ducks-project/encoding-repair/discussions>
- 🐛 Issues: <https://github.com/ducks-project/encoding-repair/issues>
