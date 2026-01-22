# CharsetProcessor

## Overview

`CharsetProcessor` is the default implementation of [CharsetProcessorInterface](CharsetProcessorInterface.md). It provides a complete charset processing service with fluent API, mutable configuration, and support for multiple independent instances.

**Namespace:** `Ducks\Component\EncodingRepair`

## Class Synopsis

```php
final class CharsetProcessor implements CharsetProcessorInterface
{
    public function __construct();
    
    // Fluent API - all return $this
    public function registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self;
    public function queueTranscoders(TranscoderInterface ...$transcoders): self;
    public function resetTranscoders(): self;
    
    public function registerDetector(DetectorInterface $detector, ?int $priority = null): self;
    public function queueDetectors(DetectorInterface ...$detectors): self;
    public function resetDetectors(): self;
    
    public function addEncodings(string ...$encodings): self;
    public function removeEncodings(string ...$encodings): self;
    public function getEncodings(): array;
    public function resetEncodings(): self;
    
    // Core operations
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

- **Fluent API**: Method chaining for configuration
- **Mutable State**: Dynamic configuration of transcoders, detectors, encodings
- **Independent Instances**: Multiple processors with different configurations
- **Default Configuration**: Pre-configured with UConverter, Iconv, MbString transcoders

## Constructor

```php
public function __construct()
```

Creates a new processor with default configuration:
- Transcoders: UConverter (100), Iconv (50), MbString (10)
- Detectors: MbStringDetector (100), FileInfoDetector (50)
- Encodings: UTF-8, Windows-1252, ISO-8859-1, ASCII, UTF-16, UTF-32, AUTO

## Usage Examples

### Basic Usage

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();
$utf8 = $processor->toUtf8($data);
```

### Fluent Configuration

```php
$processor = new CharsetProcessor();
$processor
    ->addEncodings('SHIFT_JIS', 'EUC-JP')
    ->removeEncodings('UTF-16', 'UTF-32')
    ->resetTranscoders()
    ->queueTranscoders(new MyCustomTranscoder());

$result = $processor->toCharset($data, 'UTF-8', 'SHIFT_JIS');
```

### Multiple Instances

```php
// Production processor
$prod = new CharsetProcessor();
$prod->addEncodings('SHIFT_JIS', 'EUC-JP');

// Test processor
$test = new CharsetProcessor();
$test->resetEncodings()->addEncodings('UTF-8', 'ISO-8859-1');

// Both are independent
$prodResult = $prod->toUtf8($data);
$testResult = $test->toUtf8($data);
```

### Custom Transcoders

```php
$processor = new CharsetProcessor();
$processor->queueTranscoders(
    new MyTranscoder1(),
    new MyTranscoder2(),
    new MyTranscoder3()
);
```

## Method Details

### Configuration Methods

All configuration methods return `$this` for chaining:

```php
$processor
    ->registerTranscoder($transcoder, 150)
    ->registerDetector($detector, 200)
    ->addEncodings('CUSTOM-ENCODING')
    ->removeEncodings('UTF-32');
```

### Core Operations

Same as [CharsetHelper](CharsetHelper.md) but instance-based:

```php
// Conversion
$utf8 = $processor->toUtf8($data, 'ISO-8859-1');
$iso = $processor->toIso($data, 'UTF-8');
$custom = $processor->toCharset($data, 'SHIFT_JIS', 'UTF-8');

// Detection
$encoding = $processor->detect($string);

// Repair
$fixed = $processor->repair($data);

// Safe JSON
$json = $processor->safeJsonEncode($data);
$decoded = $processor->safeJsonDecode($json);
```

## Benefits

### Testability

Easy to mock for unit tests:

```php
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

// In tests
$mock = $this->createMock(CharsetProcessorInterface::class);
$service = new MyService($mock);
```

### Flexibility

Different configurations for different contexts:

```php
// API processor - strict encodings
$apiProcessor = new CharsetProcessor();
$apiProcessor->resetEncodings()->addEncodings('UTF-8');

// Import processor - permissive
$importProcessor = new CharsetProcessor();
$importProcessor->addEncodings('SHIFT_JIS', 'EUC-JP', 'GB2312');
```

## Performance

- Lazy initialization of chains
- Efficient priority queue management
- No overhead compared to static CharsetHelper

## See Also

- [CharsetProcessorInterface](CharsetProcessorInterface.md)
- [CharsetHelper](CharsetHelper.md)
- [TranscoderChain](TranscoderChain.md)
- [DetectorChain](DetectorChain.md)
