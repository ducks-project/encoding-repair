# <a name="charsethelper__registertranscoder"></a>[CharsetHelper::registerTranscoder](#charsethelper__registertranscoder)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::registerTranscoder — Register custom transcoding strategy

## [Description](#description)

```php
public static CharsetHelper::registerTranscoder(
    string|callable $transcoder,
    bool $prepend = true
): void
```

Registers a custom transcoding provider in the Chain of Responsibility.
Allows extending CharsetHelper with custom encoding conversion strategies
without modifying the core.

## [Parameters](#parameters)

**transcoder**:

A method name (string) or callable with signature:

```php
function(string $data, string $to, string $from, array $options): ?string
```

The transcoder must return the converted string on success,
or null to pass to the next transcoder in the chain.

**prepend**:

When true, adds the transcoder at the beginning of the chain (higher priority).
When false, appends to the end (lower priority).

## [Return Values](#return-values)

No value is returned.

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws InvalidArgumentException if the transcoder is invalid (not a string or callable).

## [Examples](#examples)

### Example #1 Register custom transcoder

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

CharsetHelper::registerTranscoder(
    function (string $data, string $to, string $from, array $options): ?string {
        if ($from === 'MY-CUSTOM-ENCODING') {
            return myCustomConversion($data, $to);
        }
        return null; // Pass to next transcoder
    },
    true  // High priority
);

$result = CharsetHelper::toCharset($data, 'UTF-8', 'MY-CUSTOM-ENCODING');
```

### Example #2 Register transcoder for proprietary format

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

class LegacyEncoder {
    public static function transcode(string $data, string $to, string $from, array $options): ?string {
        if ($from !== 'LEGACY-FORMAT') {
            return null;
        }
        // Custom conversion logic
        return convertFromLegacy($data, $to);
    }
}

CharsetHelper::registerTranscoder([LegacyEncoder::class, 'transcode']);
```

## [Notes](#notes)

Transcoders are called in priority order:

1. Custom transcoders (prepended)
2. UConverter (ext-intl)
3. iconv
4. mbstring
5. Custom transcoders (appended)

## [See Also](#see-also)

- [CharsetHelper::registerDetector] — Register custom detector
- [CharsetHelper::toCharset] — Convert with registered transcoders

[CharsetHelper::registerDetector]: ./CharsetHelper.registerDetector.md#charsethelper__registerdetector
[CharsetHelper::toCharset]: ./CharsetHelper.toCharset.md#charsethelper__tocharset
