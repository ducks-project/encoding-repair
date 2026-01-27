# Development Guidelines

## Code Quality Standards

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

### Naming Conventions

- **Classes**: PascalCase (e.g., `CharsetProcessor`, `MbStringTranscoder`)
- **Interfaces**: PascalCase with `Interface` suffix (e.g., `TranscoderInterface`, `DetectorInterface`)
- **Methods**: camelCase (e.g., `toCharset()`, `registerTranscoder()`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `ENCODING_UTF8`, `MAX_REPAIR_DEPTH`)
- **Private constants**: UPPER_SNAKE_CASE with descriptive names (e.g., `DEFAULT_ENCODINGS`, `JSON_DEFAULT_DEPTH`)
- **Properties**: camelCase with type hints (e.g., `private TranscoderChain $transcoderChain`)

### Documentation Standards

- **PHPDoc blocks REQUIRED** for all public/protected methods
- Include `@param` with type and description for each parameter
- Include `@return` with type and description
- Include `@throws` for all exceptions that can be thrown
- Use `@psalm-api` annotation for public API methods
- Use `@codeCoverageIgnore` for unreachable code paths
- Use `@psalm-suppress` for intentional type violations with explanation

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

### Code Style (PSR-12/PER)

- **Indentation**: 4 spaces (NO tabs)
- **Line length**: Soft limit 120 characters, hard limit 200 characters
- **Braces**: Opening brace on same line for methods/functions
- **Visibility**: ALWAYS declare visibility (public/protected/private)
- **Final classes**: Use `final` for classes not designed for inheritance
- **Array syntax**: Use short array syntax `[]` instead of `array()`
- **String concatenation**: Use `.` with spaces (e.g., `'foo' . $bar . 'baz'`)

## Architectural Patterns

### Chain of Responsibility Pattern

Used extensively for transcoders, detectors, and interpreters:

```php
// Priority-based handler registration
public function register($handler, ?int $priority = null): void
{
    $priority = $priority ?? $handler->getPriority();
    $this->queue->insert($handler, $priority);
}

// Chain execution with fallback
public function execute($data, ...$args)
{
    foreach ($this->queue as $handler) {
        $result = $handler->handle($data, ...$args);
        if (null !== $result) {
            return $result;
        }
    }
    return null;
}
```

**Pattern frequency**: 5/5 files (TranscoderChain, DetectorChain, InterpreterChain)

### Fluent Interface Pattern

All configuration methods return `self` for method chaining:

```php
public function registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self
{
    $this->transcoderChain->register($transcoder, $priority);
    return $this;
}

// Usage
$processor->resetTranscoders()
    ->registerTranscoder(new CustomTranscoder())
    ->addEncodings('SHIFT_JIS');
```

**Pattern frequency**: 4/5 files (CharsetProcessor, all chain classes)

### Static Facade Pattern

CharsetHelper provides static API delegating to CharsetProcessor:

```php
final class CharsetHelper
{
    private static $processor = null;

    private static function getProcessor(): CharsetProcessorInterface
    {
        if (null === self::$processor) {
            self::$processor = new CharsetProcessor();
        }
        return self::$processor;
    }

    public static function toUtf8($data, string $from = self::WINDOWS_1252, array $options = [])
    {
        return self::getProcessor()->toCharset($data, self::ENCODING_UTF8, $from, $options);
    }
}
```

**Pattern frequency**: 1/5 files (CharsetHelper)

### Strategy Pattern with Priority

Handlers implement interfaces and declare priority:

```php
interface TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string;
    public function getPriority(): int;
    public function isAvailable(): bool;
}

class UConverterTranscoder implements TranscoderInterface
{
    public function getPriority(): int
    {
        return 100; // Highest priority
    }
}
```

**Pattern frequency**: 5/5 files (all handler interfaces)

## Common Implementation Patterns

### Validation with Whitelisting

Always validate encodings against whitelist:

```php
private function validateEncoding(string $encoding, string $type): void
{
    $normalized = \strtoupper($encoding);

    if (
        !\in_array($encoding, $this->allowedEncodings, true)
        && !\in_array($normalized, $this->allowedEncodings, true)
    ) {
        throw new InvalidArgumentException(
            \sprintf(
                'Invalid %s encoding: "%s". Allowed: %s',
                $type,
                $encoding,
                \implode(', ', $this->allowedEncodings)
            )
        );
    }
}
```

**Pattern frequency**: 3/5 files

### Options Merging with Defaults

Use `array_replace()` for configuration merging:

```php
private function configureOptions(array $options, array ...$replacements): array
{
    $replacements[] = $options;

    return \array_replace(
        ['normalize' => true, 'translit' => true, 'ignore' => true, 'encodings' => self::DEFAULT_ENCODINGS],
        ...$replacements
    );
}
```

**Pattern frequency**: 2/5 files

### Recursive Processing with Callbacks

Use closures for recursive data transformation:

```php
/**
 * @psalm-suppress MissingClosureParamType
 * @psalm-suppress MissingClosureReturnType
 */
$callback = fn ($value) => $this->convertValue($value, $to, $from, $options);

return $this->applyRecursive($data, $callback);
```

**Pattern frequency**: 3/5 files

### Fast-Path Optimization

Check simple cases first before expensive operations:

```php
private function isValidUtf8(string $string): bool
{
    // Fast-path: ASCII-only strings (0x00-0x7F) are always valid UTF-8
    if (!\preg_match('/[\x80-\xFF]/', $string)) {
        return true;
    }

    // Full UTF-8 validation for non-ASCII strings
    return false !== @\preg_match('//u', $string);
}
```

**Pattern frequency**: 4/5 files

## Testing Standards

### Test Structure

- **Test class naming**: `{ClassName}Test` (e.g., `CharsetProcessorTest`)
- **Test method naming**: `test{MethodName}{Scenario}` (e.g., `testToUtf8WithInvalidEncoding`)
- **Assertions**: Use specific assertions (`assertSame`, `assertContains`) over generic ones
- **Test isolation**: Each test MUST be independent and create its own fixtures

### Test Coverage Requirements

- **Minimum coverage**: 90% line coverage
- **Critical paths**: 100% coverage for public API methods
- **Edge cases**: Test boundary conditions, empty inputs, invalid inputs
- **Exception testing**: Use `expectException()` and `expectExceptionMessage()`

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

### Mocking Standards

- Use PHPUnit's `createMock()` for interface mocking
- Configure mock behavior with `method()` and `willReturn()`
- Verify mock interactions when testing side effects

```php
$mapper = $this->createMock(PropertyMapperInterface::class);
$processor->registerPropertyMapper(\stdClass::class, $mapper);
```

## Performance Optimization Patterns

### Lazy Initialization

Defer expensive object creation until needed:

```php
private static function getProcessor(): CharsetProcessorInterface
{
    if (null === self::$processor) {
        self::$processor = new CharsetProcessor();
    }
    return self::$processor;
}
```

### Early Returns

Exit early when conditions are met:

```php
if ($this->isValidUtf8($value)) {
    return $this->convertString($value, $to, self::ENCODING_UTF8, $options);
}

if (\mb_check_encoding($value, $to)) {
    return $this->normalize($value, $to, $options);
}
```

### Batch Processing Optimization

Detect encoding once for homogeneous arrays:

```php
public function toCharsetBatch(array $items, string $to, string $from, array $options): array
{
    if (self::AUTO === $from) {
        $from = $this->detectBatch($items, $options);
    }

    return \array_map(fn ($item) => $this->toCharset($item, $to, $from, $options), $items);
}
```

## Error Handling

### Exception Types

- **InvalidArgumentException**: Invalid input parameters (encoding, options)
- **RuntimeException**: Runtime errors (missing dependencies, configuration issues)
- **JsonException**: JSON encoding/decoding failures (use `JSON_THROW_ON_ERROR`)

### Error Messages

- Include context: parameter name, invalid value, allowed values
- Use `sprintf()` for formatted messages
- Be specific and actionable

```php
throw new InvalidArgumentException(
    \sprintf(
        'Invalid %s encoding: "%s". Allowed: %s',
        $type,
        $encoding,
        \implode(', ', $this->allowedEncodings)
    )
);
```

## Dependency Injection

### Constructor Injection

Inject dependencies through constructor:

```php
public function __construct(InterpreterChain $chain)
{
    $this->chain = $chain;
}
```

### Interface-Based Dependencies

Depend on interfaces, not concrete implementations:

```php
private TranscoderChain $transcoderChain;
private DetectorChain $detectorChain;
private InterpreterChain $interpreterChain;
```

## Backward Compatibility

### API Stability

- **Public API**: NEVER break without major version bump
- **Internal API**: Can change between minor versions
- **Deprecated features**: Mark with `@deprecated` and maintain for one major version

### Static Facade Preservation

CharsetHelper maintains 100% backward compatibility:

```php
// Old code still works
$utf8 = CharsetHelper::toUtf8($data);

// New code can use service
$processor = new CharsetProcessor();
$utf8 = $processor->toUtf8($data);
```

## Code Comments

### When to Comment

- **Complex algorithms**: Explain the "why" not the "what"
- **Performance optimizations**: Document why optimization is needed
- **Workarounds**: Explain why workaround exists and link to issue
- **Edge cases**: Document non-obvious behavior

### When NOT to Comment

- **Self-explanatory code**: Good naming eliminates need for comments
- **Obvious operations**: Don't state what code clearly does
- **Redundant PHPDoc**: If type hints are sufficient, skip PHPDoc

Example of good comments:

```php
// Clean invalid UTF-8 sequences first (edge case: malformed bytes like \xC2\x88)
$value = \mb_scrub($value, 'UTF-8');

// Quick check: if no corruption patterns, return as-is
if (false === \strpos($value, "\xC3\x82") && false === \strpos($value, "\xC3\x83")) {
    return $value;
}
```
