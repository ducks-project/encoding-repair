# TranscoderChain

Chain of Responsibility coordinator for transcoder strategies.

## Namespace

```php
Ducks\Component\EncodingRepair\Transcoder\TranscoderChain
```

## Description

`TranscoderChain` manages a collection of `TranscoderInterface` implementations and executes them in priority order using the Chain of Responsibility pattern. Each transcoder is tried in sequence until one successfully converts the data.

## Class Declaration

```php
final class TranscoderChain
```

## Features

- **Priority-based execution**: Higher priority transcoders execute first
- **Automatic fallback**: If one transcoder fails, the next is tried
- **SplPriorityQueue**: Efficient priority management
- **Extensible**: Register custom transcoders at runtime

## Methods

### register

```php
public function register(TranscoderInterface $transcoder, ?int $priority = null): void
```

Register a transcoder with optional priority override.

**Parameters:**
- `$transcoder`: TranscoderInterface instance
- `$priority`: Priority override (null = use transcoder's default)

**Example:**
```php
$chain = new TranscoderChain();
$chain->register(new MyTranscoder(), 150);
```

### transcode

```php
public function transcode(
    string $data,
    string $to,
    string $from,
    array $options
): ?string
```

Execute the transcoder chain.

**Parameters:**
- `$data`: String to convert
- `$to`: Target encoding
- `$from`: Source encoding
- `$options`: Conversion options

**Returns:** Converted string or null if all transcoders fail

**Example:**
```php
$result = $chain->transcode($data, 'UTF-8', 'ISO-8859-1', []);
```

## Default Transcoders

The chain includes these transcoders by default (in priority order):

1. **UConverterTranscoder** (priority: 100) - Uses ext-intl
2. **IconvTranscoder** (priority: 50) - Uses iconv
3. **MbStringTranscoder** (priority: 10) - Uses mbstring

## Usage Example

```php
use Ducks\Component\EncodingRepair\Transcoder\TranscoderChain;
use Ducks\Component\EncodingRepair\Transcoder\IconvTranscoder;

$chain = new TranscoderChain();
$chain->register(new IconvTranscoder());

$utf8 = $chain->transcode($data, 'UTF-8', 'ISO-8859-1', []);
```

## See Also

- [TranscoderInterface](TranscoderInterface.md) - Transcoder contract
- [ChainOfResponsibilityTrait](ChainOfResponsibilityTrait.md) - Generic chain implementation
- [CharsetHelper](CharsetHelper.md) - Main facade
