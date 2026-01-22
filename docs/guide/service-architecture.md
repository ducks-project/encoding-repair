# Service Architecture

## Overview

CharsetHelper v1.1 introduces a service-based architecture following SOLID principles. The new architecture provides better testability, flexibility, and maintainability while maintaining 100% backward compatibility.

## Architecture Components

### CharsetProcessorInterface

Service contract defining the API for charset processing operations.

**Benefits:**
- Enables dependency injection
- Facilitates testing with mocks
- Allows custom implementations
- Provides stable contract

### CharsetProcessor

Service implementation with fluent API and mutable state.

**Features:**
- Instantiable service class
- Fluent API (all management methods return `$this`)
- Multiple independent instances
- Configurable transcoders, detectors, and encodings

### CharsetHelper

Static facade delegating to CharsetProcessor.

**Purpose:**
- Maintains backward compatibility
- Provides convenient static API
- Lazy initialization of processor

## Design Principles

### Single Responsibility Principle (SRP)

Each class has a single, well-defined responsibility:
- `CharsetProcessorInterface`: Defines the contract
- `CharsetProcessor`: Implements charset operations
- `CharsetHelper`: Provides static facade

### Open/Closed Principle (OCP)

The architecture is open for extension but closed for modification:
- Custom transcoders via `TranscoderInterface`
- Custom detectors via `DetectorInterface`
- Custom implementations via `CharsetProcessorInterface`

### Liskov Substitution Principle (LSP)

Any `CharsetProcessorInterface` implementation can be substituted without breaking client code.

### Interface Segregation Principle (ISP)

The interface provides cohesive methods without forcing implementations to depend on unused functionality.

### Dependency Inversion Principle (DIP)

High-level modules depend on abstractions (`CharsetProcessorInterface`), not concrete implementations.

## Usage Patterns

### Static Facade (Backward Compatible)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Works exactly as before
$utf8 = CharsetHelper::toUtf8($data);
```

### Service Instance

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
// Production processor with strict encodings
$prodProcessor = new CharsetProcessor();
$prodProcessor->resetEncodings()->addEncodings('UTF-8', 'ISO-8859-1');

// Import processor with permissive encodings
$importProcessor = new CharsetProcessor();
$importProcessor->addEncodings('SHIFT_JIS', 'EUC-JP', 'GB2312');

// Both are independent
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

// Easy to test with mocks
$mock = $this->createMock(CharsetProcessorInterface::class);
$service = new MyService($mock);
```

## Migration Guide

### From Static to Service

**Before (v1.0):**
```php
CharsetHelper::registerTranscoder(new MyTranscoder());
$result = CharsetHelper::toUtf8($data);
```

**After (v1.1):**
```php
$processor = new CharsetProcessor();
$processor->registerTranscoder(new MyTranscoder());
$result = $processor->toUtf8($data);
```

### No Migration Required

The static facade still works:
```php
// This still works in v1.1
CharsetHelper::toUtf8($data);
```

## Benefits

### Testability

```php
// Easy to mock in tests
$mock = $this->createMock(CharsetProcessorInterface::class);
$mock->method('toUtf8')->willReturn('mocked');

$service = new MyService($mock);
$result = $service->process($data);
```

### Flexibility

```php
// Different configurations for different contexts
$webProcessor = new CharsetProcessor();
$webProcessor->addEncodings('UTF-8', 'ISO-8859-1');

$apiProcessor = new CharsetProcessor();
$apiProcessor->addEncodings('UTF-8', 'UTF-16');
```

### Maintainability

- Clear separation of concerns
- Single Responsibility Principle
- Easy to extend without modifying existing code

## Performance

No performance impact:
- Static facade uses lazy initialization
- Service instances have minimal overhead
- Same underlying implementation

## Backward Compatibility

100% backward compatible:
- All existing code works unchanged
- Static methods delegate to service
- No breaking changes

## See Also

- [CharsetProcessorInterface](../api/CharsetProcessorInterface.md) - Service contract
- [CharsetProcessor](../api/CharsetProcessor.md) - Service implementation
- [CharsetHelper](../api/CharsetHelper.md) - Static facade
- [Advanced Usage](advanced-usage.md) - More examples
