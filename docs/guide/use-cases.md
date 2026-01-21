# Use Cases

## Database Migration

### Migrate MySQL Latin1 to UTF-8

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Read data from old Latin1 database
$pdo = new PDO('mysql:host=localhost;dbname=olddb;charset=latin1', 'user', 'pass');
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert all data to UTF-8
foreach ($users as &$user) {
    $user = CharsetHelper::toUtf8($user, CharsetHelper::ENCODING_ISO);
}

// Insert into new UTF-8 database
$newPdo = new PDO('mysql:host=localhost;dbname=newdb;charset=utf8mb4', 'user', 'pass');
$insert = $newPdo->prepare("INSERT INTO users (id, name, email) VALUES (?, ?, ?)");

foreach ($users as $user) {
    $insert->execute([$user['id'], $user['name'], $user['email']]);
}
```

## CSV File Processing

### Import CSV with Unknown Encoding

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

## Web Scraping

### Scrape Website with Auto-Detection

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

function scrapeWebsite(string $url): string
{
    $html = file_get_contents($url);
    
    // Try to detect from meta tag
    if (preg_match('/<meta[^>]+charset=["\']?([^"\'>\\s]+)/i', $html, $matches)) {
        $encoding = strtoupper($matches[1]);
    } else {
        // Auto-detect
        $encoding = CharsetHelper::detect($html);
    }
    
    // Convert to UTF-8
    return CharsetHelper::toCharset($html, CharsetHelper::ENCODING_UTF8, $encoding);
}
```

## API Integration

### REST API with Encoding Safety

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

## Legacy System Integration

### Fix Double-Encoded Data

```php
use Ducks\Component\EncodingRepair\CharsetHelper;

// Fix double-encoded data from old system
$legacyData = $oldSystem->getData();

// Repair corruption
$clean = CharsetHelper::repair(
    $legacyData,
    CharsetHelper::ENCODING_UTF8,
    CharsetHelper::ENCODING_ISO
);

// Process clean data
processData($clean);
```

## Framework Integration

### Laravel

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Illuminate\Support\ServiceProvider;

class CharsetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('charset', function () {
            return new class {
                public function toUtf8($data, string $from = 'CP1252')
                {
                    return CharsetHelper::toUtf8($data, $from);
                }
                
                public function repair($data)
                {
                    return CharsetHelper::repair($data);
                }
            };
        });
    }
}

// Usage in controller
class UserController extends Controller
{
    public function import(Request $request)
    {
        $data = app('charset')->toUtf8($request->all());
        User::create($data);
    }
}
```

### Symfony

```php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $utf8Data = CharsetHelper::toUtf8($data);
        
        // Process...
        
        return new JsonResponse(
            CharsetHelper::safeJsonEncode($result)
        );
    }
}
```

### WordPress

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

## Next Steps

- [API Reference](../api/CharsetHelper.md)
- [Contributing](../contributing/development.md)
