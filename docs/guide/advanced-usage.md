# Advanced Usage

## Service Architecture (New in v1.1)

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

See [Service Architecture](service-architecture.md) for complete documentation.

## Repair Double-Encoded Data

### Fix Corrupted Strings

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Common issue: UTF-8 data stored in Latin1 column, then read as UTF-8
$corrupted = "CafÃ©"; // Should be "Café"

$fixed = CharsetHelper::repair($corrupted);
echo $fixed; // "Café"

// With custom max depth
$fixed = CharsetHelper::repair(
    $corrupted,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['maxDepth' => 10]  // Try to peel up to 10 encoding layers
);
```

## Custom Transcoder

### Add Support for Custom Encoding

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class EbcdicTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if ('EBCDIC' !== $from) {
            return null;
        }
        
        $converted = $this->convertFromEbcdic($data);
        return mb_convert_encoding($converted, $to, 'UTF-8');
    }
    
    public function getPriority(): int
    {
        return 75; // Between iconv (50) and UConverter (100)
    }
    
    public function isAvailable(): bool
    {
        return true;
    }
    
    private function convertFromEbcdic(string $data): string
    {
        // EBCDIC conversion logic
        return $data;
    }
}

CharsetHelper::registerTranscoder(new EbcdicTranscoder());
$result = CharsetHelper::toCharset($data, 'UTF-8', 'EBCDIC');
```

### Legacy Callable Support

```php
CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, array $options): ?string {
        if ('CUSTOM' === $from) {
            return customConvert($data, $to);
        }
        return null;
    },
    150  // Priority
);
```

## Custom Detector

### Fast ASCII/UTF-8 Detection (New in v1.2)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\PregMatchDetector;

// Register PregMatchDetector for 70% faster ASCII/UTF-8 detection
CharsetHelper::registerDetector(new PregMatchDetector());

$ascii = 'Hello World';
$utf8 = 'Café';
$iso = mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');

echo CharsetHelper::detect($ascii);  // 'ASCII' (fast-path)
echo CharsetHelper::detect($utf8);   // 'UTF-8' (preg_match validation)
echo CharsetHelper::detect($iso);    // 'ISO-8859-1' (fallback to MbStringDetector)
```

### Detect Proprietary Format

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

CharsetHelper::registerDetector(
    function (string $string, array $options): ?string {
        // Check for UTF-16 BOM
        if (strlen($string) >= 2) {
            if (ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
                return 'UTF-16LE';
            }
            if (ord($string[0]) === 0xFE && ord($string[1]) === 0xFF) {
                return 'UTF-16BE';
            }
        }
        return null;
    },
    true  // Prepend (higher priority)
);
```

## Chain of Responsibility Pattern

CharsetHelper uses multiple strategies with automatic fallback:

```text
UConverter (intl) → iconv → mbstring
     ↓ (fails)         ↓ (fails)    ↓ (always works)
```

**Transcoder priorities:**

1. **UConverter** (priority: 100, requires `ext-intl`): Best precision, supports many encodings
2. **iconv** (priority: 50): Good performance, supports transliteration
3. **mbstring** (priority: 10): Universal fallback, most permissive

**Custom transcoders** can be registered with any priority value. Higher values execute first.

**Detector priorities:**

1. **CachedDetector** (priority: 200): Caches detection results
2. **PregMatchDetector** (priority: 150): Fast ASCII/UTF-8 detection (~70% faster)
3. **MbStringDetector** (priority: 100): Fast and reliable for common encodings
4. **FileInfoDetector** (priority: 50): Fallback for difficult cases

## Performance Optimization

### PSR-16 Cache for Detection (New in v1.2)

```php
use Ducks\Component\EncodingRepair\Cache\ArrayCache;
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\MbStringDetector;
use Ducks\Component\EncodingRepair\CharsetProcessor;

// Use built-in ArrayCache
$cache = new ArrayCache();
$detector = new CachedDetector(new MbStringDetector(), $cache, 3600);

$processor = new CharsetProcessor();
$processor->registerDetector($detector, 200);

// Or use Redis/Memcached for distributed caching
// $redis = new \Symfony\Component\Cache\Psr16Cache($redisAdapter);
// $detector = new CachedDetector(new MbStringDetector(), $redis, 7200);
```

### Cache Detection Results

```php
$cache = [];

function convertWithCache(string $data): string
{
    static $cache = [];
    $hash = md5($data);
    
    if (!isset($cache[$hash])) {
        $cache[$hash] = CharsetHelper::toUtf8($data);
    }
    
    return $cache[$hash];
}
```

### Use Specific Encodings

```php
// Good: Specific encoding
$result = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);

// Avoid: Auto-detection when encoding is known
$result = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);
```

## Error Handling

### Handle Invalid Encodings

```php
try {
    $result = CharsetHelper::toCharset($data, 'UTF-8', 'INVALID');
} catch (InvalidArgumentException $e) {
    error_log($e->getMessage());
}
```

### Handle JSON Errors

```php
try {
    $json = CharsetHelper::safeJsonEncode($data);
} catch (RuntimeException $e) {
    error_log("JSON encoding failed: " . $e->getMessage());
    return json_encode(['error' => 'Encoding failed']);
}
```

## Next Steps

- [Use Cases](use-cases.md)
- [API Reference](../api/CharsetHelper.md)
- [Contributing](../contributing/development.md)
