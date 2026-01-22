# CharsetProcessorInterface

## Overview

`CharsetProcessorInterface` defines the contract for charset processing services. It provides a comprehensive API for managing transcoders, detectors, encodings, and performing charset operations.

**Namespace:** `Ducks\Component\EncodingRepair`

## Interface Synopsis

```php
interface CharsetProcessorInterface
{
    // Transcoder Management
    public function registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self;
    public function unregisterTranscoder(TranscoderInterface $transcoder): self;
    public function queueTranscoders(TranscoderInterface ...$transcoders): self;
    public function resetTranscoders(): self;
    
    // Detector Management
    public function registerDetector(DetectorInterface $detector, ?int $priority = null): self;
    public function unregisterDetector(DetectorInterface $detector): self;
    public function queueDetectors(DetectorInterface ...$detectors): self;
    public function resetDetectors(): self;
    
    // Encoding Management
    public function addEncodings(string ...$encodings): self;
    public function removeEncodings(string ...$encodings): self;
    public function getEncodings(): array;
    public function resetEncodings(): self;
    
    // Core Operations
    public function toCharset($data, string $to, string $from, array $options = []);
    public function toUtf8($data, string $from, array $options = []);
    public function toIso($data, string $from, array $options = []);
    public function detect(string $string, array $options = []): string;
    public function repair($data, string $to, string $from, array $options = []);
    public function safeJsonEncode($data, int $flags, int $depth, string $from): string;
    public function safeJsonDecode(string $json, ?bool $assoc, int $depth, int $flags, string $to, string $from);
}
```

## Features

- **Fluent API**: All management methods return `self` for method chaining
- **Flexible Configuration**: Manage transcoders, detectors, and encodings dynamically
- **SOLID Principles**: Interface segregation and dependency inversion
- **Testability**: Easy to mock for unit testing

## Constants

```php
const AUTO = 'AUTO';
const WINDOWS_1252 = 'CP1252';
const ENCODING_ISO = 'ISO-8859-1';
const ENCODING_UTF8 = 'UTF-8';
const ENCODING_UTF16 = 'UTF-16';
const ENCODING_UTF32 = 'UTF-32';
const ENCODING_ASCII = 'ASCII';
```

## Methods

### Transcoder Management

#### registerTranscoder()

Register a transcoder with optional priority.

```php
public function registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self
```

**Returns:** `self` for method chaining

#### queueTranscoders()

Register multiple transcoders at once.

```php
public function queueTranscoders(TranscoderInterface ...$transcoders): self
```

**Returns:** `self` for method chaining

#### resetTranscoders()

Reset transcoders to default configuration.

```php
public function resetTranscoders(): self
```

**Returns:** `self` for method chaining

### Detector Management

#### registerDetector()

Register a detector with optional priority.

```php
public function registerDetector(DetectorInterface $detector, ?int $priority = null): self
```

**Returns:** `self` for method chaining

#### queueDetectors()

Register multiple detectors at once.

```php
public function queueDetectors(DetectorInterface ...$detectors): self
```

**Returns:** `self` for method chaining

#### resetDetectors()

Reset detectors to default configuration.

```php
public function resetDetectors(): self
```

**Returns:** `self` for method chaining

### Encoding Management

#### addEncodings()

Add allowed encodings.

```php
public function addEncodings(string ...$encodings): self
```

**Returns:** `self` for method chaining

#### removeEncodings()

Remove allowed encodings.

```php
public function removeEncodings(string ...$encodings): self
```

**Returns:** `self` for method chaining

#### getEncodings()

Get list of allowed encodings.

```php
public function getEncodings(): array
```

**Returns:** `list<string>` - Array of encoding names

#### resetEncodings()

Reset encodings to defaults.

```php
public function resetEncodings(): self
```

**Returns:** `self` for method chaining

## Example

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;

$processor = new CharsetProcessor();

// Fluent API
$processor
    ->addEncodings('SHIFT_JIS', 'EUC-JP')
    ->queueTranscoders(new IconvTranscoder())
    ->resetDetectors();

// Use the processor
$utf8 = $processor->toUtf8($data);
```

## Implementations

- [CharsetProcessor](CharsetProcessor.md) - Default implementation

## See Also

- [CharsetProcessor](CharsetProcessor.md)
- [CharsetHelper](CharsetHelper.md)
- [TranscoderInterface](TranscoderInterface.md)
- [DetectorInterface](DetectorInterface.md)
