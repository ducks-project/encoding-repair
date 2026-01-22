# <a name="charsethelper__registertranscoder"></a>[CharsetHelper::registerTranscoder](#charsethelper__registertranscoder)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::registerTranscoder — Register custom transcoding strategy

## [Description](#description)

```php
public static CharsetHelper::registerTranscoder(
    TranscoderInterface|callable $transcoder,
    ?int $priority = null
): void
```

Registers a custom transcoding provider in the Chain of Responsibility.
Allows extending CharsetHelper with custom encoding conversion strategies
without modifying the core.

## [Parameters](#parameters)

**transcoder**:

TranscoderInterface instance or callable with signature:

```php
function(string $data, string $to, string $from, ?array $options): ?string
```

The transcoder must return the converted string on success,
or null to pass to the next transcoder in the chain.

**priority**:

Optional priority override (null = use transcoder's default priority).
Higher values execute first. Default priorities: UConverter (100), iconv (50), mbstring (10).

## [Return Values](#return-values)

No value is returned.

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws InvalidArgumentException if transcoder is not a TranscoderInterface instance or callable.

## [Examples](#examples)

### Example #1 Register custom transcoder with TranscoderInterface

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;
use Ducks\Component\EncodingRepair\Transcoder\TranscoderInterface;

class MyCustomTranscoder implements TranscoderInterface
{
    public function transcode(string $data, string $to, string $from, array $options): ?string
    {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null;
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

CharsetHelper::registerTranscoder(new MyCustomTranscoder());
CharsetHelper::registerTranscoder(new MyCustomTranscoder(), 150); // Override priority
```

### Example #2 Register callable transcoder

```php
<?php
use Ducks\Component\EncodingRepair\CharsetHelper;

CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, ?array $options): ?string {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null;
    },
    150  // Priority
);

$result = CharsetHelper::toCharset($data, 'UTF-8', 'MY-CUSTOM-ENCODING');
```

## [Notes](#notes)

Transcoders are executed in priority order (highest first):

- Priority 100+: Custom high-priority transcoders
- Priority 100: UConverter (ext-intl)
- Priority 50: iconv
- Priority 10: mbstring
- Priority 0-9: Custom low-priority transcoders

## [See Also](#see-also)

- [CharsetHelper::registerDetector] — Register custom detector
- [CharsetHelper::toCharset] — Convert with registered transcoders

[CharsetHelper::registerDetector]: ./CharsetHelper.registerDetector.md#charsethelper__registerdetector
[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#charsethelper__tocharset
