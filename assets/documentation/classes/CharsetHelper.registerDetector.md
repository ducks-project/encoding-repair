# <a name="charsethelper__registerdetector"></a>[CharsetHelper::registerDetector](#charsethelper__registerdetector)

(PHP 7 >= 7.4.0, PHP 8)

CharsetHelper::registerDetector — Register custom detection strategy

## [Description](#description)

```php
public static CharsetHelper::registerDetector(
    string|callable $detector,
    bool $prepend = true
): void
```

Registers a custom encoding detection provider in the Chain of Responsibility.
Allows extending CharsetHelper with custom encoding detection strategies for
specialized or proprietary encodings.

## [Parameters](#parameters)

**detector**:

A method name (string) or callable with signature:

```php
function(string $string, array $options): ?string
```

The detector must return the detected encoding (uppercase string) on success,
or null to pass to the next detector in the chain.

**prepend**:

When true, adds the detector at the beginning of the chain (higher priority).
When false, appends to the end (lower priority).

## [Return Values](#return-values)

No value is returned.

## <a name="errors-exceptions"></a>[Errors/Exceptions](#errors-exceptions)

Throws InvalidArgumentException if the detector is invalid (not a string or callable).

## [Examples](#examples)

### Example #1 Register custom detector

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

CharsetHelper::registerDetector(
    function (string $string, array $options): ?string {
        if (myCustomDetection($string)) {
            return 'MY-CUSTOM-ENCODING';
        }
        return null; // Pass to next detector
    },
    true  // High priority
);

$encoding = CharsetHelper::detect($unknownString);
```

### Example #2 Register detector for Asian encodings

```php
<?php
use Ducks\Component\Component\EncodingRepair\CharsetHelper;

class AsianEncodingDetector {
    public static function detect(string $string, array $options): ?string {
        // Check for Japanese encodings
        if (self::isShiftJIS($string)) {
            return 'SHIFT_JIS';
        }
        if (self::isEUCJP($string)) {
            return 'EUC-JP';
        }
        return null;
    }

    private static function isShiftJIS(string $string): bool {
        // Detection logic
        return false;
    }

    private static function isEUCJP(string $string): bool {
        // Detection logic
        return false;
    }
}

CharsetHelper::registerDetector([AsianEncodingDetector::class, 'detect']);
```

## [Notes](#notes)

Detectors are called in priority order:

1. Custom detectors (prepended)
2. mb_detect_encoding
3. FileInfo (finfo)
4. Custom detectors (appended)

## [See Also](#see-also)

- [CharsetHelper::registerTranscoder] — Register custom transcoder
- [CharsetHelper::detect] — Detect encoding with registered detectors

[CharsetHelper::registerTranscoder]: ./CharsetHelper.registerTranscoder.md#charsethelper__registertranscoder
[CharsetHelper::detect]: ./CharsetHelper.detect.md#charsethelper__detect
