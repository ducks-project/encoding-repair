# About Middleware Pattern

## Why Middleware Pattern is NOT Used

This document explains the architectural decision to **not implement** the Middleware pattern in EncodingRepair, despite its popularity in modern PHP frameworks.

## What is Middleware Pattern?

The Middleware pattern allows chaining transformations where each middleware can:

```php
interface TransformationMiddleware
{
    public function process(string $data, callable $next): string;
}

// Example usage
$result = $middleware1->process($data, function($data) use ($middleware2) {
    return $middleware2->process($data, function($data) {
        return $data; // Final result
    });
});
```

## Why It's Not Pertinent Here

### 1. Chain of Responsibility Already Implemented

EncodingRepair uses **Chain of Responsibility** pattern for Transcoders and Detectors, which is conceptually similar but better suited for **fallback strategies**:

```php
// Current architecture (optimal)
foreach ($transcoders as $transcoder) {
    $result = $transcoder->transcode($data, $to, $from, $options);
    if (null !== $result) {
        return $result; // First success wins
    }
}

// Middleware would be confusing
$result = $middleware->process($data, $next); // Who does what?
```

**Key difference:**

- **Chain of Responsibility**: Try until one succeeds (fallback)
- **Middleware**: All execute in sequence (transformation pipeline)

### 2. Single Responsibility Principle

Current architecture maintains clear separation:

- **Transcoder**: Encoding conversion only
- **Detector**: Encoding detection only
- **CharsetProcessor**: Orchestration only

Adding middleware would blur these boundaries:

```php
// Bad: Mixed responsibilities
class NormalizationMiddleware implements TransformationMiddleware
{
    public function process(string $data, callable $next): string
    {
        $normalized = Normalizer::normalize($data); // Transformation
        return $next($normalized); // Delegation
    }
}
```

### 3. Options Array Already Handles Transformations

The `$options` parameter already provides flexible configuration without architectural complexity:

```php
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1', [
    'normalize' => true,   // Unicode normalization
    'translit' => true,    // Transliteration
    'ignore' => true,      // Ignore invalid sequences
]);
```

### 4. Performance Impact

Middleware adds overhead through nested closures:

```php
// Current: Direct call (45ms/10k ops)
$result = $transcoder->transcode($data, $to, $from, $options);

// Middleware: Closure overhead (~15-20% slower)
$result = $middleware->process($data, fn($d) => $next->process($d, ...));
```

### 5. Complexity Without Benefit

Middleware would require:

- New interface (`TransformationMiddleware`)
- Middleware stack management
- Closure-based delegation
- Additional testing complexity

**For what gain?** Transformations are already configurable via options.

## The Better Alternative: Extend Options

If you need additional transformations, extend the `$options` array:

### Current Options

```php
// In CharsetProcessor::configureOptions()
private function configureOptions(array $options, array ...$replacements): array
{
    return array_replace(
        [
            'normalize' => true,  // Unicode NFC normalization
            'translit' => true,   // Transliterate unmappable chars
            'ignore' => true,     // Skip invalid sequences
            'encodings' => [...], // Detection candidate list
        ],
        ...$replacements,
        $options
    );
}
```

### Adding New Options

To add new transformations (e.g., sanitization, validation):

#### Step 1: Add Option to Defaults

```php
private function configureOptions(array $options, array ...$replacements): array
{
    return array_replace(
        [
            'normalize' => true,
            'translit' => true,
            'ignore' => true,
            'sanitize' => false,  // NEW: Strip control characters
            'validate' => false,  // NEW: Throw on invalid encoding
            'encodings' => self::DEFAULT_ENCODINGS,
        ],
        ...$replacements,
        $options
    );
}
```

#### Step 2: Implement in Conversion Logic

```php
private function convertString(string $data, string $to, string $from, array $options): string
{
    $result = $this->transcodeString($data, $to, $from, $options) ?? $data;
    
    // Apply sanitization if enabled
    if (true === ($options['sanitize'] ?? false)) {
        $result = $this->sanitize($result);
    }
    
    // Validate if enabled
    if (true === ($options['validate'] ?? false)) {
        $this->validate($result, $to);
    }
    
    return $result;
}

private function sanitize(string $data): string
{
    // Remove control characters except \n, \r, \t
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data);
}

private function validate(string $data, string $encoding): void
{
    if (!mb_check_encoding($data, $encoding)) {
        throw new InvalidArgumentException(
            sprintf('Invalid %s encoding after conversion', $encoding)
        );
    }
}
```

#### Step 3: Use the New Option

```php
// Sanitize control characters
$clean = CharsetHelper::toUtf8($data, 'ISO-8859-1', [
    'sanitize' => true,
]);

// Strict validation
$validated = CharsetHelper::toUtf8($data, 'ISO-8859-1', [
    'validate' => true,
]);
```

## Comparison Table

| Aspect | Middleware Pattern | Options Array (Current) |
|--------|-------------------|------------------------|
| **Complexity** | High (interfaces, stack, closures) | Low (array merge) |
| **Performance** | Slower (closure overhead) | Fast (direct calls) |
| **Extensibility** | New middleware classes | New array keys |
| **Testability** | Complex (mock stack) | Simple (pass options) |
| **Readability** | Obscure (nested closures) | Clear (explicit options) |
| **SOLID** | Violates SRP | Maintains SRP |
| **Fit** | Pipeline transformations | Fallback strategies ✅ |

## Conclusion

The Middleware pattern is excellent for HTTP request/response pipelines (PSR-15) but **not suitable** for encoding conversion where:

1. **Fallback strategies** are needed (Chain of Responsibility)
2. **Single responsibility** must be maintained
3. **Performance** is critical (10k+ ops/sec)
4. **Simplicity** is valued (zero dependencies)

The **options array approach** provides all the flexibility of middleware without the architectural complexity.

## Further Reading

- [Chain of Responsibility Pattern](https://refactoring.guru/design-patterns/chain-of-responsibility)
- [PSR-15: HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/) (where middleware shines)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [CharsetProcessor Source Code](https://github.com/ducks-project/encoding-repair/blob/main/CharsetProcessor.php)
