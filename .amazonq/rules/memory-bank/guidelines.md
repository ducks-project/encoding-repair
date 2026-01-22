# Development Guidelines

## Code Quality Standards

### Strict Type Declarations

**Frequency: 100% of PHP files**:

Every PHP file MUST start with strict type declaration:

```php
declare(strict_types=1);
```

This enforces type safety throughout the codebase and prevents type coercion bugs.

### File Headers

**Frequency: 100% of PHP files**:

All PHP files include a standardized header:

```php
/**
 * Part of EncodingRepair package.
 *
 * (c) Adrien Loyant <donald_duck@team-df.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
```

### Namespace Structure

**Frequency: 100% of PHP files**:

All classes use the base namespace:

```php
namespace Ducks\Component\EncodingRepair;
```

Subnamespaces for organization:

- `Ducks\Component\EncodingRepair\Transcoder\` - Encoding conversion strategies
- `Ducks\Component\EncodingRepair\Detector\` - Encoding detection strategies
- `Ducks\Component\EncodingRepair\Tests\phpunit\` - Unit tests

### Class Finality

**Frequency: 90%+ of classes**:

Classes are marked as `final` to prevent inheritance and encourage composition:

```php
final class CharsetHelper { }
final class TranscoderChain { }
final class IconvTranscoder implements TranscoderInterface { }
```

Only interfaces are not final. This enforces SOLID principles and prevents fragile base class problems.

### Visibility Modifiers

**Frequency: 100% of methods and properties**:

All methods and properties MUST have explicit visibility:

```php
public function transcode(): ?string { }
private function buildSuffix(): string { }
private ?SplPriorityQueue $queue;
```

Never omit visibility modifiers (no implicit public).

## Type System Standards

### Return Type Declarations

**Frequency: 100% of methods**:

All methods MUST declare return types:

```php
public function detect(string $string, ?array $options = null): ?string
public function getPriority(): int
public function isAvailable(): bool
private function buildSuffix(array $options): string
```

Use nullable types (`?string`, `?int`) when methods can return null.

### Parameter Type Hints

**Frequency: 100% of parameters**:

All parameters MUST have type hints:

```php
public function transcode(string $data, string $to, string $from, ?array $options = null): ?string
public function register(TranscoderInterface $transcoder, ?int $priority = null): void
```

### PHPDoc Type Annotations

**Frequency: 100% of complex types**:

Use PHPDoc for array shapes, generics, and complex types:

```php
/**
 * @var null|SplPriorityQueue<int, TranscoderInterface>
 */
private ?SplPriorityQueue $queue;

/**
 * @var list<array{transcoder: TranscoderInterface, priority: int}>
 */
private $registered = [];

/**
 * @param array<string, mixed> $options Conversion options
 * @return list{0: int, 1?: resource}
 */
private function resolveOptions(array $options): array
```

### Psalm/PHPStan Annotations

**Frequency: As needed for static analysis**:

Use static analysis annotations:

```php
/**
 * @psalm-api - Marks public API methods
 * @psalm-immutable - Marks immutable classes
 * @psalm-suppress MissingClosureParamType - Suppresses specific issues
 * @codeCoverageIgnore - Excludes code from coverage
 * @codeCoverageIgnoreStart / @codeCoverageIgnoreEnd - Excludes blocks
 */
```

## Coding Conventions

It must follow PSR-12 / PER Coding Style.

### Naming Conventions

**Classes**: PascalCase

```php
CharsetHelper, TranscoderChain, IconvTranscoder
```

**Methods**: camelCase

```php
toUtf8(), registerTranscoder(), isAvailable()
```

**Constants**: UPPER_SNAKE_CASE

```php
const ENCODING_UTF8 = 'UTF-8';
const MAX_REPAIR_DEPTH = 5;
```

**Variables**: camelCase

```php
$transcoderChain, $detectorChain, $finalPriority
```

**Private methods**: camelCase with descriptive names

```php
private function buildSuffix(): string
private function rebuildQueue(): void
private function getSplPriorityQueue(): SplPriorityQueue
```

### Method Organization

Methods are organized by visibility and purpose:

1. Constructor
2. Public methods (API)
3. Private methods (implementation details)

Example from TranscoderChain:

```php
public function __construct() { }
public function register() { }
public function transcode() { }
private function rebuildQueue() { }
private function getSplPriorityQueue() { }
```

### Return Early Pattern

**Frequency: 80%+ of methods**:

Use early returns for guard clauses:

```php
public function detect(string $string, ?array $options = null): ?string
{
    if (!$this->isAvailable()) {
        return null;
    }

    // Main logic here
}
```

### Null Coalescing Operator

**Frequency: Very common**:

Use `??` for default values:

```php
$finalPriority = $priority ?? $transcoder->getPriority();
$flags = $options['finfo_flags'] ?? \FILEINFO_NONE;
$maxDepth = $options['maxDepth'] ?? self::MAX_REPAIR_DEPTH;
```

### Ternary Operator for Simple Conditions

**Frequency: Common**:

Use ternary for simple conditional assignments:

```php
return false !== $result ? $result : null;
$suffix = true === ($options['translit'] ?? true) ? '//TRANSLIT' : '';
```

## Error Handling Patterns

### Silence Operator for Performance

**Frequency: Specific use cases**:

Use `@` operator instead of error handlers in performance-critical code:

```php
// Use silence operator (@) instead of
// \set_error_handler(static fn (): bool => true);
// set_error_handler is too expensive for high-volume loops.
$result = @\iconv($from, $to . $suffix, $data);
```

Document why silence operator is used.

### Null Returns for Chain of Responsibility

**Frequency: 100% of chain handlers**:

Return `null` to pass control to next handler:

```php
public function transcode(string $data, string $to, string $from, ?array $options = null): ?string
{
    if (!$this->isAvailable()) {
        return null; // Try next transcoder
    }

    $result = @\iconv($from, $to . $suffix, $data);
    return false !== $result ? $result : null;
}
```

### Exception Throwing

**Frequency: For invalid input only**:

Throw exceptions for invalid input, not for expected failures:

```php
throw new InvalidArgumentException(
    'Transcoder must be an instance of TranscoderInterface or a callable'
);

throw new RuntimeException(
    'JSON Encode Error: ' . \json_last_error_msg()
);
```

## Testing Standards

### Test Class Naming

**Frequency: 100% of test files**:

Test classes follow pattern: `{ClassName}Test`

```php
final class CharsetHelperTest extends TestCase
final class IconvTranscoderTest extends TestCase
```

### Test Method Naming

**Frequency: 100% of test methods**:

Test methods use descriptive names with `test` prefix:

```php
public function testToUtf8WithIsoString(): void
public function testRegisterTranscoderThrowsOnInvalidType(): void
public function testSafeJsonEncodeWithFlags(): void
```

### Test Method Structure

**Frequency: 100% of tests**:

Follow Arrange-Act-Assert pattern:

```php
public function testToUtf8WithIsoString(): void
{
    // Arrange
    $iso = \mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');

    // Act
    $result = CharsetHelper::toUtf8($iso, CharsetHelper::ENCODING_ISO);

    // Assert
    $this->assertSame('Café', $result);
    $this->assertTrue(\mb_check_encoding($result, 'UTF-8'));
}
```

### Void Return Type for Tests

**Frequency: 100% of test methods**:

All test methods return `void`:

```php
public function testToUtf8WithArray(): void
```

### Exception Testing

**Frequency: All exception scenarios**:

Use expectException methods:

```php
public function testSafeJsonDecodeThrowsOnInvalidJson(): void
{
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('JSON Decode Error');

    CharsetHelper::safeJsonDecode('invalid json{');
}
```

## Documentation Standards

### Class-Level Documentation

**Frequency: 100% of classes**:

All classes have PHPDoc blocks:

```php
/**
 * Helper class for encoding and detect charset.
 *
 * Designed to handle legacy ISO-8859-1 <-> UTF-8 interoperability issues.
 * Implements Chain of Responsibility pattern for extensibility.
 *
 * @psalm-api
 * @psalm-immutable This class has no mutable state
 * @final
 */
final class CharsetHelper
```

### Method Documentation

**Frequency: 100% of public methods**:

Public methods have complete PHPDoc:

```php
/**
 * Register a transcoder with optional priority override.
 *
 * @param TranscoderInterface $transcoder Transcoder instance
 * @param int|null $priority Priority override (null = use transcoder's default)
 *
 * @return void
 */
public function register(TranscoderInterface $transcoder, ?int $priority = null): void
```

### Inline Comments for Complex Logic

**Frequency: As needed**:

Use inline comments to explain non-obvious logic:

```php
// Use silence operator (@) instead of set_error_handler
// set_error_handler is too expensive for high-volume loops.
$result = @\iconv($from, $to . $suffix, $data);
```

### @inheritDoc Usage

**Frequency: 100% of interface implementations**:

Use `@inheritDoc` for interface method implementations:

```php
/**
 * @inheritDoc
 */
public function detect(string $string, ?array $options = null): ?string
```

## Design Patterns

### Chain of Responsibility

**Frequency: Core pattern**:

Used for Transcoder and Detector systems:

```php
// Handler interface
interface TranscoderInterface {
    public function transcode(string $data, string $to, string $from, ?array $options = null): ?string;
}

// Chain coordinator
final class TranscoderChain {
    public function transcode(string $data, string $to, string $from, array $options): ?string {
        foreach ($this->queue as $transcoder) {
            $result = $transcoder->transcode($data, $to, $from, $options);
            if (null !== $result) {
                return $result; // First successful handler wins
            }
        }
        return null;
    }
}
```

### Strategy Pattern

**Frequency: All transcoder/detector implementations**:

Each transcoder/detector is an interchangeable strategy:

```php
final class IconvTranscoder implements TranscoderInterface { }
final class MbStringTranscoder implements TranscoderInterface { }
final class UConverterTranscoder implements TranscoderInterface { }
```

### Facade Pattern

**Frequency: Main API**:

CharsetHelper provides simplified facade:

```php
// Simple API hides complex chain management
CharsetHelper::toUtf8($data);
CharsetHelper::repair($data);
CharsetHelper::safeJsonEncode($data);
```

### Immutable Object Pattern

**Frequency: Object processing**:

Objects are cloned before modification:

```php
private static function applyToObject(object $data, callable $callback): object
{
    $copy = clone $data; // Always clone
    // ... modify $copy
    return $copy;
}
```

## Performance Optimizations

### Fast-Path Optimization

**Frequency: Critical paths**:

Check common cases first:

```php
public static function detect(string $string, array $options = []): string
{
    // Fast common return
    if (self::isValidUtf8($string)) {
        return self::ENCODING_UTF8;
    }

    // Slower detection logic
    $detected = self::getDetectorChain()->detect($string, $options);
    return $detected ?? self::ENCODING_ISO;
}
```

### Lazy Initialization

**Frequency: Singleton-like instances**:

Initialize expensive objects only when needed:

```php
private static function getTranscoderChain(): TranscoderChain
{
    if (null === self::$transcoderChain) {
        self::$transcoderChain = new TranscoderChain();
        // ... register transcoders
    }
    return self::$transcoderChain;
}
```

### Priority Queue for Ordering

**Frequency: Chain implementations**:

Use SplPriorityQueue for efficient priority-based ordering:

```php
private ?SplPriorityQueue $queue;

public function register(TranscoderInterface $transcoder, ?int $priority = null): void
{
    $finalPriority = $priority ?? $transcoder->getPriority();
    $this->getSplPriorityQueue()->insert($transcoder, $finalPriority);
}
```

## Code Coverage Annotations

### Ignoring Unreachable Code

**Frequency: Defensive programming**:

Use coverage annotations for defensive code:

```php
if (!$this->isAvailable()) {
    // @codeCoverageIgnoreStart
    return null;
    // @codeCoverageIgnoreEnd
}
```

### Ignoring Fallback Paths

**Frequency: Chain of Responsibility**:

Ignore fallback returns that are never reached in tests:

```php
foreach ($this->queue as $transcoder) {
    $result = $transcoder->transcode($data, $to, $from, $options);
    if (null !== $result) {
        return $result;
    }
}

// @codeCoverageIgnoreStart
return null; // Never reached in tests (always has fallback)
// @codeCoverageIgnoreEnd
```

## Static Analysis Compliance

### PHPStan Level 8

**Frequency: 100% compliance**:

Code must pass PHPStan level 8 (maximum strictness):

- No mixed types without documentation
- No undefined variables
- No dead code
- Strict comparison operators

### Psalm Type Coverage

**Frequency: 100% target**:

All public methods fully typed:

- Parameter types
- Return types
- Property types
- Array shapes documented

### PHPDoc for Static Analysis

**Frequency: Complex types**:

Use PHPDoc to help static analyzers:

```php
/** @var mixed $magic */
$magic = $options['finfo_magic'] ?? null;
if (\is_string($magic)) {
    $args[] = $magic;
}
```

## Functional Programming Patterns

### Arrow Functions

**Frequency: Common for callbacks**:

Use arrow functions for simple callbacks:

```php
$callback = static fn ($value) => self::convertValue($value, $to, $from, $options);

return \array_map(
    static fn ($item) => self::applyRecursive($item, $callback),
    $data
);
```

### Static Closures

**Frequency: 100% of closures**:

Always use `static` keyword for closures:

```php
static fn ($value) => self::convertValue($value, $to, $from, $options)
```

This prevents accidental `$this` binding and improves performance.

## Constants and Configuration

### Public Constants for API

**Frequency: All encoding names**:

Define constants for user-facing values:

```php
public const AUTO = 'AUTO';
public const ENCODING_UTF8 = 'UTF-8';
public const ENCODING_ISO = 'ISO-8859-1';
public const WINDOWS_1252 = 'CP1252';
```

### Private Constants for Defaults

**Frequency: Internal configuration**:

Use private constants for internal defaults:

```php
private const MAX_REPAIR_DEPTH = 5;
private const JSON_DEFAULT_DEPTH = 512;
private const DEFAULT_ENCODINGS = [
    self::ENCODING_UTF8,
    self::WINDOWS_1252,
    self::ENCODING_ISO,
];
```

## Array Handling

### Array Type Hints

**Frequency: 100% of array parameters**:

Always specify array types in PHPDoc:

```php
/**
 * @param array<string, mixed> $options
 * @return list<array{transcoder: TranscoderInterface, priority: int}>
 */
```

### Array Spread Operator

**Frequency: Variable arguments**:

Use spread operator for variable arguments:

```php
$finfo = new finfo(FILEINFO_MIME_ENCODING, ...$args);
$detected = $finfo->buffer($string, ...$this->resolveOptions($options ?? []));
```

### Array Functions

**Frequency: Common**:

Prefer array functions over loops:

```php
return \array_map(
    static fn ($item) => self::applyRecursive($item, $callback),
    $data
);
```

## Summary

This codebase follows strict modern PHP standards with:

- 100% strict typing
- 100% type coverage
- Final classes by default
- Chain of Responsibility for extensibility
- Immutable object handling
- Comprehensive testing
- PHPStan level 8 compliance
- Performance-conscious design
- Clear documentation
