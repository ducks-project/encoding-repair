# CharsetHelper - How To Guide

Complete guide with practical examples and use cases for CharsetHelper.

## Table of Contents

- [Basic Usage](#basic-usage)
- [Service-Based Usage (New in v1.1)](#service-based-usage-new-in-v11)
- [String Cleaners (New in v1.3)](#string-cleaners-new-in-v13)
- [Common Use Cases](#common-use-cases)
- [Advanced Scenarios](#advanced-scenarios)
- [Integration Examples](#integration-examples)
- [Troubleshooting](#troubleshooting)
- [Best Practices](#best-practices)

---

## Basic Usage

### Encoding Validation (New)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Check if string is UTF-8
if (CharsetHelper::is($data, 'UTF-8')) {
    echo "Already UTF-8, no conversion needed";
} else {
    $data = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);
}

// Validate before database insert
$userInput = 'Gérard Müller';
if (CharsetHelper::is($userInput, 'UTF-8')) {
    $db->insert('users', ['name' => $userInput]);
}

// Check with encoding aliases
$iso = mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');
CharsetHelper::is($iso, 'CP1252');      // true (alias)
CharsetHelper::is($iso, 'ISO-8859-1');  // true
```

### Simple String Conversion

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Convert ISO-8859-1 to UTF-8
$latin = "Café résumé";
$utf8 = CharsetHelper::toUtf8($latin, CharsetHelper::ENCODING_ISO);

// Convert UTF-8 to Windows-1252
$utf8 = "Café résumé";
$iso = CharsetHelper::toIso($utf8);
```

### Array Conversion

```php
$data = [
    'name' => 'José',
    'city' => 'São Paulo',
    'description' => 'Développeur'
];

$utf8Data = CharsetHelper::toUtf8($data, CharsetHelper::ENCODING_ISO);
```

### Object Conversion

```php
class User {
    public $name;
    public $email;
    public $address;
}

$user = new User();
$user->name = 'José García';
$user->email = 'jose@example.com';

// Returns a cloned object with converted properties
$utf8User = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
```

---

## Service-Based Usage (New in v1.1)

### Using CharsetProcessor

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

// Create a processor instance
$processor = new CharsetProcessor();

// Use it like CharsetHelper
$utf8 = $processor->toUtf8($data, CharsetHelper::ENCODING_ISO);
```

### Fluent API Configuration

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();
$processor
    ->addEncodings('SHIFT_JIS', 'EUC-JP')
    ->removeEncodings('UTF-16', 'UTF-32')
    ->resetTranscoders()
    ->queueTranscoders(new MyCustomTranscoder());

$result = $processor->toUtf8($data);
```

### Multiple Configurations

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

// Production processor - strict
$prodProcessor = new CharsetProcessor();
$prodProcessor->resetEncodings()->addEncodings('UTF-8', 'ISO-8859-1');

// Import processor - permissive
$importProcessor = new CharsetProcessor();
$importProcessor->addEncodings('SHIFT_JIS', 'EUC-JP', 'GB2312');

// Both are independent
$prodResult = $prodProcessor->toUtf8($apiData);
$importResult = $importProcessor->toUtf8($legacyData);
```

### Dependency Injection

```php
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;

class ImportService
{
    private CharsetProcessorInterface $processor;
    
    public function __construct(CharsetProcessorInterface $processor)
    {
        $this->processor = $processor;
    }
    
    public function import(array $data): array
    {
        return $this->processor->toUtf8($data);
    }
}

// Easy to test with mocks
$mock = $this->createMock(CharsetProcessorInterface::class);
$service = new ImportService($mock);
```

### Managing Encodings

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();

// Add custom encodings
$processor->addEncodings('SHIFT_JIS', 'EUC-JP');

// Remove unused encodings
$processor->removeEncodings('UTF-32');

// Get current encodings
$encodings = $processor->getEncodings();
print_r($encodings);

// Reset to defaults
$processor->resetEncodings();
```

---

## Type Interpreters (New in v1.2)

### Custom Property Mappers

#### Selective Property Processing

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
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
$user->password = 'secret123';
$utf8User = $processor->toUtf8($user);
// Only name and email are converted, password remains unchanged
```

#### Performance: Large Objects

```php
// Object with 50 properties, only 2 need conversion
class LargeEntity
{
    public $title;  // Needs conversion
    public $description;  // Needs conversion
    // ... 48 other properties (binary data, numbers, etc.)
}

class LargeEntityMapper implements PropertyMapperInterface
{
    public function map(object $object, callable $transcoder, array $options): object
    {
        $copy = clone $object;
        $copy->title = $transcoder($object->title);
        $copy->description = $transcoder($object->description);
        // Skip 48 other properties
        return $copy;
    }
}

$processor->registerPropertyMapper(LargeEntity::class, new LargeEntityMapper());
// 60% faster than default object processing
```

### Custom Type Interpreters

#### Resource Interpreter

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

---

## String Cleaners (New in v1.3)

### Cleaner Execution Strategies

#### Pipeline Strategy (Default - Middleware Pattern)

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;

// Default: PipelineStrategy applies all cleaners successively
$processor = new CharsetProcessor();
$processor->registerCleaner(new BomCleaner());
$processor->registerCleaner(new HtmlEntityCleaner());

// Both cleaners are applied: BOM removed, then HTML entities decoded
$result = $processor->toUtf8($data, 'ISO-8859-1', ['clean' => true]);
```

#### First Match Strategy (Chain of Responsibility)

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;
use Ducks\Component\EncodingRepair\Cleaner\MbScrubCleaner;
use Ducks\Component\EncodingRepair\Cleaner\PregMatchCleaner;

// Stops at first successful cleaner (performance optimization)
$chain = new CleanerChain(new FirstMatchStrategy());
$chain->register(new MbScrubCleaner());
$chain->register(new PregMatchCleaner());

// Only MbScrubCleaner is executed (stops at first success)
$result = $chain->clean($data, 'UTF-8', []);
```

#### Tagged Strategy (Selective Execution)

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\TaggedStrategy;
use Ducks\Component\EncodingRepair\Cleaner\BomCleaner;
use Ducks\Component\EncodingRepair\Cleaner\HtmlEntityCleaner;
use Ducks\Component\EncodingRepair\Cleaner\WhitespaceCleaner;

// Only execute cleaners with specific tags
$chain = new CleanerChain(new TaggedStrategy(['bom', 'html']));
$chain->register(new BomCleaner(), null, ['bom']);
$chain->register(new HtmlEntityCleaner(), null, ['html']);
$chain->register(new WhitespaceCleaner(), null, ['whitespace']); // Ignored

// Only BOM and HTML cleaners are executed
$result = $chain->clean($data, 'UTF-8', []);
```

#### Dynamic Strategy Switching

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerChain;
use Ducks\Component\EncodingRepair\Cleaner\PipelineStrategy;
use Ducks\Component\EncodingRepair\Cleaner\FirstMatchStrategy;

$chain = new CleanerChain(new PipelineStrategy());
$chain->register(new BomCleaner());
$chain->register(new HtmlEntityCleaner());

// Apply all cleaners
$result1 = $chain->clean($data, 'UTF-8', []);

// Switch to first-match for performance
$chain->setStrategy(new FirstMatchStrategy());
$result2 = $chain->clean($data, 'UTF-8', []);
```

### Using Built-in Cleaners

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Cleaners are disabled by default
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1');

// Enable with clean option
$result = CharsetHelper::toUtf8($data, 'ISO-8859-1', ['clean' => true]);

// Automatically enabled in repair()
$fixed = CharsetHelper::repair($corruptedData);
```

### Custom Cleaner Implementation

```php
use Ducks\Component\EncodingRepair\Cleaner\CleanerInterface;
use Ducks\Component\EncodingRepair\CharsetProcessor;

class CustomCleaner implements CleanerInterface
{
    public function clean(string $data, string $encoding, array $options): ?string
    {
        // Only handle UTF-8
        if ('UTF-8' !== strtoupper($encoding)) {
            return null;
        }

        // Remove non-printable characters
        return preg_replace('/[^\x20-\x7E]/', '', $data);
    }

    public function getPriority(): int
    {
        return 75; // Between PregMatch (50) and MbScrub (100)
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

$processor = new CharsetProcessor();
$processor->registerCleaner(new CustomCleaner());

// Use with clean option
$result = $processor->toUtf8($data, 'ISO-8859-1', ['clean' => true]);
```

### Managing Cleaners

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;

$processor = new CharsetProcessor();

// Reset to defaults (MbScrub, PregMatch, Iconv)
$processor->resetCleaners();

// Register custom cleaner
$cleaner = new CustomCleaner();
$processor->registerCleaner($cleaner);

// Unregister specific cleaner
$processor->unregisterCleaner($cleaner);
```

### Performance Comparison

```php
// Built-in cleaners performance (benchmarked on 10,000 iterations):
// - PregMatchCleaner: ~0.9μs (fastest, UTF-8 only)
// - MbScrubCleaner: ~1.0μs (best quality)
// - IconvCleaner: ~1.6μs (universal fallback)

// Use clean option for corrupted data
$corruptedData = "Caf\xC3\xA9 \xC2\x88 invalid \x00 bytes";
$cleaned = CharsetHelper::toUtf8($corruptedData, 'UTF-8', ['clean' => true]);
// Result: "Café " (invalid bytes removed)
```

---

## Common Use Cases

### 1. Database Migration

#### Migrate MySQL Latin1 to UTF-8

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Step 1: Read data from old Latin1 database
$pdo = new PDO('mysql:host=localhost;dbname=olddb;charset=latin1', 'user', 'pass');
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Step 2: Convert all data to UTF-8
foreach ($users as &$user) {
    $user = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
}

// Step 3: Insert into new UTF-8 database
$newPdo = new PDO('mysql:host=localhost;dbname=newdb;charset=utf8mb4', 'user', 'pass');
$insert = $newPdo->prepare("INSERT INTO users (id, name, email) VALUES (?, ?, ?)");

foreach ($users as $user) {
    $insert->execute([$user['id'], $user['name'], $user['email']]);
}
```

#### Batch Migration with Progress (Optimized)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function migrateTable(PDO $source, PDO $target, string $table, int $batchSize = 1000): void
{
    $offset = 0;
    $total = $source->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    
    while ($offset < $total) {
        $stmt = $source->query("SELECT * FROM {$table} LIMIT {$batchSize} OFFSET {$offset}");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Use batch processing for 40-60% performance improvement
        $rows = CharsetHelper::toCharsetBatch(
            $rows,
            CharsetHelper::ENCODING_UTF8,
            CharsetHelper::AUTO  // Single detection for entire batch
        );
        
        foreach ($rows as $row) {
            insertRow($target, $table, $row);
        }
        
        $offset += $batchSize;
        echo "Migrated {$offset}/{$total} rows\n";
    }
}
```

### 2. CSV File Processing

#### Import CSV with Unknown Encoding

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function importCsv(string $filename): array
{
    $content = file_get_contents($filename);
    
    // Auto-detect encoding
    $encoding = CharsetHelper::detect($content);
    echo "Detected encoding: {$encoding}\n";
    
    // Convert to UTF-8
    $utf8Content = CharsetHelper::toCharset(
        $content,
        CharsetHelper::ENCODING_UTF8,
        $encoding
    );
    
    // Parse CSV
    $lines = str_getcsv($utf8Content, "\n");
    $data = array_map(fn($line) => str_getcsv($line), $lines);
    
    return $data;
}
```

#### Import CSV with Batch Processing (Optimized)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function importCsvOptimized(string $filename): array
{
    // Parse CSV first
    $lines = file($filename);
    $data = array_map(fn($line) => str_getcsv($line), $lines);
    
    // Batch convert all rows (40-60% faster with AUTO detection)
    return CharsetHelper::toCharsetBatch(
        $data,
        CharsetHelper::ENCODING_UTF8,
        CharsetHelper::AUTO  // Single detection for entire file
    );
}
```

#### Export CSV with Specific Encoding

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function exportCsv(array $data, string $filename, string $encoding = 'UTF-8'): void
{
    $csv = '';
    foreach ($data as $row) {
        $csv .= implode(',', array_map('escapeCsv', $row)) . "\n";
    }
    
    // Convert to target encoding
    $encoded = CharsetHelper::toCharset($csv, $encoding, CharsetHelper::ENCODING_UTF8);
    
    file_put_contents($filename, $encoded);
}
```

### 3. Web Scraping

#### Scrape Website with Auto-Detection

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function scrapeWebsite(string $url): string
{
    $html = file_get_contents($url);
    
    // Try to detect from meta tag
    if (preg_match('/<meta[^>]+charset=["\']?([^"\'>\s]+)/i', $html, $matches)) {
        $encoding = strtoupper($matches[1]);
    } else {
        // Auto-detect
        $encoding = CharsetHelper::detect($html);
    }
    
    // Convert to UTF-8
    return CharsetHelper::toCharset($html, CharsetHelper::ENCODING_UTF8, $encoding);
}
```

#### Parse Multiple Pages

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function scrapeMultiplePages(array $urls): array
{
    $results = [];
    
    foreach ($urls as $url) {
        $html = file_get_contents($url);
        $utf8Html = CharsetHelper::toCharset(
            $html,
            CharsetHelper::ENCODING_UTF8,
            CharsetHelper::AUTO
        );
        
        $dom = new DOMDocument();
        @$dom->loadHTML($utf8Html);
        
        $results[$url] = extractData($dom);
    }
    
    return $results;
}
```

### 4. API Integration

#### REST API with Encoding Safety

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

class ApiController
{
    public function handleRequest(array $data): string
    {
        // Ensure all input is UTF-8
        $data = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);
        
        // Process data
        $result = $this->processData($data);
        
        // Safe JSON encoding
        return CharsetHelper::safeJsonEncode($result, JSON_PRETTY_PRINT);
    }
    
    public function parseResponse(string $json): array
    {
        // Safe JSON decoding with encoding repair
        return CharsetHelper::safeJsonDecode($json, true);
    }
}
```

#### SOAP API with Legacy Encoding

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

class SoapClient
{
    public function call(string $method, array $params): array
    {
        // Convert params to ISO for legacy SOAP service
        $isoParams = CharsetHelper::toIso($params);
        
        // Make SOAP call
        $response = $this->soapClient->$method($isoParams);
        
        // Convert response back to UTF-8
        return CharsetHelper::toUtf8($response, CharsetHelper::ENCODING_ISO);
    }
}
```

### 5. File Upload Handling

#### Process Uploaded Files

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function processUpload(array $file): array
{
    $content = file_get_contents($file['tmp_name']);
    
    // Detect and convert
    $encoding = CharsetHelper::detect($content);
    $utf8Content = CharsetHelper::toCharset(
        $content,
        CharsetHelper::ENCODING_UTF8,
        $encoding
    );
    
    // Parse content
    return parseContent($utf8Content);
}
```

---

## Advanced Scenarios

### 1. Repair Double-Encoded Data

#### Fix Corrupted Database

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Common issue: UTF-8 data stored in Latin1 column, then read as UTF-8
$corrupted = "CafÃ©"; // Should be "Café"

$fixed = CharsetHelper::repair($corrupted);
echo $fixed; // "Café"
```

#### Batch Repair

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function repairDatabase(PDO $pdo, string $table, array $columns): void
{
    $stmt = $pdo->query("SELECT * FROM {$table}");
    $update = $pdo->prepare("UPDATE {$table} SET " . 
        implode(', ', array_map(fn($col) => "{$col} = ?", $columns)) . 
        " WHERE id = ?");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $values = [];
        foreach ($columns as $col) {
            $values[] = CharsetHelper::repair($row[$col]);
        }
        $values[] = $row['id'];
        
        $update->execute($values);
    }
}
```

### 2. Custom Transcoder

#### Add Support for Custom Encoding

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
        
        // Custom EBCDIC conversion
        $converted = $this->convertFromEbcdic($data);
        return mb_convert_encoding($converted, $to, 'UTF-8');
    }
    
    public function getPriority(): int
    {
        return 75;
    }
    
    public function isAvailable(): bool
    {
        return true;
    }
    
    private function convertFromEbcdic(string $data): string
    {
        // EBCDIC to ASCII conversion logic
        return $data;
    }
}

CharsetHelper::registerTranscoder(new EbcdicTranscoder());

// Now use it
$result = CharsetHelper::toCharset($ebcdicData, 'UTF-8', 'EBCDIC');
```

### 3. Custom Detector

#### BOM Detection for 100% Accuracy (New in v1.2)

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\BomDetector;

// Register BomDetector for 100% accurate BOM detection
CharsetHelper::registerDetector(new BomDetector());

$utf8Bom = "\xEF\xBB\xBF" . 'Hello World';
$utf16Le = "\xFF\xFE" . 'Hello';
$noBom = 'Hello World';

echo CharsetHelper::detect($utf8Bom);  // 'UTF-8' (BOM detected)
echo CharsetHelper::detect($utf16Le);  // 'UTF-16LE' (BOM detected)
echo CharsetHelper::detect($noBom);    // Falls back to PregMatchDetector/MbStringDetector
```

#### Fast ASCII/UTF-8 Detection with PregMatchDetector (New in v1.2)

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

#### Detect Proprietary Format

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Detector\DetectorInterface;

class Utf16BomDetector implements DetectorInterface
{
    public function detect(string $string, array $options): ?string
    {
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

CharsetHelper::registerDetector(new Utf16BomDetector());

// Or use a callable
CharsetHelper::registerDetector(
    function (string $string, array $options): ?string {
        if (strlen($string) >= 2) {
            if (ord($string[0]) === 0xFF && ord($string[1]) === 0xFE) {
                return 'UTF-16LE';
            }
        }
        return null;
    },
    150  // Priority
);
```

### 4. Streaming Large Files

#### Process Large Files in Chunks

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function convertLargeFile(string $input, string $output, int $chunkSize = 8192): void
{
    $in = fopen($input, 'rb');
    $out = fopen($output, 'wb');
    
    while (!feof($in)) {
        $chunk = fread($in, $chunkSize);
        $utf8Chunk = CharsetHelper::toUtf8($chunk, CharsetHelper::ENCODING_ISO);
        fwrite($out, $utf8Chunk);
    }
    
    fclose($in);
    fclose($out);
}
```

---

## Integration Examples

### Laravel Integration

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;
use Illuminate\Support\ServiceProvider;

class CharsetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register as singleton
        $this->app->singleton(CharsetProcessorInterface::class, function () {
            return new CharsetProcessor();
        });
        
        // Alias for convenience
        $this->app->alias(CharsetProcessorInterface::class, 'charset');
    }
}

// Usage in controller with dependency injection
class UserController extends Controller
{
    public function __construct(
        private CharsetProcessorInterface $charset
    ) {}
    
    public function import(Request $request)
    {
        $data = $this->charset->toUtf8($request->all());
        User::create($data);
    }
}

// Or use facade
class UserController extends Controller
{
    public function import(Request $request)
    {
        $data = app('charset')->toUtf8($request->all());
        User::create($data);
    }
}
```

### Symfony Integration

```php
use Ducks\Component\EncodingRepair\CharsetProcessor;
use Ducks\Component\EncodingRepair\CharsetProcessorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// Register as service in services.yaml
// services:
//     Ducks\Component\EncodingRepair\CharsetProcessorInterface:
//         class: Ducks\Component\EncodingRepair\CharsetProcessor

class ApiController extends AbstractController
{
    public function __construct(
        private CharsetProcessorInterface $charset
    ) {}
    
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $utf8Data = $this->charset->toUtf8($data);
        
        // Process...
        
        return new JsonResponse(
            $this->charset->safeJsonEncode($result)
        );
    }
}
```

### WordPress Plugin

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

add_filter('the_content', function ($content) {
    return CharsetHelper::repair($content);
});

add_action('save_post', function ($post_id) {
    $post = get_post($post_id);
    $fixed = CharsetHelper::repair($post->post_content);
    
    if ($fixed !== $post->post_content) {
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $fixed
        ]);
    }
});
```

---

## Troubleshooting

### Issue: Conversion Not Working

```php
// Check if string is already in target encoding
$string = "Café";
$encoding = CharsetHelper::detect($string);
echo "Current encoding: {$encoding}\n";

// Force conversion even if detected as UTF-8
$result = CharsetHelper::toCharset(
    $string,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['normalize' => true]
);
```

### Issue: Special Characters Lost

```php
// Enable transliteration
$result = CharsetHelper::toCharset($data, 'ASCII', 'UTF-8', [
    'translit' => true,  // é → e
    'ignore' => true     // Skip unmappable chars
]);
```

### Issue: Performance Problems

```php
// Option 1: Enable cache for entire detector chain (recommended)
$processor = new CharsetProcessor();
$processor->enableDetectionCache(); // InternalArrayCache by default

foreach ($largeDataset as $item) {
    $result = $processor->toUtf8($item);
    // Repeated strings benefit from cache (50-80% faster)
}

// Option 2: Cache specific expensive detector
use Ducks\Component\EncodingRepair\Detector\CachedDetector;
use Ducks\Component\EncodingRepair\Detector\FileInfoDetector;

$fileInfo = new FileInfoDetector();
$cached = new CachedDetector($fileInfo); // Cache only this detector

$processor = new CharsetProcessor();
$processor->resetDetectors();
$processor->registerDetector(new BomDetector());
$processor->registerDetector(new PregMatchDetector());
$processor->registerDetector(new MbStringDetector());
$processor->registerDetector($cached); // Only FileInfo is cached

// Option 3: External cache (Redis, Memcached, APCu)
// $redis = new \Symfony\Component\Cache\Psr16Cache($redisAdapter);
// $processor->enableDetectionCache($redis, 7200);
```

---

## Best Practices

### 1. Always Validate Input

```php
try {
    $result = CharsetHelper::toCharset($data, 'UTF-8', 'INVALID');
} catch (InvalidArgumentException $e) {
    // Handle invalid encoding
    error_log($e->getMessage());
}
```

### 2. Use Batch Processing for Large Arrays

```php
// Slow: Individual conversion with AUTO detection
$results = [];
foreach ($items as $item) {
    $results[] = CharsetHelper::toUtf8($item, CharsetHelper::AUTO);
}

// Fast: Batch conversion (40-60% faster)
$results = CharsetHelper::toCharsetBatch(
    $items,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// Or detect once, then convert
$encoding = CharsetHelper::detectBatch($items);
$results = CharsetHelper::toCharsetBatch($items, 'UTF-8', $encoding);
```

### 3. Use Specific Encodings When Known

```php
// Good: Check encoding first to avoid unnecessary conversion
if (!CharsetHelper::is($data, 'UTF-8')) {
    $data = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);
}

// Good: Specific encoding
$result = CharsetHelper::toUtf8($data, CharsetHelper::WINDOWS_1252);

// Avoid: Auto-detection when encoding is known
$result = CharsetHelper::toUtf8($data, CharsetHelper::AUTO);

// Exception: Use AUTO with batch processing for large arrays
$results = CharsetHelper::toCharsetBatch(
    $largeArray,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO  // Only 1 detection instead of N
);
```

### 4. Handle Errors Gracefully

```php
try {
    $json = CharsetHelper::safeJsonEncode($data);
} catch (RuntimeException $e) {
    // Log error and return fallback
    error_log("JSON encoding failed: " . $e->getMessage());
    return json_encode(['error' => 'Encoding failed']);
}
```

### 5. Test with Real Data

```php
// Unit test with actual problematic data
public function testRealWorldData(): void
{
    $corrupted = file_get_contents('tests/fixtures/corrupted.txt');
    $fixed = CharsetHelper::repair($corrupted);
    
    $this->assertStringContainsString('Café', $fixed);
}
```

### 6. Monitor Performance

```php
$start = microtime(true);
$result = CharsetHelper::toUtf8($largeData);
$duration = microtime(true) - $start;

if ($duration > 1.0) {
    error_log("Slow conversion: {$duration}s");
}
```

---

## Additional Resources

- [CharsetHelper API Documentation](./classes/CharsetHelper.md)
- [CharsetProcessor API Documentation](./classes/CharsetProcessor.md)
- [CharsetProcessorInterface API Documentation](./classes/CharsetProcessorInterface.md)
- [CachedDetector API Documentation](./classes/CachedDetector.md)
- [BomDetector API Documentation](./classes/BomDetector.md)
- [PregMatchDetector API Documentation](./classes/PregMatchDetector.md)
- [DetectionCacheTrait API Documentation](./classes/DetectionCacheTrait.md)
- [InternalArrayCache API Documentation](./classes/InternalArrayCache.md)
- [ArrayCache API Documentation](./classes/ArrayCache.md)
- [CleanerInterface API Documentation](./classes/CleanerInterface.md)
- [CleanerChain API Documentation](./classes/CleanerChain.md)
- [MbScrubCleaner API Documentation](./classes/MbScrubCleaner.md)
- [PregMatchCleaner API Documentation](./classes/PregMatchCleaner.md)
- [IconvCleaner API Documentation](./classes/IconvCleaner.md)
- [Type Interpreter System](./INTERPRETER_SYSTEM.md)
- [TypeInterpreterInterface API](./classes/TypeInterpreterInterface.md)
- [PropertyMapperInterface API](./classes/PropertyMapperInterface.md)
- [Service Architecture Guide](./SERVICE_ARCHITECTURE.md)
- [GitHub Repository](https://github.com/ducks-project/encoding-repair)
- [Issue Tracker](https://github.com/ducks-project/encoding-repair/issues)
