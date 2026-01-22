# Service Architecture Implementation

## Overview

The codebase has been refactored to follow SOLID principles with a service-based architecture.

## New Structure

```text
CharsetProcessorInterface.php  # Service contract
CharsetProcessor.php           # Service implementation
CharsetHelper.php              # Static facade (backward compatible)
```

## Key Components

### 1. CharsetProcessorInterface

Defines the contract for charset processing with:

- Transcoder management (register, unregister, queue, reset)
- Detector management (register, unregister, queue, reset)
- Encoding management (add, remove, get, reset)
- Core operations (toCharset, toUtf8, toIso, detect, repair, safeJson*)

### 2. CharsetProcessor

Implements the interface with:

- Fluent API (all methods return `$this`)
- Mutable state (transcoders, detectors, encodings)
- All business logic from CharsetHelper

### 3. CharsetHelper (Refactored)

Now a simple static facade that:

- Delegates all calls to CharsetProcessor
- Maintains backward compatibility
- Lazy initializes the processor

## Benefits

### SOLID Principles

✅ **Single Responsibility**: Each class has one clear purpose
✅ **Open/Closed**: Extensible via interface, closed for modification
✅ **Liskov Substitution**: Interface can be implemented differently
✅ **Interface Segregation**: Clean, focused interface
✅ **Dependency Inversion**: Depends on interface, not implementation

### Additional Benefits

✅ **Testability**: Service is easily mockable
✅ **Flexibility**: Multiple instances with different configurations
✅ **Fluent API**: Chainable method calls
✅ **Backward Compatibility**: Existing code works unchanged

## Usage Examples

### Static Facade (Backward Compatible)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

$utf8 = CharsetHelper::toUtf8($data);
```

### Service Instance (New Way)

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();
$processor
    ->addEncodings('SHIFT_JIS')
    ->queueTranscoders(new MyTranscoder())
    ->resetDetectors();

$utf8 = $processor->toUtf8($data);
```

### Multiple Configurations

```php
// Production processor
$prodProcessor = new CharsetProcessor();
$prodProcessor->addEncodings('SHIFT_JIS', 'EUC-JP');

// Test processor with minimal encodings
$testProcessor = new CharsetProcessor();
$testProcessor->resetEncodings()->addEncodings('UTF-8', 'ISO-8859-1');
```

## API Reference

### Transcoder Management

```php
$processor->registerTranscoder(TranscoderInterface $transcoder, ?int $priority = null): self
$processor->unregisterTranscoder(TranscoderInterface $transcoder): self
$processor->queueTranscoders(TranscoderInterface ...$transcoders): self
$processor->resetTranscoders(): self
```

### Detector Management

```php
$processor->registerDetector(DetectorInterface $detector, ?int $priority = null): self
$processor->unregisterDetector(DetectorInterface $detector): self
$processor->queueDetectors(DetectorInterface ...$detectors): self
$processor->resetDetectors(): self
```

### Encoding Management

```php
$processor->addEncodings(string ...$encodings): self
$processor->removeEncodings(string ...$encodings): self
$processor->getEncodings(): array
$processor->resetEncodings(): self
```

### Core Operations

```php
$processor->toCharset($data, string $to, string $from, array $options = [])
$processor->toUtf8($data, string $from, array $options = [])
$processor->toIso($data, string $from, array $options = [])
$processor->detect(string $string, array $options = []): string
$processor->repair($data, string $to, string $from, array $options = [])
$processor->safeJsonEncode($data, int $flags, int $depth, string $from): string
$processor->safeJsonDecode(string $json, ?bool $assoc, int $depth, int $flags, string $to, string $from)
```

## Migration Guide

### No Changes Required

Existing code using `CharsetHelper` continues to work:

```php
// This still works
CharsetHelper::toUtf8($data);
CharsetHelper::registerTranscoder($transcoder);
```

### Optional: Migrate to Service

For better testability and flexibility:

```php
// Before
CharsetHelper::toUtf8($data);

// After
$processor = new CharsetProcessor();
$processor->toUtf8($data);
```

## Testing

All existing tests pass without modification:

- ✅ 85 tests, 120 assertions
- ✅ PHPStan level 8
- ✅ 100% backward compatibility
