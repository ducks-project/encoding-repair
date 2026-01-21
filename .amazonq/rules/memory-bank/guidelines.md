# Development Guidelines

## Code Quality Standards

### Strict Typing

- **ALWAYS** use `declare(strict_types=1);` at the top of every PHP file
- Enable strict type checking for all function parameters and return types
- Example from CharsetHelper.php:

```php
<?php

declare(strict_types=1);

namespace Ducks\Component\Component\EncodingRepair;
```

### File Header Documentation

Every file MUST include a standardized header block:

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

### Type Declarations

- **ALWAYS** declare parameter types and return types for all methods
- Use nullable types (`?string`) when appropriate
- Example:

```php
public static function toCharset(
    $data,
    string $to = self::ENCODING_UTF8,
    string $from = self::ENCODING_ISO,
    array $options = []
) {
    // Implementation
}
```

### PHPDoc Standards

- **ALWAYS** include PHPDoc blocks for all public methods
- Include `@param` tags with full type information including array shapes
- Include `@return` tags with precise return types
- Include `@throws` tags for all exceptions
- Use `@psalm-immutable` for immutable classes
- Use `@final` annotation for classes that shouldn't be extended
- Example:

```php
/**
 * Convert $data string from one encoding to another.
 *
 * @param string|array|object $data Data to convert
 * @param string $to Target encoding
 * @param string $from Source encoding (use AUTO for detection)
 * @param array<string, mixed> $options Conversion options
 * - 'normalize': bool (default: true)
 * - 'translit': bool (default: true)
 * - 'ignore': bool (default: true)
 *
 * @return string|array|object The data transcoded in the target encoding
 *
 * @throws InvalidArgumentException If encoding is invalid
 */
```

### Array Type Annotations

- Use precise array type annotations: `array<string, mixed>`, `list<string>`
- Document array structure in PHPDoc when complex
- Example:

```php
/**
 * @var list<string|callable(string, string, string, array<string, mixed>): (string|null)>
 */
private static $transcoders = [
    'transcodeWithUConverter',
    'transcodeWithIconv',
    'transcodeWithMbString',
];
```

## Coding Style (PSR-12 / PER)

### PHP CS Fixer Configuration

The project uses PHP CS Fixer with these key rules:

#### Spacing and Formatting

- **Binary operators**: Single space around operators
- **Cast spaces**: Single space after cast `(string) $value`
- **Concat space**: Single space around concatenation operator `. $var .`
- **Function typehint space**: Space after closing parenthesis before colon
- **No extra blank lines**: Remove unnecessary blank lines
- **Single space after construct**: `if (`, `foreach (`, etc.

#### Naming Conventions

- **Magic constant casing**: `__DIR__`, `__FILE__` (uppercase)
- **Magic method casing**: `__construct`, `__toString` (lowercase)
- **Native function casing**: Lowercase for built-in functions `\array_map`, `\is_string`

#### Code Organization

- **No unused imports**: Remove unused `use` statements
- **No unneeded curly braces**: Remove unnecessary braces
- **No unneeded import alias**: Don't alias imports unnecessarily
- **Linebreak after opening tag**: Always add newline after `<?php`

#### PHPDoc Standards

- **Align left**: PHPDoc tags aligned to left (not vertical alignment)
- **No access tags**: Don't use `@access` tags
- **No package tags**: Don't use `@package` tags
- **Trim consecutive blank lines**: Remove extra blank lines in PHPDoc
- **Summary required**: First line must be a summary

#### Yoda Style

- **Enabled**: Use Yoda conditions for comparisons
- Example: `if (null !== $result)` instead of `if ($result !== null)`
- Example: `if (false === $detected)` instead of `if ($detected === false)`

### Code Style Examples

#### Correct Yoda Style

```php
if (null !== $detected) {
    return $detected;
}

if (false !== $result) {
    return $result;
}

if (self::ENCODING_UTF8 === $targetEncoding) {
    return self::normalize($result, $targetEncoding, $options);
}
```

#### Correct Spacing

```php
// Binary operators
$result = $a + $b;
$isValid = $x === $y;

// Concatenation
$message = 'Error: ' . $errorMsg . ' occurred';

// Cast
$string = (string) $value;

// Function calls
$result = \array_map(static fn($item) => $item, $data);
```

#### Correct PHPDoc

```php
/**
 * Detects the charset encoding of a string.
 *
 * @param string $string String to analyze
 * @param array<string, mixed> $options Conversion options
 * - 'encodings': array of encodings to test
 *
 * @return string Detected encoding (uppercase)
 */
public static function detect(string $string, array $options = []): string
{
    // Implementation
}
```

## Architectural Patterns

### Static Utility Class Pattern

- Use `final class` to prevent inheritance
- Make constructor `private` to prevent instantiation
- All methods should be `public static`
- No instance properties (stateless)
- Example:

```php
/**
 * @psalm-immutable This class has no mutable state
 * @final
 */
final class CharsetHelper
{
    /**
     * Private constructor to prevent instantiation of static utility class.
     */
    private function __construct() {}

    public static function toUtf8(
        $data,
        string $from = self::WINDOWS_1252,
        array $options = []
    ) {
        // Implementation
    }
}
```

### Chain of Responsibility Pattern

- Use static arrays to hold provider chains
- Providers return `null` to pass to next in chain
- Loop through providers until one succeeds
- Example:

```php
/**
 * @var list<string|callable(string, string, string, array<string, mixed>): (string|null)>
 */
private static $transcoders = [
    'transcodeWithUConverter',  // Priority 1
    'transcodeWithIconv',       // Priority 2
    'transcodeWithMbString',    // Priority 3
];

// Usage
foreach (self::$transcoders as $transcoder) {
    $result = self::invokeProvider($transcoder, $data, $to, $from, $options);

    if (null !== $result) {
        return $result;
    }
}
```

### Immutable Operations

- **ALWAYS** clone objects before modification
- Never mutate input parameters
- Return new instances instead of modifying existing ones
- Example:

```php
private static function applyToObject(object $data, callable $callback): object
{
    $copy = clone $data;

    $properties = \get_object_vars($copy);
    foreach ($properties as $key => $value) {
        $copy->$key = self::applyRecursive($value, $callback);
    }

    return $copy;
}
```

### Recursive Processing Pattern

- Use callback-based transformation
- Handle arrays, objects, and scalars uniformly
- Preserve data structure
- Example:

```php
private static function applyRecursive($data, callable $callback)
{
    if (\is_array($data)) {
        return \array_map(
            static fn($item) => self::applyRecursive($item, $callback),
            $data
        );
    }

    if (\is_object($data)) {
        return self::applyToObject($data, $callback);
    }

    return $callback($data);
}
```

### Fail-Safe Fallback Pattern

- Try multiple strategies in priority order
- Return original data if all strategies fail
- Use null coalescing for graceful degradation
- Example:

```php
private static function convertString(
    string $data,
    string $to,
    string $from,
    array $options
): string {
    // Return original if everything failed
    return self::transcodeString($data, $to, $from, $options) ?? $data;
}
```

## Error Handling

### Validation

- **ALWAYS** validate input parameters early
- Throw `InvalidArgumentException` for invalid inputs
- Use descriptive error messages with context
- Example:

```php
private static function validateEncoding(string $encoding, string $type): void
{
    $normalized = \strtoupper($encoding);

    if (
        !\\in_array($encoding, self::ALLOWED_ENCODINGS, true)
        && !\\in_array($normalized, self::ALLOWED_ENCODINGS, true)
    ) {
        throw new InvalidArgumentException(
            \\sprintf(
                'Invalid %s encoding: "%s". Allowed: %s',
                $type,
                $encoding,
                \\implode(', ', self::ALLOWED_ENCODINGS)
            )
        );
    }
}
```

### Exception Handling

- Use `RuntimeException` for runtime errors (e.g., JSON encoding failures)
- Include error context in exception messages
- Use `json_last_error_msg()` for JSON errors
- Example:

```php
$json = \\json_encode($data, $flags, $depth);

if (false === $json) {
    throw new RuntimeException(
        'JSON Encode Error: ' . \\json_last_error_msg()
    );
}
```

### Silent Error Suppression

- Use `@` operator sparingly, only for known false positives
- Document why error suppression is needed
- Example:

```php
// Use silence operator (@) instead of set_error_handler
// set_error_handler is too expensive for high-volume loops.
$result = @\\iconv($from, $to . $suffix, $data);
```

## Performance Optimization

### Fast Path Optimization

- Check common cases first before expensive operations
- Example:

```php
public static function detect(string $string, array $options = []): string
{
    // Fast common return.
    if (self::isValidUtf8($string)) {
        return self::ENCODING_UTF8;
    }

    // Expensive detection logic...
}
```

### Avoid Expensive Operations

- Prefer `@` operator over `set_error_handler` in loops
- Cache detection results when possible
- Use early returns to avoid unnecessary processing

### Extension Priority

- Prioritize faster extensions: UConverter > iconv > mbstring
- Check extension availability before use
- Example:

```php
if (!\\class_exists(UConverter::class)) {
    return null;
}
```

## Constants and Configuration

### Class Constants

- Use `public const` for API constants
- Use `private const` for internal configuration
- Group related constants together
- Use SCREAMING_SNAKE_CASE
- Example:

```php
public const AUTO = 'AUTO';
public const ENCODING_UTF8 = 'UTF-8';
public const ENCODING_ISO = 'ISO-8859-1';

private const MAX_REPAIR_DEPTH = 5;
private const JSON_DEFAULT_DEPTH = 512;
```

### Default Values

- Use const arrays for default configurations
- Document array structure
- Example:

```php
private const DEFAULT_ENCODINGS = [
    self::ENCODING_UTF8,
    self::WINDOWS_1252,
    self::ENCODING_ISO,
    self::ENCODING_ASCII,
];
```

## Options Pattern

### Configuration Merging

- Use `array_replace` for merging options with defaults
- Support multiple override layers
- Document all available options in PHPDoc
- Example:

```php
/**
 * @param array<string, mixed> $options User-provided options
 * @param array<string, mixed> ...$replacements Additional override layers
 *
 * @return array<string, mixed> Merged configuration
 */
private static function configureOptions(
    array $options,
    array ...$replacements
): array {
    $replacements[] = $options;

    return \\array_replace(
        [
            'normalize' => true,
            'translit' => true,
            'ignore' => true,
            'encodings' => self::DEFAULT_ENCODINGS,
        ],
        ...$replacements
    );
}
```

## Naming Conventions

### Method Names

- Use descriptive verb-noun combinations
- Prefix with `transcode`, `detect`, `validate`, `configure`, etc.
- Private methods should describe implementation: `transcodeWithUConverter`
- Public methods should describe intent: `toUtf8`, `repair`, `detect`

### Variable Names

- Use descriptive names: `$sourceEncoding`, `$targetEncoding`
- Avoid abbreviations except for common ones: `$to`, `$from`
- Use `$data` for generic input, `$value` for single items

### Boolean Variables

- Prefix with `is`, `has`, `should`: `$isValid`, `$hasResult`

## Testing Standards

### Test Organization

- Place unit tests in `tests/phpunit/`
- Place benchmarks in `tests/benchmark/`
- Use PHPUnit ^9.5 || ^10.0

### Coverage Requirements

- Minimum 90% code coverage
- 100% type coverage (PHPStan level 8)

### Test Execution

```bash
# Run with coverage
XDEBUG_MODE=coverage ./vendor/bin/phpunit Tests/phpunit --process-isolation -c phpunit.xml.dist

# Run benchmarks
./vendor/bin/phpbench run Tests/benchmark/ --report=aggregate --retry-threshold=5
```

## Static Analysis

### PHPStan Configuration

- Level 8 (maximum strictness)
- PHPUnit extension enabled
- 100% type coverage required

### Psalm Configuration

- Strict error level
- Immutability checking enabled
- Use `@psalm-immutable` annotation for immutable classes

## Rector Rules

### Enabled Sets

- PHP 7.4 features
- Dead code removal
- Code quality improvements

### Skipped Rules

- `RemoveUselessParamTagRector` - Keep param tags for documentation
- `RemoveUselessReturnTagRector` - Keep return tags for documentation
- `RemoveUnusedConstructorParamRector` - May have false positives

### Type Coverage

- Level 0 (strictest) - All types must be declared

## Git Workflow

### Commit Messages

- Use conventional commits format
- Examples: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`

### Branch Strategy

- `main` branch for stable releases
- Feature branches: `feature/amazing-feature`
- Bug fix branches: `fix/issue-description`

## Documentation Standards

### README Structure

- Comprehensive feature documentation
- Code examples for all public methods
- Performance benchmarks
- Comparison with alternatives
- Use cases and real-world examples

### Inline Comments

- Explain WHY, not WHAT
- Document performance considerations
- Document edge cases and gotchas
- Example:

```php
// Loop while it looks like valid UTF-8
while ($iterations < $maxDepth && self::isValidUtf8($fixed)) {
    // Attempt to reverse convert (UTF-8 -> $from)
    $test = self::transcodeString($fixed, $from, self::ENCODING_UTF8, $options);

    if (null === $test || $test === $fixed || !self::isValidUtf8($test)) {
        break;
    }

    // If conversion worked AND result is still valid UTF-8 AND result is different
    $fixed = $test;
    $iterations++;
}
```

## Security Considerations

### Input Validation

- Whitelist allowed encodings to prevent injection
- Validate all user inputs early
- Use strict comparison (`===`) for security checks

### Safe Defaults

- Enable transliteration and ignore flags by default
- Use Windows-1252 instead of strict ISO-8859-1 (more characters)
- Normalize UTF-8 output by default

## Extensibility Guidelines

### Provider Registration

- Support both method names (string) and callables
- Validate providers before registration
- Support priority control (prepend/append)
- Example:

```php
public static function registerTranscoder(
    $transcoder,
    bool $prepend = true
): void {
    self::validateTranscoder($transcoder);

    if ($prepend) {
        \\array_unshift(self::$transcoders, $transcoder);
    } else {
        self::$transcoders[] = $transcoder;
    }
}
```

### Provider Interface

- Providers must return `null` to pass to next in chain
- Providers must match expected signature
- Document signature in PHPDoc
- Example:

```php
/**
 * @param string|callable $transcoder Method name or callable with signature:
 * fn(string, string, string, array): string|false
 */
```
