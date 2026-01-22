# CharsetProcessor

Service implementation for charset processing operations with fluent API.

## Namespace

```php
Ducks\Component\EncodingRepair\CharsetProcessor
```

## Description

`CharsetProcessor` is the main service class that implements `CharsetProcessorInterface`. It provides a fluent API for managing transcoders, detectors, and encodings, as well as performing charset operations.

Unlike the static `CharsetHelper` facade, `CharsetProcessor` is instantiable and allows multiple independent instances with different configurations.

## Class Declaration

```php
final class CharsetProcessor implements CharsetProcessorInterface
```

## Features

- **Fluent API**: All management methods return `$this` for method chaining
- **Mutable State**: Each instance maintains its own configuration
- **Multiple Instances**: Create different processors for different contexts
- **Dependency Injection**: Implements interface for easy testing and DI
- **SOLID Principles**: Single Responsibility, Open/Closed, Dependency Inversion

## Constructor

```php
public function __construct()
```

Creates a new processor instance with default transcoders and detectors.

## Transcoder Management

### registerTranscoder

```php
public function registerTranscoder(
    TranscoderInterface|callable $transcoder,
    ?int $priority = null
): self
```

Register a transcoder with optional priority.

**Parameters:**
- `$transcoder`: TranscoderInterface instance or callable
- `$priority`: Priority override (null = use transcoder's default)

**Returns:** `$this` for method chaining

### unregisterTranscoder

```php
public function unregisterTranscoder(TranscoderInterface $transcoder): self
```

Remove a specific transcoder from the chain.

**Returns:** `$this` for method chaining

### queueTranscoders

```php
public function queueTranscoders(TranscoderInterface ...$transcoders): self
```

Add multiple transcoders at once.

**Returns:** `$this` for method chaining

### resetTranscoders

```php
public function resetTranscoders(): self
```

Remove all transcoders and reinitialize with defaults.

**Returns:** `$this` for method chaining

## Detector Management

### registerDetector

```php
public function registerDetector(
    DetectorInterface|callable $detector,
    ?int $priority = null
): self
```

Register a detector with optional priority.

**Returns:** `$this` for method chaining

### unregisterDetector

```php
public function unregisterDetector(DetectorInterface $detector): self
```

Remove a specific detector from the chain.

**Returns:** `$this` for method chaining

### queueDetectors

```php
public function queueDetectors(DetectorInterface ...$detectors): self
```

Add multiple detectors at once.

**Returns:** `$this` for method chaining

### resetDetectors

```php
public function resetDetectors(): self
```

Remove all detectors and reinitialize with defaults.

**Returns:** `$this` for method chaining

## Encoding Management

### addEncodings

```php
public function addEncodings(string ...$encodings): self
```

Add encodings to the detection list.

**Returns:** `$this` for method chaining

### removeEncodings

```php
public function removeEncodings(string ...$encodings): self
```

Remove encodings from the detection list.

**Returns:** `$this` for method chaining

### getEncodings

```php
public function getEncodings(): array
```

Get current encoding list.

**Returns:** Array of encoding names

### resetEncodings

```php
public function resetEncodings(): self
```

Reset to default encoding list.

**Returns:** `$this` for method chaining

## Charset Operations

### toUtf8

```php
public function toUtf8(
    mixed $data,
    string $from = self::WINDOWS_1252,
    array $options = []
): mixed
```

Convert data to UTF-8.

### toIso

```php
public function toIso(
    mixed $data,
    string $from = self::ENCODING_UTF8,
    array $options = []
): mixed
```

Convert data to ISO-8859-1.

### toCharset

```php
public function toCharset(
    mixed $data,
    string $to,
    string $from,
    array $options = []
): mixed
```

Convert data to any charset.

### detect

```php
public function detect(string $string, array $options = []): string
```

Detect encoding of a string.

### repair

```php
public function repair(
    mixed $data,
    string $to = self::ENCODING_UTF8,
    string $from = self::ENCODING_ISO,
    array $options = []
): mixed
```

Repair double-encoded data.

### safeJsonEncode

```php
public function safeJsonEncode(
    mixed $data,
    int $flags = 0,
    int $depth = 512
): string
```

Safely encode to JSON with charset handling.

### safeJsonDecode

```php
public function safeJsonDecode(
    string $json,
    ?bool $associative = null,
    int $depth = 512,
    int $flags = 0,
    string $to = self::ENCODING_UTF8,
    string $from = self::WINDOWS_1252
): mixed
```

Safely decode JSON with charset conversion.

## Usage Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();
$utf8 = $processor->toUtf8($data);
```

### Fluent API

```php
$processor = new CharsetProcessor();
$processor
    ->addEncodings('SHIFT_JIS', 'EUC-JP')
    ->resetDetectors()
    ->registerTranscoder(new MyTranscoder(), 150);

$result = $processor->toUtf8($data);
```

### Multiple Instances

```php
// Production processor
$prodProcessor = new CharsetProcessor();
$prodProcessor->resetEncodings()->addEncodings('UTF-8', 'ISO-8859-1');

// Import processor
$importProcessor = new CharsetProcessor();
$importProcessor->addEncodings('SHIFT_JIS', 'GB2312');

// Independent configurations
$prodResult = $prodProcessor->toUtf8($data);
$importResult = $importProcessor->toUtf8($legacyData);
```

### Dependency Injection

```php
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

class MyService
{
    private CharsetProcessorInterface $processor;
    
    public function __construct(CharsetProcessorInterface $processor)
    {
        $this->processor = $processor;
    }
    
    public function process($data)
    {
        return $this->processor->toUtf8($data);
    }
}
```

## See Also

- [CharsetProcessorInterface](CharsetProcessorInterface.md) - Service contract
- [CharsetHelper](CharsetHelper.md) - Static facade
- [Advanced Usage](../guide/advanced-usage.md) - More examples
