# Use Cases

Practical examples of CharsetHelper usage in real-world scenarios.

## 1. Database Migration (Latin1 → UTF-8)

Migrate legacy databases from ISO-8859-1/Windows-1252 to UTF-8.

### Simple Migration

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Migrate user table
$users = $db->query("SELECT * FROM users")->fetchAll();

foreach ($users as $user) {
    $user = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
    $db->update('users', $user, ['id' => $user['id']]);
}
```

### Batch Migration (Faster)

```php
// Migrate 10,000 rows with batch processing (40-60% faster)
$users = $db->query("SELECT * FROM users")->fetchAll();

// Single encoding detection for entire batch
$utf8Users = CharsetHelper::toCharsetBatch(
    $users,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// Bulk update
foreach ($utf8Users as $user) {
    $db->update('users', $user, ['id' => $user['id']]);
}
```

### Migration with Error Handling

```php
$users = $db->query("SELECT * FROM users")->fetchAll();
$errors = [];

foreach ($users as $user) {
    try {
        $utf8User = CharsetHelper::toUtf8($user, CharsetHelper::WINDOWS_1252);
        $db->update('users', $utf8User, ['id' => $user['id']]);
    } catch (\Exception $e) {
        $errors[] = ['id' => $user['id'], 'error' => $e->getMessage()];
    }
}

if (!empty($errors)) {
    file_put_contents('migration_errors.json', json_encode($errors));
}
```

## 2. CSV Import with Unknown Encoding

Handle CSV files with unknown or mixed encodings.

### Auto-Detection

```php
$csv = file_get_contents('data.csv');

// Auto-detect and convert
$utf8Csv = CharsetHelper::toCharset(
    $csv,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// Parse as UTF-8
$data = str_getcsv($utf8Csv);
```

### Batch CSV Processing

```php
// Process large CSV files efficiently
$csvData = array_map('str_getcsv', file('data.csv'));

// Batch conversion (single encoding detection)
$utf8Csv = CharsetHelper::toCharsetBatch($csvData, 'UTF-8', CharsetHelper::AUTO);

// Import to database
foreach ($utf8Csv as $row) {
    $db->insert('products', [
        'name' => $row[0],
        'description' => $row[1],
        'price' => $row[2]
    ]);
}
```

### CSV with Known Encoding

```php
// European CSV (Windows-1252)
$csv = file_get_contents('european_data.csv');
$utf8Csv = CharsetHelper::toUtf8($csv, CharsetHelper::WINDOWS_1252);

// Japanese CSV (Shift_JIS)
$processor = new CharsetProcessor();
$processor->addEncodings('SHIFT_JIS');
$utf8Csv = $processor->toUtf8($japaneseData, 'SHIFT_JIS');
```

## 3. API Response Sanitization

Ensure API responses are always valid UTF-8.

### JSON API Controller

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

class ApiController
{
    public function jsonResponse($data): JsonResponse
    {
        // Ensure valid UTF-8 before encoding
        $json = CharsetHelper::safeJsonEncode($data);
        return new JsonResponse($json, 200, [], true);
    }
}
```

### REST API with Mixed Data Sources

```php
class ProductController
{
    public function getProduct(int $id): JsonResponse
    {
        // Data from legacy database (Latin1)
        $product = $db->query("SELECT * FROM products WHERE id = ?", [$id]);
        
        // Convert to UTF-8
        $product = CharsetHelper::toUtf8($product, CharsetHelper::WINDOWS_1252);
        
        // Safe JSON encoding
        return new JsonResponse(
            CharsetHelper::safeJsonEncode($product)
        );
    }
}
```

### API with Error Handling

```php
try {
    $response = CharsetHelper::safeJsonEncode($data);
    return new Response($response, 200, ['Content-Type' => 'application/json']);
} catch (\RuntimeException $e) {
    // Log encoding error
    $logger->error('JSON encoding failed', ['error' => $e->getMessage()]);
    
    // Return error response
    return new Response(
        json_encode(['error' => 'Invalid data encoding']),
        500,
        ['Content-Type' => 'application/json']
    );
}
```

## 4. Web Scraping

Handle web pages with various encodings.

### Basic Scraping

```php
$html = file_get_contents('https://example.com');

// Detect encoding from HTML meta tags or auto-detect
$encoding = CharsetHelper::detect($html);

// Convert to UTF-8 for processing
$utf8Html = CharsetHelper::toCharset(
    $html,
    CharsetHelper::ENCODING_UTF8,
    $encoding
);

$dom = new DOMDocument();
$dom->loadHTML($utf8Html);
```

### Scraping with cURL

```php
$ch = curl_init('https://example.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$html = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Extract charset from Content-Type header
preg_match('/charset=([^;]+)/i', $contentType, $matches);
$encoding = $matches[1] ?? CharsetHelper::AUTO;

// Convert to UTF-8
$utf8Html = CharsetHelper::toCharset($html, 'UTF-8', $encoding);
```

### Multi-Site Scraping

```php
$sites = [
    'https://example.fr' => 'ISO-8859-1',
    'https://example.jp' => 'SHIFT_JIS',
    'https://example.cn' => 'GB2312',
];

$processor = new CharsetProcessor();
$processor->addEncodings('SHIFT_JIS', 'GB2312', 'EUC-JP');

foreach ($sites as $url => $encoding) {
    $html = file_get_contents($url);
    $utf8Html = $processor->toUtf8($html, $encoding);
    
    // Process UTF-8 content
    processContent($utf8Html);
}
```

## 5. Legacy System Integration

Fix corrupted data from old systems.

### Double-Encoding Repair

```php
// Fix double-encoded data from old system
$legacyData = $oldSystem->getData();

// Repair corruption (e.g., "CafÃ©" → "Café")
$clean = CharsetHelper::repair(
    $legacyData,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO
);

// Process clean data
processData($clean);
```

### Deep Corruption Repair

```php
// Data encoded multiple times (up to 10 layers)
$corrupted = $legacySystem->getData();

$fixed = CharsetHelper::repair(
    $corrupted,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO,
    ['maxDepth' => 10]
);
```

### Legacy Database Sync

```php
// Sync from legacy system to modern system
$legacyRecords = $legacyDb->query("SELECT * FROM customers")->fetchAll();

foreach ($legacyRecords as $record) {
    // Repair double-encoding
    $record = CharsetHelper::repair($record);
    
    // Convert to UTF-8
    $record = CharsetHelper::toUtf8($record, CharsetHelper::WINDOWS_1252);
    
    // Insert into modern system
    $modernDb->insert('customers', $record);
}
```

## 6. File Upload Processing

Handle user-uploaded files with unknown encodings.

### Text File Upload

```php
$uploadedFile = $_FILES['document']['tmp_name'];
$content = file_get_contents($uploadedFile);

// Auto-detect and convert
$utf8Content = CharsetHelper::toCharset(
    $content,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::AUTO
);

// Save as UTF-8
file_put_contents('uploads/' . $_FILES['document']['name'], $utf8Content);
```

### CSV Upload with Validation

```php
$csvFile = $_FILES['csv']['tmp_name'];
$rows = array_map('str_getcsv', file($csvFile));

// Batch conversion
$utf8Rows = CharsetHelper::toCharsetBatch($rows, 'UTF-8', CharsetHelper::AUTO);

// Validate and import
foreach ($utf8Rows as $row) {
    if (validateRow($row)) {
        importRow($row);
    }
}
```

## 7. Email Processing

Handle emails with various encodings.

### IMAP Email Conversion

```php
$imap = imap_open('{imap.example.com:993/imap/ssl}INBOX', $user, $pass);
$emails = imap_search($imap, 'ALL');

foreach ($emails as $emailId) {
    $body = imap_body($imap, $emailId);
    
    // Convert to UTF-8
    $utf8Body = CharsetHelper::toUtf8($body, CharsetHelper::AUTO);
    
    // Process email
    processEmail($utf8Body);
}

imap_close($imap);
```

### Email Subject Decoding

```php
// Decode MIME-encoded subjects
$subject = imap_mime_header_decode($rawSubject);
$decodedSubject = '';

foreach ($subject as $part) {
    $encoding = $part->charset ?? 'UTF-8';
    $text = CharsetHelper::toUtf8($part->text, $encoding);
    $decodedSubject .= $text;
}
```

## 8. Log File Processing

Process log files with mixed encodings.

### Log Aggregation

```php
$logFiles = glob('/var/log/app/*.log');
$allLogs = [];

foreach ($logFiles as $logFile) {
    $content = file_get_contents($logFile);
    
    // Ensure UTF-8
    $utf8Content = CharsetHelper::toUtf8($content, CharsetHelper::AUTO);
    
    $allLogs[] = $utf8Content;
}

// Aggregate logs
file_put_contents('aggregated.log', implode("\n", $allLogs));
```

### Log Search with Encoding Handling

```php
function searchLogs(string $pattern, array $logFiles): array
{
    $results = [];
    
    foreach ($logFiles as $file) {
        $content = file_get_contents($file);
        $utf8Content = CharsetHelper::toUtf8($content, CharsetHelper::AUTO);
        
        if (preg_match($pattern, $utf8Content, $matches)) {
            $results[$file] = $matches;
        }
    }
    
    return $results;
}
```

## 9. XML/RSS Feed Processing

Handle feeds with various encodings.

### RSS Feed Reader

```php
$rss = file_get_contents('https://example.com/feed.xml');

// Detect encoding from XML declaration
preg_match('/encoding=["\']([^"\']+)["\']/', $rss, $matches);
$encoding = $matches[1] ?? CharsetHelper::AUTO;

// Convert to UTF-8
$utf8Rss = CharsetHelper::toCharset($rss, 'UTF-8', $encoding);

// Parse XML
$xml = simplexml_load_string($utf8Rss);
```

### Multi-Feed Aggregator

```php
$feeds = [
    'https://example.com/feed1.xml',
    'https://example.com/feed2.xml',
    'https://example.jp/feed.xml',
];

$items = [];

foreach ($feeds as $feedUrl) {
    $content = file_get_contents($feedUrl);
    $utf8Content = CharsetHelper::toUtf8($content, CharsetHelper::AUTO);
    
    $xml = simplexml_load_string($utf8Content);
    foreach ($xml->channel->item as $item) {
        $items[] = [
            'title' => (string)$item->title,
            'description' => (string)$item->description,
        ];
    }
}
```

## 10. Configuration File Migration

Migrate configuration files to UTF-8.

### INI File Migration

```php
$iniFiles = glob('config/*.ini');

foreach ($iniFiles as $file) {
    $content = file_get_contents($file);
    
    // Convert to UTF-8
    $utf8Content = CharsetHelper::toUtf8($content, CharsetHelper::WINDOWS_1252);
    
    // Backup original
    copy($file, $file . '.bak');
    
    // Save UTF-8 version
    file_put_contents($file, $utf8Content);
}
```

### JSON Config with Encoding Issues

```php
$configFile = 'config/app.json';
$content = file_get_contents($configFile);

// Repair and decode
$utf8Content = CharsetHelper::repair($content);
$config = CharsetHelper::safeJsonDecode($utf8Content, true);

// Modify config
$config['new_setting'] = 'value';

// Save with safe encoding
file_put_contents($configFile, CharsetHelper::safeJsonEncode($config));
```
