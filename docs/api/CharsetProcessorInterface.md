# CharsetProcessorInterface

Service contract for charset processing operations.

## Namespace

```php
Ducks\Component\EncodingRepair\CharsetProcessorInterface
```

## Description

`CharsetProcessorInterface` defines the contract for charset processing services. It provides methods for managing transcoders, detectors, encodings, and performing charset operations.

This interface enables dependency injection, testing with mocks, and multiple implementations.

## Interface Declaration

```php
interface CharsetProcessorInterface
```

## Constants

```php
public const AUTO = 'AUTO';
public const ENCODING_UTF8 = 'UTF-8';
public const ENCODING_UTF16 = 'UTF-16';
public const ENCODING_UTF32 = 'UTF-32';
public const ENCODING_ISO = 'ISO-8859-1';
public const WINDOWS_1252 = 'CP1252';
public const ASCII = 'ASCII';
```

## Methods

### Transcoder Management

```php
public function registerTranscoder(
    TranscoderInterface|callable $transcoder,
    ?int $priority = null
): self;

public function unregisterTranscoder(TranscoderInterface $transcoder): self;

public function queueTranscoders(TranscoderInterface ...$transcoders): self;

public function resetTranscoders(): self;
```

### Detector Management

```php
public function registerDetector(
    DetectorInterface|callable $detector,
    ?int $priority = null
): self;

public function unregisterDetector(DetectorInterface $detector): self;

public function queueDetectors(DetectorInterface ...$detectors): self;

public function resetDetectors(): self;
```

### Encoding Management

```php
public function addEncodings(string ...$encodings): self;

public function removeEncodings(string ...$encodings): self;

public function getEncodings(): array;

public function resetEncodings(): self;
```

### Charset Operations

```php
public function toUtf8(
    mixed $data,
    string $from = self::WINDOWS_1252,
    array $options = []
): mixed;

public function toIso(
    mixed $data,
    string $from = self::ENCODING_UTF8,
    array $options = []
): mixed;

public function toCharset(
    mixed $data,
    string $to,
    string $from,
    array $options = []
): mixed;

public function detect(string $string, array $options = []): string;

public function repair(
    mixed $data,
    string $to = self::ENCODING_UTF8,
    string $from = self::ENCODING_ISO,
    array $options = []
): mixed;

public function safeJsonEncode(
    mixed $data,
    int $flags = 0,
    int $depth = 512
): string;

public function safeJsonDecode(
    string $json,
    ?bool $associative = null,
    int $depth = 512,
    int $flags = 0,
    string $to = self::ENCODING_UTF8,
    string $from = self::WINDOWS_1252
): mixed;
```

## Design Principles

### Single Responsibility Principle

Each method has a single, well-defined purpose:
- Management methods modify configuration
- Operation methods perform charset conversions

### Open/Closed Principle

The interface is open for extension (custom implementations) but closed for modification (stable contract).

### Liskov Substitution Principle

Any implementation can be substituted without breaking client code.

### Interface Segregation Principle

The interface provides cohesive methods without forcing implementations to depend on unused functionality.

### Dependency Inversion Principle

Clients depend on the interface abstraction, not concrete implementations.

## Usage Examples

### Dependency Injection

```php
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

class DataImporter
{
    private CharsetProcessorInterface $processor;
    
    public function __construct(CharsetProcessorInterface $processor)
    {
        $this->processor = $processor;
    }
    
    public function import(string $file): array
    {
        $data = file_get_contents($file);
        return $this->processor->toUtf8($data);
    }
}
```

### Testing with Mocks

```php
use PHPUnit\Framework\TestCase;
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

class DataImporterTest extends TestCase
{
    public function testImport(): void
    {
        $mock = $this->createMock(CharsetProcessorInterface::class);
        $mock->expects($this->once())
            ->method('toUtf8')
            ->willReturn(['name' => 'Test']);
        
        $importer = new DataImporter($mock);
        $result = $importer->import('test.csv');
        
        $this->assertSame(['name' => 'Test'], $result);
    }
}
```

### Custom Implementation

```php
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

class CachingCharsetProcessor implements CharsetProcessorInterface
{
    private CharsetProcessorInterface $inner;
    private array $cache = [];
    
    public function __construct(CharsetProcessorInterface $inner)
    {
        $this->inner = $inner;
    }
    
    public function toUtf8(mixed $data, string $from = self::WINDOWS_1252, array $options = []): mixed
    {
        $key = md5(serialize($data) . $from);
        
        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->inner->toUtf8($data, $from, $options);
        }
        
        return $this->cache[$key];
    }
    
    // ... implement other methods
}
```

## Implementations

- [CharsetProcessor](CharsetProcessor.md) - Default implementation with fluent API

## See Also

- [CharsetProcessor](CharsetProcessor.md) - Service implementation
- [CharsetHelper](CharsetHelper.md) - Static facade
- [Service Architecture](../guide/service-architecture.md) - Architecture documentation
