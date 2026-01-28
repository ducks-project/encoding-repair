# Advanced Usage

Advanced features and extensibility options for CharsetHelper.

## Service-Based Architecture

### Using CharsetProcessor Service

For better testability and flexibility, use the `CharsetProcessor` service directly:

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

// Create a processor instance
$processor = new CharsetProcessor();

// Fluent API for configuration
$processor
    ->addEncodings('SHIFT_JIS', 'EUC-JP')
    ->queueTranscoders(new MyCustomTranscoder())
    ->resetDetectors();

// Use the processor
$utf8 = $processor->toUtf8($data);
```

### Multiple Processor Instances

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

// Easy to mock in tests
$mock = $this->createMock(CharsetProcessorInterface::class);
$service = new MyService($mock);
```

## Custom Type Interpreters

### Custom Property Mappers

Optimize object processing by converting only specific properties:

```php
use Ducks\Component\EncodingRepair\Interpreter\PropertyMapperInterface;

class UserMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->name = $transcoder($object->name);
        $copy->email = $transcoder($object->email);
        // password is NOT transcoded (security)
        // avatar_binary is NOT transcoded (performance)
        return $copy;
    }
}

$processor = new CharsetProcessor();
$processor->registerPropertyMapper(User::class, new UserMapper());

$user = new User();
$user->name = 'José';
$user->password = 'secret123';  // Will NOT be converted
$utf8User = $processor->toUtf8($user);

// Performance: 60% faster for objects with 50+ properties
```

### Custom Type Interpreters

Add support for custom data types:

```php
use Ducks\Component\EncodingRepair\Interpreter\TypeInterpreterInterface;

class ResourceInterpreter implements TypeInterpreterInterface
{
    public function supports($data): bool
    {
        return \is_resource($data);
    }

    public function interpret($data, callable $transcoder, array $options)
    {
        $content = \stream_get_contents($data);
        $converted = $transcoder($content);

        $newResource = \fopen('php://memory', 'r+');
        \fwrite($newResource, $converted);
        \rewind($newResource);

        return $newResource;
    }

    public function getPriority(): int
    {
        return 80;
    }
}

$processor->registerInterpreter(new ResourceInterpreter(), 80);

$resource = fopen('data.txt', 'r');
$convertedResource = $processor->toUtf8($resource);
```

## Custom Cleaners

### Registering Custom Cleaners

Register custom string cleaners with flexible execution strategies:

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;

class CustomCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Custom cleaning logic
        return preg_replace('/[^\x20-\x7E]/', '', $data);
    }

    public function getPriority(): int
    {
        return 75;
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

$processor = new CharsetProcessor();
$processor->registerCleaner(new CustomCleaner());

// Use clean option to enable cleaners
$result = $processor->toUtf8($data, 'ISO-8859-1', ['clean' => true]);
```

### Cleaner Execution Strategies

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\TaggedStrategy;

// Pipeline (default): Apply all cleaners successively
$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner());
$chain->register(new HtmlEntityCleaner());
// Both cleaners are applied

// First Match: Stop at first success (performance)
$chain = new CleanerChain(new FirstMatchStrategy());
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());
// Only MbScrubCleaner is executed

// Tagged: Selective execution
$chain = new CleanerChain(new TaggedStrategy(['bom', 'html']));
$chain->register(new BomCleaner(), null, ['bom']);
$chain->register(new HtmlEntityCleaner(), null, ['html']);
$chain->register(new WhitespaceCleaner(), null, ['whitespace']); // Ignored
// Only BOM and HTML cleaners are executed
```

### Built-in Cleaners

Some cleaners are available but not registered by default:

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\Utf8FixerCleaner;
use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;

$processor = new CharsetProcessor();

// Add BOM removal
$processor->registerCleaner(new BomCleaner());

// Add UTF-8 corruption repair
$processor->registerCleaner(new Utf8FixerCleaner());

// Add whitespace normalization
$processor->registerCleaner(new WhitespaceCleaner());

// Use with clean option
$result = $processor->toUtf8($data, 'ISO-8859-1', ['clean' => true]);
```

**Default cleaners:**

- **MbScrubCleaner** (priority: 100) - Uses mb_scrub() for best quality
- **PregMatchCleaner** (priority: 50) - Fastest (~0.9μs), removes control characters
- **IconvCleaner** (priority: 10) - Universal fallback with //IGNORE

**Additional cleaners available:**

- **BomCleaner** (priority: 150) - Removes BOM (Byte Order Mark)
- **NormalizerCleaner** (priority: 90) - Normalizes Unicode characters (NFC)
- **Utf8FixerCleaner** (priority: 80) - Repairs light UTF-8 corruption
- **HtmlEntityCleaner** (priority: 60) - Decodes HTML entities
- **WhitespaceCleaner** (priority: 40) - Normalizes whitespace
- **TransliterationCleaner** (priority: 30) - Transliterates to ASCII

**Note:** Cleaners are disabled by default (`clean: false`), but enabled in `repair()` method.

## Custom Transcoders

### Registering Custom Transcoders

Extend CharsetHelper with your own conversion strategies:

```php
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class MyCustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        // Return null to try next transcoder in chain
        return null;
    }

    public function getPriority(): int
    {
        return 75; // Between iconv (50) and UConverter (100)
    }

    public function isAvailable(): bool
    {
        return extension_loaded('my_extension');
    }
}

// Register with default priority
CharsetHelper::registerTranscoder(new MyCustomTranscoder());

// Register with custom priority
CharsetHelper::registerTranscoder(new MyCustomTranscoder(), 150);

// Legacy: Register a callable
CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, array $options): ?string {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null;
    },
    150  // Priority
);
```

### Transcoder Priorities

**Default transcoder priorities:**

1. **UConverter** (priority: 100, requires `ext-intl`): Best precision, supports many encodings
2. **iconv** (priority: 50): Good performance, supports transliteration
3. **mbstring** (priority: 10): Universal fallback, most permissive

**Custom transcoders** can be registered with any priority value. Higher values execute first.

## Custom Detectors

### Registering Custom Detectors

Add custom encoding detection methods:

```php
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;

class MyCustomDetector implements DetectorInterface
{
    public function detect(string $string, array $options): ?string
    {
        // Check for UTF-16LE BOM
        if (strlen($string) >= 2 && ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
            return 'UTF-16LE';
        }
        // Return null to try next detector
        return null;
    }

    public function getPriority(): int
    {
        return 150; // Higher than MbStringDetector (100)
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

// Register with default priority
CharsetHelper::registerDetector(new MyCustomDetector());

// Register with custom priority
CharsetHelper::registerDetector(new MyCustomDetector(), 200);

// Legacy: Register a callable
CharsetHelper::registerDetector(
    function (string $string, array $options): ?string {
        if (strlen($string) >= 2 && ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
            return 'UTF-16LE';
        }
        return null;
    },
    200  // Priority
);
```

### Detector Priorities

**Default detector priorities:**

1. **BomDetector** (priority: 160): BOM detection with 100% accuracy
2. **PregMatchDetector** (priority: 150): Fast ASCII/UTF-8 detection (~70% faster)
3. **MbStringDetector** (priority: 100, requires `ext-mbstring`): Fast and reliable using mb_detect_encoding
4. **FileInfoDetector** (priority: 50, requires `ext-fileinfo`): Fallback using finfo class

**Note:** `CachedDetector` is not included by default. Users can add it manually if needed.

**Custom detectors** can be registered with any priority value. Higher values execute first.

## Cache Support

### PSR-16 Cache Integration

CachedDetector supports PSR-16 cache for persistent detection results:

```php
// Option 1: Cache entire detector chain (recommended)
$processor = new CharsetProcessor();
$processor->enableDetectionCache(); // Uses InternalArrayCache

// Option 2: Cache specific detector (fine-grained control)
$fileInfo = new FileInfoDetector();
$cached = new CachedDetector($fileInfo);
$processor->registerDetector($cached);

// External cache (Redis, Memcached, APCu)
// $redis = new \Symfony\Component\Cache\Psr16Cache($redisAdapter);
// $processor->enableDetectionCache($redis, 7200);
```

### Custom Cache Implementation

```php
use Psr\SimpleCache\CacheInterface;

class RedisCacheAdapter implements CacheInterface
{
    private $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    public function get($key, $default = null)
    {
        $value = $this->redis->get($key);
        return $value !== false ? $value : $default;
    }

    public function set($key, $value, $ttl = null)
    {
        if (null === $ttl) {
            return $this->redis->set($key, $value);
        }
        return $this->redis->setex($key, $ttl, $value);
    }

    // Implement other CacheInterface methods...
}

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$cache = new RedisCacheAdapter($redis);

$processor = new CharsetProcessor();
$processor->enableDetectionCache($cache, 3600);
```

## Chain of Responsibility Pattern

### Understanding the Pattern

CharsetHelper uses multiple strategies with automatic fallback:

```text
UConverter (intl) → iconv → mbstring
     ↓ (fails)         ↓ (fails)    ↓ (always works)
```

### Custom Chain Configuration

```php
$processor = new CharsetProcessor();

// Reset and rebuild transcoder chain
$processor->resetTranscoders();
$processor->registerTranscoder(new MyCustomTranscoder(), 200);
$processor->registerTranscoder(new UConverterTranscoder(), 100);
$processor->registerTranscoder(new IconvTranscoder(), 50);

// Reset and rebuild detector chain
$processor->resetDetectors();
$processor->registerDetector(new MyCustomDetector(), 200);
$processor->registerDetector(new BomDetector(), 160);
$processor->registerDetector(new MbStringDetector(), 100);
```

## Performance Optimization

### Batch Processing

Use batch methods for large datasets:

```php
// Slow: Detects encoding for each item
$results = array_map(
    fn($item) => CharsetHelper::toUtf8($item, CharsetHelper::AUTO),
    $items
);

// Fast: Single encoding detection (40-60% faster)
$results = CharsetHelper::toCharsetBatch($items, 'UTF-8', CharsetHelper::AUTO);
```

### Property Mappers for Large Objects

Use custom property mappers for objects with many properties:

```php
// Without mapper: All 100 properties converted
$utf8Object = $processor->toUtf8($largeObject);

// With mapper: Only 5 properties converted (60% faster)
$processor->registerPropertyMapper(LargeObject::class, new SelectiveMapper());
$utf8Object = $processor->toUtf8($largeObject);
```

### Caching Detection Results

Cache encoding detection for repeated operations:

```php
$processor = new CharsetProcessor();
$processor->enableDetectionCache($redisCache, 7200);

// First call: Detects and caches
$encoding = $processor->detect($data);

// Subsequent calls: Uses cache (50-80% faster)
$encoding = $processor->detect($data);
```

## Testing and Mocking

### Mocking CharsetProcessor

```php
use PHPUnit\Framework\TestCase;
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

class MyServiceTest extends TestCase
{
    public function testProcess(): void
    {
        $processor = $this->createMock(CharsetProcessorInterface::class);
        $processor->expects($this->once())
            ->method('toUtf8')
            ->with('test data')
            ->willReturn('converted data');

        $service = new MyService($processor);
        $result = $service->process('test data');

        $this->assertSame('converted data', $result);
    }
}
```

### Testing Custom Handlers

```php
class CustomTranscoderTest extends TestCase
{
    public function testTranscode(): void
    {
        $transcoder = new MyCustomTranscoder();

        $result = $transcoder->transcode('test', 'UTF-8', 'MY-ENCODING', []);

        $this->assertSame('expected result', $result);
    }

    public function testPriority(): void
    {
        $transcoder = new MyCustomTranscoder();

        $this->assertSame(75, $transcoder->getPriority());
    }

    public function testIsAvailable(): void
    {
        $transcoder = new MyCustomTranscoder();

        $this->assertTrue($transcoder->isAvailable());
    }
}
```
